<?php

namespace SurfSharekit\notifications;

use Exception;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\RepoItem;

class NotificationKeyGenerator {

    /**
     * @param string $notificationBaseKey
     * @param string $notificationAction
     * @param string $notificationType
     * @return string|null
     */
    public static function generate(string $notificationBaseKey, string $notificationAction, string $notificationType): ?string {
        return  $notificationBaseKey . $notificationAction . $notificationType;
    }
}