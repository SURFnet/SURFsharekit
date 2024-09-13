<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SurfSharekit\constants\RepoItemTypeConstant;
use SurfSharekit\Models\Helper\Constants;

class RepoItemUploadField extends DataObject {
    private static $table_name = 'SurfSharekit_RepoItemUploadField';

    private static $db = [
        'Title' => 'Varchar(255)',
        'IsRequired' => 'Int(0)',
        'AttributeKey' => "Enum('RepoType, InstituteID, OwnerID, FileID',null)",
        "RepoItemType" => "Varchar(255)"
    ];

    private static $has_one = [
        'MetaField' => MetaField::class,
        'RepoItemUploadConfig' => RepoItemUploadConfig::class,
        "RepoItemUploadField" => RepoItemUploadField::class
    ];

    private static $has_many = [
        "RepoItemUploadFields" => RepoItemUploadField::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'MetaField.Title' => 'MetaField'
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $attributeKeyField = $fields->dataFieldByName('AttributeKey');
        $attributeKeyField->setHasEmptyDefault(true);
        $fields = MetaField::ensureDropdownField($this, $fields);

        /** @var DropdownField $dropdownField */
        $dropdownField = new DropdownField("RepoItemType", "RepoItemType", array_combine(RepoItemTypeConstant::SECONDARY_TYPES, RepoItemTypeConstant::SECONDARY_TYPES));
        $dropdownField->setEmptyString("-- Choose a type for the SubRepoItem --");
        $fields->replaceField("RepoItemType", $dropdownField);

        if ($this->isInDB() && !$this->RepoItemType) {
            $fields->removeByName("RepoItemUploadFields");
        }

        $fields->removeByName("RepoItemUploadFieldID");

        return $fields;
    }

    public function canView($member = null) {
        if (parent::canView($member)) {
            return true;
        }

        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }

        return false;
    }

    public function canEdit($member = null) {
        if (parent::canEdit($member)) {
            return true;
        }

        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }

        return false;
    }

    public function canDelete($member = null) {
        if (parent::canDelete($member)) {
            return true;
        }

        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }

        return false;
    }

    public function canCreate($member = null, $context = []) {
        if (parent::canCreate($member)) {
            return true;
        }

        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }

        return false;
    }
}
