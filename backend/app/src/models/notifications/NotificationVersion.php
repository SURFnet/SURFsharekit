<?php

namespace SurfSharekit\models\notifications;

use PermissionProviderTrait;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\PermissionProvider;
use UuidExtension;
use UuidRelationExtension;

/**
 * Class NotificationVersion
 * @package SurfSharekit\Models
 * @property Int VersionCode
 * @property Boolean IsSynchronized
 * @property String SynchronizationDatetime
 * @method HasManyList<Notification> Notifications
 * @method HasManyList<NotificationSetting> NotificationSettings
 * @method HasManyList<NotificationCategory> NotificationCategories
 * @method NotificationVersion NotificationVersion
 * @method NotificationCategory NotificationCategory
 */
class NotificationVersion extends DataObject implements PermissionProvider {
    use PermissionProviderTrait;

    private static $singular_name = 'Notification version';
    private static $plural_name = 'Notification versions';
    private static $table_name = 'SurfSharekit_NotificationVersion';

    private static $extensions = [
        UuidExtension::class,
        UuidRelationExtension::class
    ];

    private static $db = [
        "VersionCode" => "Int",
        "IsSynchronized" => "Boolean(0)",
        "SynchronizationDatetime" => "Datetime"
    ];

    private static $has_many = [
        "Notifications" => Notification::class,
        "NotificationSettings" => NotificationSetting::class,
        "NotificationCategories" => NotificationCategory::class
    ];

    private static $summary_fields = [
        "VersionCode",
        "IsSynchronized",
        "SynchronizationDatetime",
    ];

    private static $required_fields = [
        "VersionCode"
    ];

    private static $default_sort = "VersionCode DESC";

    function getCMSValidator(): RequiredFields {
        return new RequiredFields($this::$required_fields);
    }

    public function getTitle(): string {
        return $this->VersionCode ?: "";
    }

    public static function getLatestSynchronizedVersion(): ?NotificationVersion {
        /** @var null|NotificationVersion $latestNotificationVersion */
        $latestNotificationVersion = NotificationVersion::get()->filter("IsSynchronized", true)->first();
        return $latestNotificationVersion;
    }

    protected function onBeforeWrite() {
        parent::onBeforeWrite();
        if (!$this->isInDB()) {
            $latestVersion = NotificationVersion::get()->sort("VersionCode DESC")->first();
            $this->VersionCode = $latestVersion ? $latestVersion->VersionCode + 1 : 1;
        }
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        if ($this->isInDB()) {
            $fields->removeByName([
                "NotificationCategories",
                "NotificationSettings",
                "Notifications",
            ]);
            $fields->replaceField("VersionCode", $fields->dataFieldByName("VersionCode")->performReadonlyTransformation());
            $fields->replaceField("IsSynchronized", $fields->dataFieldByName("IsSynchronized")->performReadonlyTransformation());
            $fields->replaceField("SynchronizationDatetime", $fields->dataFieldByName("SynchronizationDatetime")->performReadonlyTransformation());
        } else {
            /** @var NotificationVersion $latestVersion */
            $latestVersion = NotificationVersion::get()->sort("VersionCode DESC")->first();
            $fields->dataFieldByName("VersionCode")->setValue($latestVersion ? $latestVersion->VersionCode + 1 : 1);
            $fields->replaceField("VersionCode", $fields->dataFieldByName("VersionCode")->performReadonlyTransformation());

            $fields->dataFieldByName("VersionCode")->dataValue();
            $fields->removeByName([
                "IsSynchronized",
                "SynchronizationDatetime"
            ]);
        }

        return $fields;
    }
}