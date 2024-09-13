<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * @method DataList TemplateSections
 */
class TemplateStep extends DataObject {

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $table_name = 'SurfSharekit_TemplateStep';
    private static $default_sort = 'SortOrder ASC';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Title_EN' => 'Varchar(255)',
        'Title_NL' => 'Varchar(255)',
        'Subtitle_EN' => 'Varchar(255)',
        'Subtitle_NL' => 'Varchar(255)',
        'SortOrder' => 'Int(0)'
    ];

    /**
     * @var DataList
     */
    private static $has_many = [
        'TemplateSections' => TemplateSection::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Title_NL' => 'Title_NL',
        'Title_EN' => 'Title_EN'
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();
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