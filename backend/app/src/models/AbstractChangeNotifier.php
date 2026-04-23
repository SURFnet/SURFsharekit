<?php

namespace SurfSharekit\Models;

use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataObject;
use SurfSharekit\Models\Helper\Logger;
use Throwable;

/**
 * Base class for object-specific change notifiers.
 * Subclasses implement payload creation and reuse the Redis publishing logic.
 */
abstract class AbstractChangeNotifier {
    /**
     * Build a payload when ChangeNotifier is being called.
     * Extend this class for the specific DataObject you want to do this for.
     */
    abstract public function buildPayload(DataObject $dataObject, array $changedFields): ?array;

    /**
     * Public entry point used by the dispatcher.
     */
    public function notify(DataObject $dataObject, array $changedFields): void {
        $payload = $this->buildPayload($dataObject, $changedFields);
        if ($payload) {
            $this->publishToRedis($payload);
        }
    }

    /**
     * Shared Redis publishing helper.
     */
    protected function publishToRedis(array $payload): void {
        $queueName = Environment::getEnv('REDIS_QUEUE_NAME') ?: 'repoitem-change-events'; // TODO: Change fallback in the future if we're gonna introduce different queues
        $host = Environment::getEnv('REDIS_HOST') ?? Environment::getEnv('REDIS_HOSTNAME') ?? 'redis';
        $port = (int)(Environment::getEnv('REDIS_PORT') ?: 6379);
        $password = Environment::getEnv('REDIS_PASSWORD');
        $user = Environment::getEnv('REDIS_USER') ?: null;

        if (!$queueName) {
            return;
        }

        if (!class_exists(\Redis::class)) {
            Logger::debugLog('Redis extension missing: cannot publish change notification.');
            return;
        }

        try {
            $redis = new \Redis();
            $redis->connect($host, $port);

            if ($password) {
                if ($user) {
                    $redis->auth([$user, $password]);
                } else {
                    $redis->auth($password);
                }
            }

            $encodedPayload = json_encode($payload);
            if ($encodedPayload === false) {
                Logger::debugLog('Failed encoding change notification payload for Redis queue.');
                return;
            }

            // Ensure the payload is a flat associative array of scalar values
            $fields = array_map(function ($value) {
                return is_scalar($value) ? (string)$value : json_encode($value);
            }, $payload);

            // Add directly as stream fields
            $redis->xAdd($queueName, '*', $fields);
        } catch (Throwable $exception) {
            Logger::debugLog('Failed publishing change notification to Redis: ' . $exception->getMessage());
        }
    }
}
