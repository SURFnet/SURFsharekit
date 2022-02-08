<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * Class MetaField
 * @package SurfSharekit\Models
 * @method HasManyList MetaFieldOptions
 * @method MetaFieldType MetaFieldType
 * DataObject representing a single field that can be added to a template (@see TemplateMetaField)
 * to be filled in (@see RepoItemMetaField)
 */
class MetaField extends DataObject {

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $table_name = 'SurfSharekit_MetaField';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Label_EN' => 'Varchar(255)',
        'Label_NL' => 'Varchar(255)',
        'IsCopyable' => 'Int(0)',
        'Description_EN' => 'Text',
        'Description_NL' => 'Text',
        'InfoText_EN' => 'Varchar(255)',
        'InfoText_NL' => 'Varchar(255)',
        'DefaultKey' => "Enum('CurrentDate,AuthorInstitute,AuthorDiscipline,TemplateRootInstitute',null)",
        'AttributeKey' => "Enum('Title,Subtitle,PublicationDate,EmbargoDate,InstituteID,Language,Alias,SubType',null)",
        'SummaryKey' => "Varchar(255)",
        'MakesRepoItemFindable' => "Boolean(0)",
        'SystemKey' => "Enum('PublishedNotificationEmail,PrivateChannel,PublicChannel,Archive,ContainsParents,ContainsChildren,Tags',null)",
    ];

    private static $has_one = [
        'MetaFieldType' => MetaFieldType::class
    ];

    private static $has_many = [
        'MetaFieldOptions' => MetaFieldOption::class,
        'TemplateMetaFields' => TemplateMetaField::class,
        'RepoItemMetaFields' => RepoItemMetaField::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Label_NL' => 'Label_NL',
        'Label_EN' => 'Label_EN',
        'MetaFieldType.Title' => 'Type'
    ];

    public function getCMSValidator() {
        return new RequiredFields([
            'Title'
        ]);
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $member = Security::getCurrentUser();
        if (!Permission::checkMember($member, 'ADMIN')) {
            $fields->removeByName('RepoItemMetaFields');
            $fields->removeByName('TemplateMetaFields');
            $fields->removeByName('SummaryKey');
            $fields->removeByName('MakesRepoItemFindable');
        }

        $systemKeyField = $fields->dataFieldByName('SystemKey');
        $systemKeyField->setHasEmptyDefault(true);
        $systemKeyField->setDescription("Used to trigger system specific events, like sending an email when this field gets published with the system key 'PublishedNotificationEmail'.");

        $fields->dataFieldByName('InfoText_EN')->setDescription('Max. 255 tekens');
        $fields->dataFieldByName('InfoText_NL')->setDescription('Max. 255 tekens');

        /** @var DropdownField $metaFieldTypeField */
        $metaFieldTypeField = $fields->dataFieldByName('MetaFieldTypeID');
        $metaFieldTypeField->setSource(MetaFieldType::get()->sort('Title')->map('ID', 'Title'));
        $metaFieldTypeField->setEmptyString('Select a metafield type');
        $metaFieldTypeField->setHasEmptyDefault(false);
        $metaFieldTypeField->setDescription('Changing the field type may cause unexpected results in the existing metafield');

        if ($this->isInDB()) {
            /** @var GridField $metaFieldOptionsGridField */
            $metaFieldOptionsGridField = $fields->dataFieldByName('MetaFieldOptions');
            $metaFieldOptionsGridFieldConfig = $metaFieldOptionsGridField->getConfig();
            $metaFieldOptionsGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldDeleteAction()]);
        }
        /**
         * @var DropdownField $defaultKeyField
         */
        $defaultKeyField = $fields->dataFieldByName('DefaultKey');
        $defaultKeyField->setEmptyString('Select a default key');
        $defaultKeyField->setHasEmptyDefault(true);
        $defaultKeyField->setDescription('Add a variable default option for this field. i.e.:<br/>
        <b>CurrentDate:</b> The current date<br/>
        <b>AuthorInstitute:</b> The current member organisation.<br/>
        <b>AuthorDiscipline:</b> The current discipline.<br/>
        <b>TemplateRootInstitute:</b> The top level organisation.<br/>
        ');

        /** @var DropdownField $attributeKeyField */
        $attributeKeyField = $fields->dataFieldByName('AttributeKey');
        $attributeKeyField->setEmptyString('Select an attribute key');
        $attributeKeyField->setHasEmptyDefault(true);
        return $fields;
    }

    /**
     * @param Member $member
     * @param Template $template
     * @return array
     * Return a non-stored default option for this Metafield based on the DefaultKey
     * e.g. the name of the author of the Repoitem, their Institute, Email or the current date
     */
    function getDefaultValuesFor(Member $member, Template $template): array {
        $defaultValuesArray = [];
        switch ($this->DefaultKey) {
            case 'CurrentDate':
                $defaultFromType = new DefaultMetaFieldOptionPart();
                $defaultFromType->Value = date("Y-m-d");
                $defaultValuesArray[] = $defaultFromType;
                break;
            case 'AuthorEmail':
                $defaultFromType = new DefaultMetaFieldOptionPart();
                $defaultFromType->Value = $member->Email;
                $defaultValuesArray[] = $defaultFromType;
                break;
            case 'AuthorName':
                $defaultFromType = new DefaultMetaFieldOptionPart();
                $defaultFromType->Value = $member->getName();
                $defaultValuesArray[] = $defaultFromType;
                break;
            case 'AuthorInstitute':
                $defaultFromType = new DefaultMetaFieldOptionPart();
                /** @var Institute $institute */
                $institute = $template->Institute();
                $defaultFromType->Value = $institute->ID;
                $defaultFromType->InstituteID = $institute->ID;
                $defaultFromType->InstituteUuid = $institute->Uuid;
                $defaultValuesArray[] = $defaultFromType;
                break;
            case 'AuthorDiscipline':
                $defaultFromType = new DefaultMetaFieldOptionPart();
                /** @var Institute $institute */
                $disciplineGroups = $member->Groups()->filter('Institute.Level', 'Discipline');
                if ($disciplineGroups->count() == 1) {
                    $institute = $disciplineGroups->first()->Institute;
                    $defaultFromType->Value = $institute->ID;
                    $defaultFromType->InstituteID = $institute->ID;
                    $defaultFromType->InstituteUuid = $institute->Uuid;
                    $defaultValuesArray[] = $defaultFromType;
                }
                break;
            case 'TemplateRootInstitute':
                $defaultFromType = new DefaultMetaFieldOptionPart();
                /** @var Institute $institute */
                $institute = $template->Institute();
                $rootInstitute = $institute->getRootInstitute();
                $defaultFromType->InstituteID = $rootInstitute->ID;
                $defaultValuesArray[] = $defaultFromType;
                break;
            default;
        }
        return $defaultValuesArray;
    }

    function isValidMetaFieldValue(RepoItemMetaFieldValue $repoItemMetaFieldValue): bool {
        $metaFieldType = $this->MetaFieldType();

        if (($regexBasedValidation = $metaFieldType->ValidationRegex) && $repoItemMetaFieldValue->Value) {
            $value = $metaFieldType->JSONEncodedStorage ? json_decode($repoItemMetaFieldValue->Value) : $repoItemMetaFieldValue->Value;
            if (!preg_match('/' . $regexBasedValidation . '/', $value)) {
                return false;
            }
        }
        if ($repoItemMetaFieldValue->MetaFieldOptionID) {
            $metaFieldOption = MetaFieldOption::get_by_id($repoItemMetaFieldValue->MetaFieldOptionID);
            if (!$metaFieldOption || !$metaFieldOption->Exists()) {
                return false;
            }
            if ($metaFieldOption->MetaFieldID != $this->ID) {
                return false;
            }
        }
        if ($repoItemMetaFieldValue->RepoItemID) {
            $repoItem = RepoItem::get_by_id($repoItemMetaFieldValue->RepoItemID);
            if (!$repoItem || !$repoItem->Exists()) {
                return false;
            }
            if (in_array(strtolower($metaFieldType->Title), ["PublicationRecord", "LearningObject", "ResearchObject", "RepoItemRepoItemFile", "RepoItemLearningObject", "RepoItemLink", "RepoItemPerson"])) {
                if (strtolower($repoItem->RepoType) != strtolower($metaFieldType->Title)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function canCreate($member = null, $context = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }
}