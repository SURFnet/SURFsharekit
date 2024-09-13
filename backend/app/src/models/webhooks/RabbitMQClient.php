<?php

namespace SurfSharekit\models\webhooks;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use SilverStripe\Core\Environment;
use SurfSharekit\Models\Helper\Logger;

class RabbitMQClient {
    private static $connection;

    public static function getConnection(): AMQPStreamConnection
    {
        if (!self::$connection) {
            // Establish the connection only if it doesn't exist
            self::$connection = new AMQPStreamConnection(Environment::getEnv("RABBITMQ_HOSTNAME"), Environment::getEnv("RABBITMQ_PORT"), 'guest', 'guest');
        }

        return self::$connection;
    }

    /**
     * @param Webhook $webhook
     * @return void
     * @throws \Exception
     */
    public static function queueWebhook(Webhook $webhook) {
        try {
            $payload = [
                "id" => $webhook->Uuid,
                "url" => $webhook->Url,
                "payload" => $webhook->getPayload()
            ];

            $msg = new AMQPMessage(
                json_encode($payload),
                array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
            );

            $queueName = Environment::getEnv("WEBHOOK_QUEUE_NAME");

            $connection = self::getConnection();
            $channel = $connection->channel();
            $channel->queue_declare($queueName, false, true, false, false);
            $channel->basic_publish($msg, '', $queueName);
            $channel->close();
            $connection->close();
        } catch (Exception $e) {
            Logger::debugLog("Failed queueing webhook $webhook->Uuid");
        }
    }
}