<?php

use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Event;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\EmailHelper;
use SurfSharekit\models\notifications\NotificationType;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\notifications\NotificationAction;
use SurfSharekit\notifications\NotificationKeyGenerator;

class RecoverRequestCreatedEventHandler extends NotificationEventHandler
{
    public function process(Event $event) {
        $relatedObject = $event->Object();
        if ($relatedObject instanceof RepoItem) {
            $emailData = [
                "Link" => $this->createDashboardURL()
            ];

            if ($relatedObject->Owner()->exists()) {
                $ownerEmail = $relatedObject->Owner()->Email;
                $personsToMail = $this->getAllPersonsToMail($relatedObject, $ownerEmail);

                foreach ($personsToMail as $personEmail) {
                    $person = Person::get()->filter('Email', $personEmail)->first();
                    $personConfig = $person->PersonConfig();
                    $notificationKey = NotificationKeyGenerator::generate($relatedObject->RepoType, NotificationAction::RECOVER_REQUEST, NotificationType::EMAIL);

                    if ($personConfig && $personConfig->exists() && $personConfig->isNotificationEnabled($notificationKey)) {
                        $emailData['Role'] = $person->getHighestInstituteRole($relatedObject->Institute());
                        $emailData['PublicationType'] = $relatedObject->TranslatedType;
                        $emailData['Institute'] = $relatedObject->Institute->Title;
                        EmailHelper::sendEmail([$personEmail], 'Email\\RecoverRequest', "SURFsharekit | Er staat een verwijderverzoek voor je klaar", $emailData);
                    }
                }
            }
        }
    }

    private function getAllPersonsToMail(RepoItem $repoItem, string $emailToExclude): array {
        foreach (Constants::ALL_REPOTYPES as $type) {
            $typeUpper = strtoupper($type);
            $clauses[] = "(SurfSharekit_RepoItem.RepoType = '$type' AND (Permission.Code = 'REPOITEM_PUBLISH_$typeUpper' OR PermissionRoleCode.Code = 'REPOITEM_PUBLISH_$typeUpper'))";
        }

        $personsToMail = Person::get()
            ->innerJoin('Group_Members', 'Group_Members.MemberID = SurfSharekit_Person.ID')
            ->innerJoin('Group', 'Group_Members.GroupID = Group.ID')
            ->innerJoin('(' . InstituteScoper::getInstitutesOfUpperScope([$repoItem->InstituteID])->sql() . ')', 'gi.ID = Group.InstituteID', 'gi')
            //get parents of groups
            ->leftJoin('Group_Roles', 'Group_Roles.GroupID = Group.ID')
            //join on permissions
            ->leftJoin('PermissionRoleCode', 'PermissionRoleCode.RoleID = Group_Roles.PermissionRoleID')
            ->leftJoin('Permission', 'Permission.GroupID = Group_Roles.GroupID')
            ->innerJoin('SurfSharekit_RepoItem', "SurfSharekit_RepoItem.ID = $repoItem->ID")
            ->whereAny($clauses)
            ->filter('Email:not', $emailToExclude)
            ->leftJoin('SurfSharekit_PersonConfig', 'SurfSharekit_Person.PersonConfigID = SurfSharekit_PersonConfig.ID');

        return $personsToMail->columnUnique('Email');
    }
}