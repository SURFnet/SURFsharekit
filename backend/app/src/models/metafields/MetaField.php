<?php

namespace SurfSharekit\Models;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SurfSharekit\Action\CustomAction;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Tasks\GetMetafieldOptionsFromJsonTask;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

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
        Versioned::class . '.versioned'
    ];

    private static $table_name = 'SurfSharekit_MetaField';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Label_EN' => 'Varchar(1024)',
        'Label_NL' => 'Varchar(1024)',
        'IsCopyable' => 'Int(0)',
        'Description_EN' => 'Text',
        'Description_NL' => 'Text',
        'InfoText_EN' => 'Varchar(1024)',
        'InfoText_NL' => 'Varchar(1024)',
        'JsonUrl' => 'Varchar(1024)',
        'JsonKey' => 'Varchar(255)',
        'DefaultKey' => "Enum('CurrentDate,AuthorInstitute,AuthorDiscipline,TemplateRootInstitute',null)",
        'AttributeKey' => "Enum('Title,Subtitle,PublicationDate,EmbargoDate,InstituteID,Language,Alias,SubType,AccessRight,External,Important,AllowedForInstitute',null)",
        'SummaryKey' => "Varchar(255)",
        'MakesRepoItemFindable' => "Boolean(0)",
        'RetainOptionOrder' => "Boolean(0)",
        'SystemKey' => "Enum('PublishedNotificationEmail,PrivateChannel,PublicChannel,Archive,ContainsParents,ContainsChildren,Tags,AccessControl',null)",
        'ParentRepoType' => "Enum('PublicationRecord,LearningObject,ResearchObject,Dataset,Project',null)",
        'JsonType' => "Enum('String, StringArray, Object, ObjectArray, Number, NumberArray, Boolean, BooleanArray', null)",
    ];

    private static $has_one = [
        'MetaFieldType' => MetaFieldType::class,
        'MetaFieldJsonExample' => MetaFieldJsonExample::class
    ];

    private static $has_many = [
        'MetaFieldOptions' => MetaFieldOption::class,
        'TemplateMetaFields' => TemplateMetaField::class,
        'RepoItemMetaFields' => RepoItemMetaField::class,
        'MetaFieldOptionCategory' => MetaFieldOptionCategory::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Label_NL' => 'Label_NL',
        'Label_EN' => 'Label_EN',
        "JsonKey" => "JsonKey",
        'MetaFieldType.Title' => 'Type',
    ];

    public static function ensureDropdownField(DataObject $object, FieldList $cmsFields, $fieldName = 'MetaFieldID', $title = 'MetaField', $emptyDefault = false, $emptyString = '') {
        $metaFieldField = $cmsFields->dataFieldByName($fieldName);
        $readOnly = $metaFieldField->isReadonly();
        $metaFieldField = new DropdownField($fieldName, $title);
        $metaFieldField->setValue($object->$fieldName);
        $metaFieldField->setHasEmptyDefault($emptyDefault);
        $metaFieldField->setReadonly($readOnly);
        $metaFieldField->setSource(MetaField::get()->map('ID', 'Title'));
        $metaFieldField->setEmptyString($emptyString);
        $cmsFields->replaceField($fieldName, $metaFieldField);
        return $cmsFields;
    }

    public function getCMSValidator() {
        return new RequiredFields([
            'Title'
        ]);
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        if (!$this->isInDB() || !in_array(strtolower($this->MetaFieldType()->Key), ['dropdown', 'multiselectdropdown', 'tree-multiselect'])) {
            $fields->removeByName('RetainOptionOrder');
        }

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

        $parentRepoTypeField = $fields->dataFieldByName('ParentRepoType');
        $parentRepoTypeField->setHasEmptyDefault(true);

        $fields->dataFieldByName('InfoText_EN')->setDescription('Max. 1024 tekens');
        $fields->dataFieldByName('InfoText_NL')->setDescription('Max. 1024 tekens');

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
            if ($this->RetainOptionOrder) {
                $metaFieldOptionsGridFieldConfig->addComponents([new GridFieldOrderableRows('SortOrder')]);
            }
            $metaFieldOptionsGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldDeleteAction()]);

            // Only show root options
            $metaFieldOptionsGridField = $fields->dataFieldByName("MetaFieldOptions");
            $metaFieldOptionsGridField->setList($this->MetaFieldOptions()->filter(["MetaFieldOptionID" => 0]));

            if ($this->JsonUrl) {
                $generateMetafieldOptionsButton = new CustomAction('doCustomAction', 'Generate metafield options');
                $fields->insertBefore('Title', $generateMetafieldOptionsButton);
            }

            /** @var GridField $metaFieldOptionCategoryGridField */
            $metaFieldOptionCategoryGridField =  $fields->dataFieldByName('MetaFieldOptionCategory');
            $metaFieldOptionCategoryGridFieldConfig = $metaFieldOptionCategoryGridField->getConfig();
            $metaFieldOptionCategoryGridFieldConfig->addComponents([new GridFieldOrderableRows('Sort')]);
            $metaFieldOptionCategoryGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldDeleteAction()]);
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
    function getDefaultValuesFor($member, Template $template): array {
        $defaultValuesArray = [];
        switch ($this->DefaultKey) {
            case 'CurrentDate':
                $defaultFromType = new DefaultMetaFieldOptionPart();
                $defaultFromType->Value = date("Y-m-d");
                $defaultValuesArray[] = $defaultFromType;
                break;
            case 'AuthorEmail':
                if($member) {
                    $defaultFromType = new DefaultMetaFieldOptionPart();
                    $defaultFromType->Value = $member->Email;
                    $defaultValuesArray[] = $defaultFromType;
                }
                break;
            case 'AuthorName':
                if($member) {
                    $defaultFromType = new DefaultMetaFieldOptionPart();
                    $defaultFromType->Value = $member->getName();
                    $defaultValuesArray[] = $defaultFromType;
                }
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
            if (in_array(strtolower($metaFieldType->Title), Constants::ALL_REPOTYPES)) {
                if (strtolower($repoItem->RepoType) != strtolower($metaFieldType->Title)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
        $this->removeCacheWhereNeeded();
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();

        // Validate JsonKey
        if (!$this->isValidJsonKey($this->JsonKey)) {
            throw new ValidationException("Invalid JsonKey. It must be camelCase, contain only alphabetic characters, and start with a lowercase letter.");
        }

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

    public function removeCacheWhereNeeded() {
        SimpleCacheItem::get()
            ->innerJoin('SurfSharekit_TemplateMetaField', 'SurfSharekit_TemplateMetaField.ID = SurfSharekit_SimpleCacheItem.DataObjectID')
            ->filter(['Key' => 'Description', 'DataObjectClass' => "SurfSharekit\\Models\\TemplateMetaField"])
            ->where(['SurfSharekit_TemplateMetaField.MetaFieldID' => $this->ID])->removeAll();
    }

    private function isValidJsonKey($jsonKey): bool {
        // Check if the string is empty
        if (empty($jsonKey)){
            return false;
        }

        // Check if the string consists only of a-z and A-Z characters
        if (!preg_match('/^[a-zA-Z]+$/', $jsonKey)) {
            return false;
        }

        // Check if the first character is lowercased
        if ($jsonKey[0] !== strtolower($jsonKey[0])) {
            return false;
        }

        // If all of these conditions have met, it's a correct JsonKey
        return true;
    }
}