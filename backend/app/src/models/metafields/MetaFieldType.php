<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Class MetaFieldType
 * @package SurfSharekit\Models
 * DataObject representing the type of a @see MetaField (e.g. Text, Date)
 * This dataobject is used to validate inserted values and communicate fieldTypes to frontends
 */
class MetaFieldType extends DataObject {

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $table_name = 'SurfSharekit_MetaFieldType';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Key' => 'Varchar(255)',
        'ValidationRegex' => 'Text',
        'OptionGenerationKey' => 'Varchar(255)',
        'JSONEncodedStorage' => 'Boolean(0)'
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Key' => 'Key'
    ];

    private static $field_labels = [
        'ValidationRegex' => 'PHP Regex to validate input values',
        'Key' => 'Type communicated to FrontEnd'
    ];

    public function getCMSValidator() {
        return new RequiredFields([
            'Title', 'Key'
        ]);
    }

    public function getCMSFields() {
        $cmsFields = parent::getCMSFields();
        $optionGenerationKey = $cmsFields->dataFieldByName('OptionGenerationKey');
        $optionGenerationKey->setDescription("Used to automatically generate options for this field based on a system object (e.g. \"SurfSharekit\Models\Institute\" or \"SurfSharekit\Models\RepoItems\").");
        return $cmsFields;
    }

    public function canView($member = null) {
        return true;
    }
}
