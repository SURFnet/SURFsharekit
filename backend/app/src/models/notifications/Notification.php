<?php

namespace SurfSharekit\models\notifications;

use PermissionProviderTrait;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\PermissionRole;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Logger;
use UuidExtension;
use UuidRelationExtension;

/**
 * Class Notification
 * @package SurfSharekit\Models
 * @property String Key
 * @property String LabelNL
 * @property String LabelEN
 * @property Int SortOrder
 * @property Int NotificationVersionID
 * @property Int NotificationCategoryID
 * @method NotificationCategory NotificationCategory
 * @method NotificationVersion NotificationVersion
 * @method HasManyList<NotificationSetting> NotificationSettings
 * @method ManyManyList<PermissionRole> PermissionRoles
 */
class Notification extends DataObject implements PermissionProvider {
    use PermissionProviderTrait;

    private static $singular_name = 'Notification';
    private static $plural_name = 'Notifications';
    private static $table_name = 'SurfSharekit_Notification';

    private static $extensions = [
        UuidExtension::class,
        UuidRelationExtension::class
    ];

    const keys = [
        // Claims
        "ClaimRequestApproved",
        "ClaimRequestDeclined",
        "ClaimRequestSubmitted",

        // Sanitize
        "SanitizationResult",

        // LearningObject
        "LearningObjectApproved",
        "LearningObjectDeclined",
        "LearningObjectFillRequest",
        "LearningObjectReviewRequest",
        "LearningObjectRecoverRequest",
        "LearningObjectRecoverRequestApproved",
        "LearningObjectRecoverRequestDeclined",

        // ResearchObject
        "ResearchObjectApproved",
        "ResearchObjectDeclined",
        "ResearchObjectFillRequest",
        "ResearchObjectReviewRequest",
        "ResearchObjectRecoverRequest",
        "ResearchObjectRecoverRequestApproved",
        "ResearchObjectRecoverRequestDeclined",

        // PublicationRecord
        "PublicationRecordApproved",
        "PublicationRecordDeclined",
        "PublicationRecordFillRequest",
        "PublicationRecordReviewRequest",
        "PublicationRecordRecoverRequest",
        "PublicationRecordRecoverRequestApproved",
        "PublicationRecordRecoverRequestDeclined",

        // Dataset
        "DatasetApproved",
        "DatasetDeclined",
        "DatasetFillRequest",
        "DatasetReviewRequest",
        "DatasetRecoverRequest",
        "DatasetRecoverRequestApproved",
        "DatasetRecoverRequestDeclined",

        // Project
        "ProjectApproved",
        "ProjectDeclined",
        "ProjectFillRequest",
        "ProjectReviewRequest",
        "ProjectRecoverRequest",
        "ProjectRecoverRequestApproved",
        "ProjectRecoverRequestDeclined",
    ];

    private static $db = [
        "Key" => "Varchar(255)",
        "LabelNL" => "Text",
        "LabelEN" => "Text",
        "SortOrder" => "Int"
    ];

    private static $has_one = [
        "NotificationVersion" => NotificationVersion::class,
        "NotificationCategory" => NotificationCategory::class
    ];

    private static $has_many = [
        "NotificationSettings" => NotificationSetting::class
    ];

    private static $many_many = [
        "PermissionRoles" => PermissionRole::class
    ];

    private static $summary_fields = [
        "Key",
        "NotificationCategory.Title",
        "DisplayNotificationSettingTypes",
        "NotificationVersion.VersionCode",
    ];

    private static $field_labels = [
        "NotificationVersion.VersionCode" => "Notification Version",
        "NotificationCategory.Title" => "Notification Category",
        "DisplayNotificationSettingTypes" => "NotificationSetting Types"
    ];

    private static $required_fields = [
        "NotificationVersionID",
        "NotificationCategoryID",
        "Key",
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

        $keyDropdownField = new DropdownField("Key", "Key", array_combine($this::keys, $this::keys));
        $fields->replaceField("Key", $keyDropdownField);

        $disabledNotificationItems = Notification::get()->exclude("ID", $this->ID)->column("Key");
        $keyDropdownField->setDisabledItems($disabledNotificationItems);

        $fields->removeByName([
            "SortOrder"
        ]);

        if ($this->isInDB()) {
            $fields->removeByName("PermissionRoles");
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

        $permissionRoleListboxField = new ListboxField("PermissionRoles", "Visible for", PermissionRole::get()->map());
        $fields->addFieldToTab("Root.Main", $permissionRoleListboxField);

        return $fields;
    }

    public function getTitle(): string {
        return $this->Key ?: "";
    }

    public function getDisplayNotificationSettingTypes(): string {
        $settings = $this->NotificationSettings();
        $displayString = "";
        /** @var NotificationSetting $setting */
        foreach ($settings as $setting) {
            $displayString .= ($setting->NotificationType()->Key . " ");
        }
        return $displayString;
    }
}