<?php

use SilverStripe\Core\Environment;
use SurfSharekit\Models\Event;

abstract class NotificationEventHandler {

    private static $instances = array();

    public static function getInstance() {
        $class = get_called_class();
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    abstract public function process(Event $event);

    protected function createDashboardURL(): string {
        return Environment::getEnv("FRONTEND_BASE_URL") . '/dashboard';
    }
}