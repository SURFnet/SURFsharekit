<?php

namespace SurfSharekit\Models;

use SilverStripe\Assets\Image;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\DataObject;

class MetaFieldOptionCategory extends DataObject {

    private static $table_name = 'SurfSharekit_MetaFieldOptionCategory';

    private static $db = [
        "Title" => "Varchar(255)",
        "Sort" => "Int"
    ];

    private static $has_one = [
        'MetaField' => MetaField::class
    ];

    private static $has_many = [
        'MetaFieldOptions' => MetaFieldOption::class
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeByName('MetaFieldID');
        $fields->dataFieldByName('Sort')->setDisabled(true);
        $fields->removeByName('MetaFieldOptions');
        return $fields;
    }

    public function canCreate($member = null, $context = null) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }
}