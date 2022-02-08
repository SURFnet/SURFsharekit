<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Class MetaFieldOption
 * @package SurfSharekit\Models
 * DataObject representing a single option on a (@see MetaField)
 */
class MetaFieldOption extends DataObject {

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $table_name = 'SurfSharekit_MetaFieldOption';

    private static $db = [
        'Value' => 'Varchar(255)',
        'IsRemoved' => 'Boolean(0)',
        'Label_EN' => 'Varchar(255)',
        'Label_NL' => 'Varchar(255)',
        'Description_EN' => 'Text',
        'Description_NL' => 'Text',
    ];

    private static $has_one = [
        'MetaField' => MetaField::class,
        'MetaFieldOption' => MetaFieldOption::class
    ];

    private static $has_many = [
        'MetaFieldOptions' => MetaFieldOption::class
    ];

    private static $indexes = [
        'FulltextSearchOption' => [
            'type' => 'fulltext',
            'columns' => ['Label_EN', 'Label_NL']
        ]
    ];

    private static $summary_fields = [
        'Value' => 'Value',
        'Label_NL' => 'Label_NL',
        'Label_EN' => 'Label_EN'
    ];

    public function getTitle() {
        return $this->Value;
    }

    public function getCoalescedLabel_EN() {
        return $this->MetaFieldOptionID ? $this->MetaFieldOption()->CoalescedLabel_EN . ' - ' . $this->Label_EN : $this->Label_EN;
    }

    public function getCoalescedLabel_NL() {
        return $this->MetaFieldOptionID ? $this->MetaFieldOption()->CoalescedLabel_NL . ' - ' . $this->Label_NL : $this->Label_NL;
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

    public function getFieldKey() {
        return $this->MetaField()->Uuid;
    }

    protected function onAfterWrite() {
        parent::onAfterWrite();
        $this->updateRelevantRepoItems();
    }

    /**
     * Update the attributes of repoItems that make use of this object as an attribute via the attributeKey system
     */
    private function updateRelevantRepoItems() {
        //implied not the first time writing this object
        if (!$this->isChanged('ID') && $this->isChanged('Value')) {
            RepoItem::updateAttributeBasedOnMetafield($this->Value, "MetaFieldOptionID = $this->ID");
        }
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $parentField = new DropdownField('MetaFieldOptionID', 'Parent option');
        $parentField->setSource(MetaFieldOption::get()->filter('MetaFieldID', $this->MetaFieldID)->map('ID', 'Label_NL'));
        $parentField->setEmptyString('Select a parent option if applicable');
        $parentField->setHasEmptyDefault(true);
        $fields->removeByName('MetaFieldOptionID');
        $fields->addFieldToTab('Root.Main', $parentField);
        return $fields;
    }
}