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
 * @property string Title
 * @property string Description
 * @property string Slug
 * @property int SkipAPIKeyValidation
 * @property string CallbackUrl
 * @property bool PushEnabled
 * @property bool IsPersonChannel
 * @property int ProtocolID
 * @method Protocol Protocol
 * @method HasManyList ChannelFilters
 * @method ManyManyList Members
 * @method ManyManyList Institutes
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
        'SkipAPIKeyValidation' => 'Int(0)',
        'CallbackUrl' => 'Varchar(255)',
        'PushEnabled' => 'Boolean',
        'IsPersonChannel' => 'Boolean(0)',
        'IsInstituteChannel' => 'Boolean(0)'
    ];

    private static $field_labels = [
        'IsPersonChannel' => 'Channel can return Persons',
        'IsInstituteChannel' => 'Channel can return Institutes'
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

        $fields->dataFieldByName('CallbackUrl')->setDescription('This is the url where RepoItem changes are pushed to on creation, deletion or update');
        $fields->dataFieldByName('PushEnabled')->setDescription('Enabling this functionality makes sure that changes in RepoItems are send to the callback url defined above');
        $fields->dataFieldByName('IsPersonChannel')->setDescription('Persons are ALWAYS returned in JSON format, regardless of the selected protocol. Also, Channel and protocol filters do NOT affect the data that is returned for Persons');
        $fields->dataFieldByName('IsInstituteChannel')->setDescription('Institutes are ALWAYS returned in JSON format, regardless of the selected protocol. Also, Channel and protocol filters do NOT affect the data that is returned for Institutes');
        $fields->dataFieldByName('ProtocolID')->setDescription('After protocol change, cache needs to be rebuild');

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

    public function validate() {
        $results = parent::validate();

        if(!$this->CallbackUrl && $this->PushEnabled) {
            $results->addFieldError('CallbackUrl', 'Make sure to define a callback url the push functionality is enabled');
        }

        return $results;
    }
}