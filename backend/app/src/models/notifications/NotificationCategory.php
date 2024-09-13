<?php

namespace SurfSharekit\models\notifications;

use PermissionProviderTrait;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\PermissionProvider;
use SurfSharekit\api\internal\descriptions\NotificationJsonApiDescription;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use UuidExtension;
use UuidRelationExtension;

/**
 * Class NotificationCategory
 * @package SurfSharekit\Models
 * @property String Title
 * @property String LabelNL
 * @property String LabelEN
 * @property Int SortOrder
 * @property Int NotificationVersionID
 * @method NotificationVersion NotificationVersion
 * @method HasManyList<Notification> Notifications
 */
class NotificationCategory extends DataObject implements PermissionProvider {
    use PermissionProviderTrait;

    private static $singular_name = 'Notification category';
    private static $plural_name = 'Notification categories';
    private static $table_name = 'SurfSharekit_NotificationCategory';

    private static $extensions = [
        UuidExtension::class,
        UuidRelationExtension::class
    ];

    private static $db = [
        "Title" => "Varchar(255)",
        "LabelNL" => "Varchar(255)",
        "LabelEN" => "Varchar(255)",
        "SortOrder" => "Int"
    ];

    private static $has_one = [
        "NotificationVersion" => NotificationVersion::class
    ];

    private static $has_many = [
        "Notifications" => Notification::class
    ];

    private static $summary_fields = [
        "Title",
        "LabelNL",
        "LabelEN",
        "NotificationVersion.VersionCode"
    ];

    private static $field_labels = [
        "NotificationVersion.VersionCode" => "Notification Version"
    ];

    private static $required_fields = [
        "NotificationVersionID",
        "Title"
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

        $fields->removeByName([
            "SortOrder"
        ]);

        if ($this->isInDB()) {
            $fields->replaceField("NotificationVersionID", $fields->dataFieldByName("NotificationVersionID")->performReadonlyTransformation());

            /** @var GridField $notificationsGridField */
            $notificationsGridField = $fields->dataFieldByName("Notifications");
            $notificationsGridFieldConfig = $notificationsGridField->getConfig();
            $notificationsGridFieldConfig->addComponents([new GridFieldOrderableRows("SortOrder")]);
            $notificationsGridFieldConfig->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
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