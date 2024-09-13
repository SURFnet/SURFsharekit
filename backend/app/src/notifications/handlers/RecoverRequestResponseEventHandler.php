<?php

use SurfSharekit\Models\Helper\EmailHelper;
use SurfSharekit\models\notifications\NotificationType;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\notifications\NotificationAction;
use SurfSharekit\notifications\NotificationKeyGenerator;

class RecoverRequestResponseEventHandler extends NotificationEventHandler
{
    /**
     * @var string approved | declined
     */
    private bool $isApproved;

    public function process(\SurfSharekit\Models\Event $event) {
        $relatedObject = $event->Object();
        if ($relatedObject instanceof RepoItem) {
            $emailData = [
                "Link" => $this->createDashboardURL(),
                "Title" => $relatedObject->Title
            ];

            if ($relatedObject->Owner()->exists()) {
                $ownerEmail = $relatedObject->Owner()->Email;

                $person = Person::get()->filter('Email', $ownerEmail)->first();
                $personConfig = $person->PersonConfig();

                if ($this->isApproved()) {
                    $notificationKey = NotificationKeyGenerator::generate($relatedObject->RepoType, NotificationAction::RECOVER_REQUEST_APPROVED, NotificationType::EMAIL);
                } else {
                    $notificationKey = NotificationKeyGenerator::generate($relatedObject->RepoType, NotificationAction::RECOVER_REQUEST_DECLINED, NotificationType::EMAIL);
                }

                if ($personConfig && $personConfig->exists() && $personConfig->isNotificationEnabled($notificationKey)) {
                    if ($this->isApproved()) {
                        EmailHelper::sendEmail([$ownerEmail], 'Email\\RecoverRequestApproved', "SURFsharekit | Je verwijderverzoek is goedgekeurd", $emailData);
                    } else {
                        EmailHelper::sendEmail([$ownerEmail], 'Email\\RecoverRequestDeclined', "SURFsharekit | Je verwijderverzoek is afgekeurd", $emailData);
                    }
                }
            }
        }
    }

    public function setIsApproved(bool $approved): self {
        $this->isApproved = $approved;

        return $this;
    }

    public function isApproved() {
        return $this->isApproved;
    }
}