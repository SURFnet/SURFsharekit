<?php

namespace SurfSharekit\Models;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
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
        'Label_EN' => 'Varchar(1024)',
        'Label_NL' => 'Varchar(1024)',
        'Description_EN' => 'Text',
        'Description_NL' => 'Text',
        'SortOrder' => 'Int(0)',
        'Icon' => 'Enum(array(null,
                "OpenAccess", 
                "RestrictedAccess", 
                "ClosedAccess",
                "CC-BY-0", 
                "CC-BY", 
                "CC-BY-SA", 
                "CC-BY-NC", 
                "CC-BY-NC-SA", 
                "CC-BY-ND", 
                "CC-BY-NC-ND", 
                "PublicDomain", 
                "AllRights",
                "VideoAndSound"     
                "YouTube"
        ))',
        'MetaFieldOptionSourceUrl' => 'Varchar(1024)'
    ];

    private static $has_one = [
        'MetaField' => MetaField::class,
        'MetaFieldOptionCategory' => MetaFieldOptionCategory::class,
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

    public function getHasChildren(){
        return $this->MetaFieldOptions()->count() > 0;
    }

    public function getCoalescedLabel_EN() {
        return $this->MetaFieldOptionID ? $this->MetaFieldOption()->CoalescedLabel_EN . ' - ' . $this->Label_EN : $this->Label_EN;
    }

    public function getCoalescedLabel_NL() {
        return $this->MetaFieldOptionID ? $this->MetaFieldOption()->CoalescedLabel_NL . ' - ' . $this->Label_NL : $this->Label_NL;
    }

    public function getRootNode()
    {
        if ($this->MetaFieldOptionID === 0 || !$this->MetaFieldOption()->exists()) {
            return $this->Uuid;
        }

        return $this->MetaFieldOption()->getRootNode();
    }

    public function getIdentifier(){
        return $this->Uuid;
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

    protected function onBeforeWrite() {
        if (!$this->isInDB() && !$this->SetCustomSortOrder) {
            $maxSortOrder = MetaFieldOption::get()->filter('MetaFieldID', $this->MetaFieldID)->max('SortOrder');
            if ($maxSortOrder) {
                $this->SortOrder = $maxSortOrder + 1;
            }
        }
        parent::onBeforeWrite();
    }

    public function getFieldKey() {
        return $this->MetaField()->Uuid;
    }

    protected function onAfterWrite() {
        parent::onAfterWrite();
        $this->updateRelevantRepoItems();
        $this->MetaField()->removeCacheWhereNeeded();
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
        // Disabled custom fields for now, as it seems they cause out of memory exceptions

//        $parentField = new DropdownField('MetaFieldOptionID', 'Parent option');
//        $parentField->setSource(MetaFieldOption::get()->filter('MetaFieldID', $this->MetaFieldID)->map('ID', 'Label_NL'));
//        $parentField->setEmptyString('Select a parent option if applicable');
//        $parentField->setHasEmptyDefault(true);

        if ($this->isInDB()) {
            $fields->insertBefore("Value", new ReadonlyField("Uuid", "Uuid", $this->Uuid));
        }

        if (strtolower($this->Metafield()->MetaFieldType()->Key) == 'dropdown') {
            $fields->addFieldToTab(
                'Root.Main',
                DropdownField::create('Icon', 'Icon')
                    ->setEmptyString('- Select -')
                    ->setSource([
                        'OpenAccess' => 'Toegankelijk voor iedereen',
                        'RestrictedAccess' => 'Beperkt toegankelijk',
                        'ClosedAccess' => 'Niet toegankelijk',
                    ])
            );
        } else if (strtolower($this->Metafield()->MetaFieldType()->Key) == 'rightofusedropdown') {
            $fields->addFieldToTab(
                'Root.Main',
                DropdownField::create('Icon', 'Icon')
                    ->setEmptyString('- Select -')
                    ->setSource([
                        "CC-BY-0" => "CC-BY-0",
                        "CC-BY" => "CC-BY",
                        "CC-BY-SA" => "CC-BY-SA",
                        "CC-BY-NC" => "CC-BY-NC",
                        "CC-BY-NC-SA" => "CC-BY-NC-SA",
                        "CC-BY-ND" => "CC-BY-ND",
                        "CC-BY-NC-ND" => "CC-BY-NC-ND",
                        "PublicDomain" => "PublicDomain",
                        "AllRights" => "AllRights",
                        "VideoAndSound" => "VideoAndSound",
                        "YouTube" => "YouTube"
                    ])
            );
        }



        // Check if Metafield type is Dropdown
        if ($this->MetaField() && !in_array(strtolower($this->MetaField()->MetaFieldType()->Key), ['dropdown', 'rightofusedropdown'])) {
            $fields->removeByName('Icon');
        }

        $fields->removeByName('MetaFieldOptionID');
        $fields->removeByName('SortOrder');
//        $fields = MetaField::ensureDropdownField($this, $fields);
//        $fields->addFieldToTab('Root.Main', $parentField);
        return $fields;
    }
}