<?php

namespace SurfSharekit\models\notifications;

use PermissionProviderTrait;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;
use UuidExtension;
use UuidRelationExtension;

/**
 * Class NotificationSetting
 * @package SurfSharekit\Models
 * @property String Key
 * @property Int NotificationID
 * @property Int NotificationVersionID
 * @property Int NotificationTypeID
 * @method Notification Notification
 * @method NotificationVersion NotificationVersion
 * @method NotificationType NotificationType
 */
class NotificationSetting extends DataObject implements PermissionProvider {
    use PermissionProviderTrait;

    private static $singular_name = 'Notification setting';
    private static $plural_name = 'Notification settings';
    private static $table_name = 'SurfSharekit_NotificationSetting';

    private static $extensions = [
        UuidExtension::class,
        UuidRelationExtension::class
    ];

    private static $db = [
        "Key" => "Varchar(255)",
        "IsDisabled" => "Boolean(0)"
    ];

    private static $has_one = [
        "Notification" => Notification::class,
        "NotificationVersion" => NotificationVersion::class,
        "NotificationType" => NotificationType::class
    ];

    private static $field_labels = [
        "NotificationVersion.VersionCode" => "Notification Version",
        "NotificationType.Title" => "Notification Type",
    ];

    private static $summary_fields = [
        "Key",
        "NotificationType.Title",
        "NotificationVersion.VersionCode",
    ];

    private static $required_fields = [
        "NotificationID",
        "NotificationVersionID",
        "NotificationTypeID",
        "Key",
    ];

    function getCMSValidator(): RequiredFields {
        return new RequiredFields($this::$required_fields);
    }


    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeByName("Key");

        if ($this->isInDB()) {
            $fields->replaceField("NotificationVersionID", $fields->dataFieldByName("NotificationVersionID")->performReadonlyTransformation());
            $fields->replaceField("NotificationID", $fields->dataFieldByName("NotificationID")->performReadonlyTransformation());
            $fields->replaceField("NotificationTypeID", $fields->dataFieldByName("NotificationTypeID")->performReadonlyTransformation());
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

    protected function onBeforeWrite() {
        parent::onBeforeWrite();

        if ($this->isChanged("NotificationTypeID")) {
            $this->Key = ($this->Notification()->Key . $this->NotificationType()->Key);
        }

        if (!$this->isInDB()) {
            $latestVersion = NotificationVersion::get()->sort("VersionCode DESC")->first();
            if ($latestVersion) {
                $this->NotificationVersionID = $latestVersion->ID;
            }
        }
    }

}