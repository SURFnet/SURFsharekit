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

class FillRequestCreatedEventHandler extends NotificationEventHandler {
    public function process(Event $event) {
        $relatedObject = $event->Object();
        if ($relatedObject instanceof RepoItem) {
            $emailData = [
                "Link" => $this->createDashboardURL()
            ];

            $personsToMail = $this->getAllPersonsToMail($relatedObject);

            foreach ($personsToMail as $personEmail) {
                $person = Person::get()->filter('Email', $personEmail)->first();
                $personConfig = $person->PersonConfig();
                $notificationKey = NotificationKeyGenerator::generate($relatedObject->RepoType, NotificationAction::FILL_REQUEST, NotificationType::EMAIL);
                if ($personConfig && $personConfig->exists() && $personConfig->isNotificationEnabled($notificationKey)) {
                    $emailData['Role'] = $person->getHighestInstituteRole($relatedObject->Institute());
                    $emailData['PublicationType'] = $relatedObject->TranslatedType;
                    $emailData['Institute'] = $relatedObject->Institute->Title;
                    EmailHelper::sendEmail([$personEmail], 'Email\\FillRequest', "SURFsharekit | Er staat een aanvulverzoek voor je klaar", $emailData);
                }
            }
        }
    }

    private function getAllPersonsToMail(RepoItem $repoItem): array {
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
            ->leftJoin('SurfSharekit_PersonConfig', 'SurfSharekit_Person.PersonConfigID = SurfSharekit_PersonConfig.ID');

        return $personsToMail->columnUnique('Email');
    }
}