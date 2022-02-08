<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Class TemplateSection
 * @package SurfSharekit\Models
 * DataObject representing a subset of @see TemplateMetaField
 */
class TemplateSection extends DataObject {

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $table_name = 'SurfSharekit_TemplateSection';
    private static $default_sort = 'SortOrder ASC';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Title_EN' => 'Varchar(255)',
        'Title_NL' => 'Varchar(255)',
        'Subtitle_EN' => 'Varchar(255)',
        'Subtitle_NL' => 'Varchar(255)',
        'SortOrder' => 'Int(0)',
        'IconKey' => 'Varchar(255)',
        'IsUsedForSelection' => 'Boolean(0)'
    ];

    private static $has_many = [
        'TemplateMetaFields' => TemplateMetaField::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Title_NL' => 'Title_NL',
        'Title_EN' => 'Title_EN'
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->removeByName('TemplateMetaFields');

        $isUsedForSectionField = $fields->dataFieldByName('IsUsedForSelection');
        $isUsedForSectionField->setDescription("Frontend behaviour when enabled: section progress is set to 100% immediately when one or more items are filled in or enabled.");

        return $fields;
    }

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }


}