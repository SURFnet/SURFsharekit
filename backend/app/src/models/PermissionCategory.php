<?php

namespace SurfSharekit\models;

use PermissionProviderTrait;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\PermissionProvider;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use UuidExtension;
use UuidRelationExtension;

/**
 * Class PermissionCategory
 * @package SurfSharekit\Models
 * @property String Title
 * @property String LabelNL
 * @property String LabelEN
 * @property Int SortOrder
 * @method HasManyList<PermissionDescription> PermissionDescriptions
 */
class PermissionCategory extends DataObject implements PermissionProvider {
    use PermissionProviderTrait;

    private static $singular_name = 'Permission Category';
    private static $plural_name = 'Permission Categories';
    private static $table_name = 'SurfSharekit_PermissionCategory';

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

    private static $has_many = [
        "PermissionDescriptions" => PermissionDescription::class
    ];

    private static $required_fields = [
        "Title",
    ];

    function getCMSValidator(): RequiredFields {
        return new RequiredFields($this::$required_fields);
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeByName("SortOrder");

        if ($this->isInDB()) {
            /** @var GridField $permissionDescriptionGridField */
            $permissionDescriptionGridField = $fields->dataFieldByName("PermissionDescriptions");
            $gridFieldConfig = $permissionDescriptionGridField->getConfig();
            $gridFieldConfig->addComponent(new GridFieldOrderableRows("SortOrder"));
        }

        return $fields;
    }
}