<?php

namespace SurfSharekit\models;

use PermissionProviderTrait;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\PermissionRoleCode;
use UuidExtension;
use UuidRelationExtension;

/**
 * Class PermissionDescription
 * @package SurfSharekit\Models
 * @property String Title
 * @property String TextNL
 * @property String TextEN
 * @property String PermissionCode
 * @property Int SortOrder
 * @property Int PermissionCategoryID
 * @method PermissionCategory PermissionCategory
 */
class PermissionDescription extends DataObject implements PermissionProvider {
    use PermissionProviderTrait;

    private static $singular_name = 'Permission description';
    private static $plural_name = 'Permission descriptions';
    private static $table_name = 'SurfSharekit_PermissionDescription';

    private static $extensions = [
        UuidExtension::class,
        UuidRelationExtension::class
    ];

    private static $db = [
        "Title" => "Varchar(255)",
        "TextNL" => "Varchar(255)",
        "TextEN" => "Varchar(255)",
        "PermissionCode" => "Varchar(255)",
        "SortOrder" => "Int"
    ];

    private static $has_one = [
        "PermissionCategory" => PermissionCategory::class,
    ];

    private static $required_fields = [
        "Title",
        "PermissionCode",
        "PermissionCategoryID"
    ];

    function getCMSValidator(): RequiredFields {
        return new RequiredFields($this::$required_fields);
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeByName("SortOrder");

        $permissionCodeDropdownfield = new DropdownField("PermissionCode", "Permission Code", PermissionRoleCode::get()->map("Code", "Code"));
        $fields->replaceField("PermissionCode", $permissionCodeDropdownfield);

        $fields->dataFieldByName("TextNL")->setDescription("Place *** before and after a piece of text to highlight it in the front-end: 'This is an ***example*** text'");
        $fields->dataFieldByName("TextEN")->setDescription("Place *** before and after a piece of text to highlight it in the front-end: 'This is an ***example*** text'");

        return $fields;
    }

}