<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\models\notifications\NotificationSetting;
use SurfSharekit\models\notifications\NotificationVersion;

/**
 * Class PersonConfig
 * @package SurfSharekit\Models
 * @property Boolean EmailNotificationsEnabled
 * @property Int NotificationVersion
 * @property array|string EnabledNotifications
 * @method Person Person
 */
class PersonConfig extends DataObject {
    private static $singular_name = 'PersonConfig';
    private static $plural_name = 'PersonConfigs';
    private static $table_name = 'SurfSharekit_PersonConfig';

    private static $db = [
        'EmailNotificationsEnabled' => 'Boolean(1)',
        "NotificationVersion" => "Int",
        "EnabledNotifications" => "Text"
    ];

    private static $belongs_to = [
        'Person' => Person::class
    ];

    protected function onBeforeWrite() {
        parent::onBeforeWrite();

        if (!$this->isInDB()) {
            $this->setNotificationDefaults();
        }
    }

    function setNotificationDefaults() {
        $latestNotificationVersion = NotificationVersion::getLatestSynchronizedVersion();
        if ($latestNotificationVersion) {
            $this->NotificationVersion = $latestNotificationVersion->VersionCode;
            $notificationSettings = NotificationSetting::get()->filter("NotificationVersion.VersionCode:LessThanOrEqual", $latestNotificationVersion->VersionCode)->column("Key");
            $this->EnabledNotifications = json_encode($notificationSettings);
        }
    }

    function getLoggedInUserPermissions() {
        $loggedInMember = Security::getCurrentUser();
        return [
            'canEdit' => $this->canEdit($loggedInMember),
            'canView' => $this->canView($loggedInMember)
        ];
    }

    public function getEnabledNotifications(): array {
        return $this->getField("EnabledNotifications") ?  json_decode($this->getField("EnabledNotifications"), true) : [];
    }

    /**
     * @param string $notificationKey
     * @return bool
     */
    public function isNotificationEnabled(string $notificationKey): bool {
        if (in_array($notificationKey, $this->EnabledNotifications)) {
            return true;
        }
        return false;
    }

    function canView($member = null) {
        return $this->Person()->canView($member);
    }

    function canEdit($member = null) {
        return $this->Person()->canEdit($member);
    }
}