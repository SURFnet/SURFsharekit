<?php

namespace SurfSharekit\models\notifications;

use PermissionProviderTrait;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\PermissionProvider;
use UuidExtension;
use UuidRelationExtension;

/**
 * Class NotificationType
 * @package SurfSharekit\Models
 * @property String Title
 * @property String Key
 * @property Int NotificationVersionID
 * @method NotificationVersion NotificationVersion
 * @method HasManyList<NotificationSetting> NotificationSettings
 */
class NotificationType extends DataObject implements PermissionProvider {
    use PermissionProviderTrait;

    const EMAIL = "Email";

    private static $singular_name = 'Notification type';
    private static $plural_name = 'Notification types';
    private static $table_name = 'SurfSharekit_NotificationType';

    const keys = [
        self::EMAIL
    ];

    private static $extensions = [
        UuidExtension::class,
        UuidRelationExtension::class
    ];

    private static $db = [
        "Title" => "Varchar(255)",
        "Key" => "Varchar(255)",
    ];

    private static $has_one = [
        "NotificationVersion" => NotificationVersion::class,
    ];

    private static $has_many = [
        "NotificationSettings" => NotificationSetting::class,
    ];

    private static $field_labels = [
        "NotificationVersion.VersionCode" => "Notification Version"
    ];
    private static $summary_fields = [
        "Title",
        "Key",
        "NotificationVersion.VersionCode",
    ];

    private static $required_fields = [
        "Title",
        "Key",
        "NotificationVersionID"
    ];

    function getCMSValidator(): RequiredFields {
        return new RequiredFields($this::$required_fields);
    }

    protected function onBeforeWrite() {
        parent::onBeforeWrite();

        if (!$this->isInDB()) {
            $latestVersion = NotificationVersion::get()->sort("VersionCode DESC")->first();
            if ($latestVersion) {
                $this->NotificationVersionID = $latestVersion->ID;
            }
        }
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $keyDropdownFields = new DropdownField("Key", "Key", array_combine(self::keys, self::keys));
        $fields->replaceField("Key", $keyDropdownFields);

        $fields->removeByName([
            "NotificationSettings"
        ]);

        if ($this->isInDB()) {
            $fields->replaceField("NotificationVersionID", $fields->dataFieldByName("NotificationVersionID")->performReadonlyTransformation());
            $fields->replaceField("Key", $fields->dataFieldByName("Key")->performReadonlyTransformation());
        } else {
            /** @var NotificationVersion $latestVersion */
            $latestVersion = NotificationVersion::get()->sort("VersionCode DESC")->first();
            if ($latestVersion) {
                $fields->dataFieldByName("NotificationVersionID")->setValue($latestVersion->ID);
                $fields->replaceField("NotificationVersionID", $fields->dataFieldByName("NotificationVersionID")->performReadonlyTransformation());
            }
        }

        return $fields;
    }
}