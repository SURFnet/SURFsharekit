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

}
