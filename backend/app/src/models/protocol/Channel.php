<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\GridFieldArchiveAction;
use SilverStripe\Versioned\Versioned;

/**
 * Class Channel
 * @package SurfSharekit\Models
 * @method HasManyList ChannelFilters
 * @method ManyManyList Members
 * DataObject representing a channel the external api can open
 */
class Channel extends DataObject {
    private static $table_name = 'SurfSharekit_Channel';

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $db = [
        'Title' => 'Varchar(255)',
        'Description' => 'Varchar(255)',
        'Slug' => 'Varchar(255)',
        'SkipAPIKeyValidation' => 'Int(0)'
    ];

    private static $field_labels = [

    ];

    private static $has_one = [
        'Protocol' => Protocol::class
    ];

    private static $has_many = [
        'ChannelFilters' => ChannelFilter::class
    ];

    private static $many_many = [
        'Members' => Member::class,
        'Institutes' => Institute::class
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        if ($this->isInDB()) {
            /** @var GridField $membersGridField */
            $membersGridField = $fields->dataFieldByName('Members');
            $membersGridFieldConfig = $membersGridField->getConfig();
            $membersGridFieldConfig->removeComponentsByType([new GridFieldAddNewButton(), new GridFieldArchiveAction()]);

            /** @var GridField $institutesGridField */
            $institutesGridField = $fields->dataFieldByName('Institutes');
            $institutesGridFieldConfig = $institutesGridField->getConfig();
            $institutesGridFieldConfig->removeComponentsByType([new GridFieldAddNewButton(), new GridFieldArchiveAction()]);

            $skipAPIKeyValidationField = CheckboxField::create('SkipAPIKeyValidation', 'SkipAPIKeyValidation', $this->SkipAPIKeyValidation);
            $skipAPIKeyValidationField->setDescription('When set, an API key is not necessary and the first Member is used.');
            $fields->replaceField('SkipAPIKeyValidation', $skipAPIKeyValidationField);

        }
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