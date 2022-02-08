<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataObject;

/**
 * Class Channel
 * @package SurfSharekit\Models
 * DataObject representing a channel the external api can open
 */
class ChannelFilter extends DataObject {
    private static $table_name = 'SurfSharekit_ChannelFilter';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Enabled' => 'Int(1)',
        'Value' => 'Varchar(255)',
        'RepoItemAttribute' => "Enum('Status,Language,RepoType,IsPublic,IsRemoved,IsArchived',null)"
    ];

    private static $field_labels = [

    ];

    private static $has_one = [
        'Channel' => Channel::class,
        'MetaField' => MetaField::class
    ];

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        /**
         * @var $dropdown DropdownField
         */
        $dropdown = $fields->dataFieldByName('RepoItemAttribute');
        $dropdown->setHasEmptyDefault(true);
        return $fields;
    }
}