<?php

use SilverStripe\Control\Email\Email;
use SurfSharekit\Models\BulkAction;
use SurfSharekit\Models\Event;
use SurfSharekit\Models\Helper\EmailHelper;
use SurfSharekit\Models\PersonConfig;

class SanitizationProcessEndEventHandler extends NotificationEventHandler {

    public function process(Event $event) {
        $relatedObject = $event->Object();
        if ($relatedObject instanceof BulkAction) {
            /** @param $relatedObject BulkAction * */

            $createdBy = $relatedObject->createdBy();
            if ($createdBy->exists()) {
                /** @var PersonConfig $personConfig */
                $personConfig = $createdBy->PersonConfig();
                if ($personConfig && $personConfig->exists() && $personConfig->isNotificationEnabled("SanitizationResult")){
                    switch ($relatedObject->ProcessStatus) {
                        case 'COMPLETED':
                            $this->sendCompletedMail($relatedObject);
                            break;
                        case 'FAILED':
                            $this->sendFailMail($relatedObject);
                            break;
                    }
                }
            }
        }
    }

    private function sendCompletedMail(BulkAction $bulkAction) {
        $emailData = [
            "Link" => $this->createDashboardURL(),
            "Time" => $bulkAction->Created,
            "TotalCount" => $bulkAction->TotalCount,
            "SuccessCount" => $bulkAction->SuccessCount,
            "FailCount" => $bulkAction->FailCount
        ];
        EmailHelper::sendEmail([$bulkAction->createdBy()->Email], 'Email\\SanitizationProcessSuccess', "SURFsharekit | Saneerproces voltooid", $emailData);
        echo("[PROCESSING_EVENT] - Sending an email to the inititor of BulkAction ($bulkAction->Uuid)..."); echo("<br>");
    }

    private function sendFailMail(BulkAction $bulkAction) {
        $emailData = [
            "Link" => $this->createDashboardURL(),
            "Time" => $bulkAction->Created
        ];

        echo("[PROCESSING_EVENT] - Sending an email to the inititor of BulkAction ($bulkAction->Uuid)...");
        echo("<br>");

        EmailHelper::sendEmail([$bulkAction->createdBy()->Email], 'Email\\SanitizationProcessFailed', 'SURFsharekit | Saneerproces mislukt', $emailData);
    }

}