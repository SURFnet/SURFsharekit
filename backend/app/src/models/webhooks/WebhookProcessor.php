<?php

namespace SurfSharekit\models\webhooks;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Ramsey\Uuid\Uuid;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\Helper\Logger;

class WebhookProcessor extends BuildTask {

    private $processorID;
    private $client;

    public function __construct() {
        parent::__construct();
        $this->processorID = Uuid::uuid4();
        $this->client = new Client();
    }

    public function run($request) {
        // Update Webhooks with ProcessorID
        $this->claimWebhooks();

        $webhooksToProcess = Webhook::get()->filter(["ProcessorID" => $this->processorID]);
        foreach ($webhooksToProcess as $webhook) {
            try {
                $this->sendWebhook($webhook, true);
            } catch (Exception $e) {
                continue;
            }
        }

        $this->deleteProcessedWebhooks();
    }

    /**
     * @return void
     */
    private function claimWebhooks(): void {
        DB::get_conn()->preparedQuery("
            UPDATE SurfSharekit_Webhook as wh
            SET wh.ProcessorID = ?
            WHERE wh.ProcessorID IS NULL;
        ", [$this->processorID]);
    }

    /**
     * @return void
     */
    private function deleteProcessedWebhooks(): void {
        DB::get_conn()->preparedQuery("
            DELETE FROM SurfSharekit_Webhook
            WHERE ProcessorID = ?;
        ", [$this->processorID]);
    }

    /**
     * @param Webhook $webhook
     * @param bool $retryOnFailure
     * @return void
     */
    private function sendWebhook(Webhook $webhook, bool $retryOnFailure = false) {
        $headers = [
            'content-type' => 'application/vnd.api+json'
        ];

        try {
            $this->client->post(
                $webhook->Url,
                [
                    RequestOptions::HEADERS => $headers,
                    RequestOptions::BODY => $webhook->getPayload()
                ]
            );
        } catch (Exception $e) {
            Logger::debugLog("Webhook $webhook->Uuid failed: ".  $e->getMessage());
            if ($retryOnFailure) {
                $this->sendWebhook($webhook);
            }
        }
    }
}