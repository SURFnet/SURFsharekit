<?php

use SilverStripe\Control\Email\Email;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Claim;
use SurfSharekit\Models\Event;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\EmailHelper;
use SurfSharekit\models\notifications\NotificationType;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonConfig;
use SurfSharekit\notifications\ClaimNotificationSubject;
use SurfSharekit\notifications\NotificationAction;
use SurfSharekit\notifications\NotificationKeyGenerator;

class ClaimStatusChangedEventHandler extends NotificationEventHandler {
    public function process(Event $event) {
        $claim = $event->Object();
        if ($claim instanceof Claim) {
            $personToClaim = $claim->Object();
            $claimInstitute = $claim->Institute();
            $claimCreator = $claim->CreatedBy();
            $emailData = [
                "Link" => $this->createDashboardURL(),
                "AuthorName" => $claimCreator->Title,
                "PersonName" => $personToClaim->Title,
                "Reason" => $claim->ReasonOfDecline,
                "GroupName" => $claimInstitute->getRootInstitute()->Groups()->filter(['Roles.Title' => RoleConstant::MEMBER])->first()->Title
            ];

            if ($claimCreator->exists()) {
                /** @var PersonConfig $personConfig */
                $personConfig = $claimCreator->PersonConfig();

                /** @param $personToClaim Claim * */
                switch ($claim->Status) {
                    case "Submitted":
                        $personsToMail = $this->getAllAvailableClaimApproversMails($personToClaim, [$claimCreator->Email, $personToClaim->Email]);
                        foreach ($personsToMail as $person){
                            $personConfig = $person->PersonConfig();
                            $notificationKey = NotificationKeyGenerator::generate("ClaimRequest", NotificationAction::SUBMITTED, NotificationType::EMAIL);
                            if ($personConfig && $personConfig->isNotificationEnabled($notificationKey)) {
                                EmailHelper::sendEmail($personsToMail, 'Email\\PersonClaimSubmitted', "SURFsharekit | Verzoek $personToClaim->Title", $emailData);
                            }
                        }
                        break;
                    case "Approved":
                        $notificationKey = NotificationKeyGenerator::generate("ClaimRequest", NotificationAction::APPROVED, NotificationType::EMAIL);
                        if ($personConfig && $personConfig->exists() && $personConfig->isNotificationEnabled($notificationKey)) {
                            EmailHelper::sendEmail([$claimCreator->Email], 'Email\\PersonClaimApproved', "SURFsharekit | Goedgekeurd verzoek $personToClaim->Title", $emailData);
                            break;
                        }
                        break;
                    case "Declined":
                        $notificationKey = NotificationKeyGenerator::generate("ClaimRequest", NotificationAction::DECLINED, NotificationType::EMAIL);
                        if ($personConfig && $personConfig->exists() && $personConfig->isNotificationEnabled($notificationKey)) {
                            EmailHelper::sendEmail([$claimCreator->Email], 'Email\\PersonClaimDeclined', "SURFsharekit | Afgekeurd verzoek $personToClaim->Title", $emailData);
                            break;
                        }
                        break;
                    default:
                        break;
                }
            }
        }
    }

    private function getAllAvailableClaimApproversMails($person, array $emailToExclude): array {
        $permissionsChecks[] = "(Permission.Code = 'PERSON_CLAIM_OTHER' OR PermissionRoleCode.Code = 'PERSON_CLAIM_OTHER')";
        $personsToMail = Person::get()
            ->innerJoin('Group_Members', 'Group_Members.MemberID = SurfSharekit_Person.ID')
            ->innerJoin('Group', 'Group_Members.GroupID = Group.ID')
            ->innerJoin('(' . InstituteScoper::getInstitutesOfUpperScope($person->extend('getInstituteIdentifiers')[0])->sql() . ')', 'gi.ID = Group.InstituteID', 'gi')
            //get parents of groups
            ->leftJoin('Group_Roles', 'Group_Roles.GroupID = Group.ID')
            //join on permissions
            ->leftJoin('PermissionRoleCode', 'PermissionRoleCode.RoleID = Group_Roles.PermissionRoleID')
            ->leftJoin('Permission', 'Permission.GroupID = Group_Roles.GroupID')
            ->whereAny($permissionsChecks)
            ->filter(['Email:not', $emailToExclude])
            ->leftJoin('SurfSharekit_PersonConfig', 'Person.PersonConfigID = PersonConfig.ID')
            ->where(['SurfSharekit_PersonConfig.EmailNotificationsEnabled'=> 1]);
        return $personsToMail->columnUnique('Email');
    }
}