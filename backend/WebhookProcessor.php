<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use SilverStripe\Core\Environment;

$processor = new WebhookProcessor();
$processor->run();

class WebhookProcessor {

    const LOG_FILE_PATH = "/var/log/";
    const LOG_FILE_NAME = "webhook-processor.log";

    /** @var AMQPStreamConnection $connection */
    private $connection;
    private $channel;
    /** @var Client $guzzleClient */
    private $guzzleClient;

    public function __construct() {
        $this->connection = new AMQPStreamConnection(Environment::getEnv("RABBITMQ_HOSTNAME"), Environment::getEnv("RABBITMQ_PORT"), 'guest', 'guest');
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare(Environment::getEnv("WEBHOOK_QUEUE_NAME"), false, true, false, false);
        $this->guzzleClient = new Client();

        $this->channel->basic_qos(null, 1, false);
        $this->channel->basic_consume(Environment::getEnv("WEBHOOK_QUEUE_NAME"), '', false, false, false, false, $this->getMessageCallback());
    }

    public function run() {
        echo " [*] Waiting for messages. To exit press CTRL+\n";

        try {
            $this->channel->consume();
        } catch (\Throwable $e) {
            echo $e->getMessage();
            $this->writeLog("An error occurred while consuming fron : " . $e->getMessage());
        }

        $this->channel->close();
        $this->connection->close();
    }

    /**
     * @return Closure
     */
    private function getMessageCallback(): Closure {
        $guzzleClient = $this->guzzleClient;

        return function ($msg) use (&$guzzleClient) {
            echo "Received message: " . $msg->getBody() . " \n";

            $headers = [
                'content-type' => 'application/vnd.api+json'
            ];

            try {
                $decodedMsg = json_decode($msg->getBody(), true);

                $guzzleClient->post(
                    $decodedMsg["url"],
                    [
                        RequestOptions::HEADERS => $headers,
                        RequestOptions::BODY => $decodedMsg["payload"]
                    ]
                );

                $msg->ack();
            } catch (Exception $e) {
                $msg->nack();
                $this->writeLog("Failed processing webhook " .  $decodedMsg["id"] . " : " . $e->getMessage());
            }
        };
    }

    /**
     * @param string $message
     * @return void
     */
    private function writeLog(string $message): void {
        $timestamp = "[" . (new DateTime())->format("c") . "] ";
        $logEntry = $timestamp . $message;
        file_put_contents(self::LOG_FILE_PATH . self::LOG_FILE_NAME, $logEntry.PHP_EOL , FILE_APPEND);
    }
}

