<?php

use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Event;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\EmailHelper;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\models\notifications\NotificationType;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonConfig;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\notifications\NotificationAction;
use SurfSharekit\notifications\NotificationKeyGenerator;

class RepoItemStatusChangedEventHandler extends NotificationEventHandler {

    public function process(Event $event) {
        $relatedObject = $event->Object();
        if ($relatedObject instanceof RepoItem) {
            /** @var $relatedObject RepoItem * */

            $dashboardLink = $this->createDashboardURL();
            $emailData = [
                "Link" => $dashboardLink
            ];

            $owner = $relatedObject->Owner();
            if ($owner->exists()){
                /** @var PersonConfig $personConfig */
                $personConfig = $owner->PersonConfig();

                switch ($relatedObject->Status) {
                    case "Published":
                    case "Approved":
                        $notificationKey = NotificationKeyGenerator::generate($relatedObject->RepoType, NotificationAction::APPROVED, NotificationType::EMAIL);
                        if ($personConfig && $personConfig->exists() && $personConfig->isNotificationEnabled($notificationKey)) {
                            echo("[PROCESSING_EVENT] - Sending an email to 1 unique email addresses...");
                            echo("<br>");
                            Logger::debugLog("[PROCESSING_EVENT] - Sending [SURF Sharekit | Je materiaal is goedgekeurd] to 1 unique email addresses...");
                            EmailHelper::sendEmail([$relatedObject->Owner()->Email], 'Email\\RepoItemApproved', "SURFsharekit | Je materiaal is goedgekeurd", $emailData);
                            break;
                        }
                        break;
                    case "Submitted":
                        $ownerEmail = $relatedObject->Owner()->Email;
                        if(is_null($ownerEmail)){
                            $ownerEmail = '';
                        }
                        $personsToMail = $this->getAllPersonsToMail($relatedObject, $ownerEmail);
                        echo("[PROCESSING_EVENT] - Sending an email to " . count($personsToMail) . " unique email addresses...");
                        echo("<br>");
                        Logger::debugLog("[PROCESSING_EVENT] - Sending [SURF Sharekit | Er staat een materiaal voor je klaar] to " . count($personsToMail) . " unique email addresses...");
                        foreach ($personsToMail as $personEmail) {
                            if(strlen($personEmail) == 0){
                                break; // Exclude empty email to prevent memory exhaust
                            }
                            $person = Person::get()->filter('Email', $personEmail)->first();
                            $personConfig = $person->PersonConfig();
                            $notificationKey = NotificationKeyGenerator::generate($relatedObject->RepoType, NotificationAction::REVIEW_REQUEST, NotificationType::EMAIL);
                            if ($personConfig && $personConfig->exists() && $personConfig->isNotificationEnabled($notificationKey)) {
                                $emailData['Role'] = $person->getHighestInstituteRole($relatedObject->Institute());
                                $emailData['PublicationType'] = $relatedObject->TranslatedType;
                                $emailData['Institute'] = $relatedObject->Institute->Title;
                                EmailHelper::sendEmail([$personEmail], 'Email\\RepoItemSubmitted', "SURFsharekit | Er staat een materiaal voor je klaar", $emailData);
                            }
                        }
                        break;
                    case "Declined":
                        $notificationKey = NotificationKeyGenerator::generate($relatedObject->RepoType, NotificationAction::DECLINED, NotificationType::EMAIL);
                        if ($personConfig && $personConfig->exists() && $personConfig->isNotificationEnabled($notificationKey)) {
                            echo("[PROCESSING_EVENT] - Sending an email to 1 unique email addresses...");
                            echo("<br>");
                            Logger::debugLog("[PROCESSING_EVENT] - Sending [SURF Sharekit | Je materiaal is afgekeurd] to 1 unique email addresses...");
                            EmailHelper::sendEmail([$relatedObject->Owner()->Email], 'Email\\RepoItemDeclined', "SURFsharekit | Je materiaal is afgekeurd", $emailData);
                            break;
                        }
                        break;
                    default: /* repoItem status was changed in the meantime, do not send email */ break;
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
            ->filter('Email:not', [$emailToExclude, '', null])
            ->leftJoin('SurfSharekit_PersonConfig', 'SurfSharekit_Person.PersonConfigID = SurfSharekit_PersonConfig.ID');
        // Exclude empty email to prevent memory exhaust
        return $personsToMail->columnUnique('Email');
    }
}