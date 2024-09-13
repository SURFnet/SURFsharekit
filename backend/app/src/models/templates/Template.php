<?php

namespace SurfSharekit\Models;

use DataObjectJsonApiEncoder;
use Exception;
use RelationaryPermissionProviderTrait;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Helper\Logger;
use Symbiote\GridFieldExtensions\WritingGridFieldOrdereableRows;

/**
 * Class Template
 * @package SurfSharekit\Models
 * @method Institute Institute()
 * @method HasManyList TemplateMetaFields();
 * DataObject representing a subset of @see MetaField objects for a given RepoType and Institute
 * This object is used to create a @see RepoItem with the same RepoType
 */
class Template extends DataObject implements PermissionProvider {
    const RELATION_TEMPLATE = 'Template';

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    use RelationaryPermissionProviderTrait;

    private static $cascade_deletes = [
        'TemplateMetaFields'
    ];

    private static $table_name = 'SurfSharekit_Template';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Label_NL' => 'Varchar(255)',
        'Label_EN' => 'Varchar(255)',
        'Description' => 'Text',
        'RepoType' => 'Enum(array("PublicationRecord", "LearningObject", "ResearchObject", "Dataset", "Project", "RepoItemRepoItemFile", "RepoItemLearningObject", "RepoItemLink", "RepoItemPerson", "RepoItemResearchObject"))',
        'AllowCustomization' => 'Int(0)'
    ];

    private static $has_one = [
        'Institute' => Institute::class
    ];

    private static $has_many = [
        'TemplateMetaFields' => TemplateMetaField::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Institute.Title' => 'Institute',
        'RepoType' => 'Type',
        'Description' => 'Description'
    ];

    private static $searchable_fields = [
        'Title',
        'Institute.Title',
        'RepoType',
        'Description',
        'AllowCustomization'
    ];

    private static $field_labels = [
        'Institute.Title' => 'Institute',
        'RepoType' => 'Type'
    ];

    private static $defaultTemplateUuids = [
        'PublicationRecord' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282',
        'RepoItemRepoItemFile' => 'c5ea3076-d11b-4611-a7af-c424cc0dcf15',
        'LearningObject' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848',
        'Dataset' => '85126a1f-072f-4bdb-bb75-d83da2f744db',
        'Project' => '69ee56b9-b093-49af-87f8-2bf963585d98',
        'ResearchObject' => '93311c51-8d31-4595-b64e-0b15ce50798a',
        'RepoItemPerson' => 'b08fa914-8ac8-4bc5-8708-a96b26858819',
        'RepoItemLearningObject' => 'b3521815-26d2-4cf5-a7d4-6f9aa8b74187',
        'RepoItemLink' => '94661347-0626-43ed-afe1-c3d9aaee0e0f',
        'RepoItemResearchObject' => 'f0152c72-d86b-4eac-a6b0-804d27dbe1cd'
    ];

    private static $indexes = [
        'RepoType' => true
    ];

    private $parentIDMap = [];

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->dataFieldByName('Title')->setReadonly(true);

        $allowCustomizationField = CheckboxField::create('AllowCustomization', 'AllowCustomization', $this->AllowCustomization);
        $allowCustomizationField->setDescription('When allow customization is set, this template could be customized by site-admins.');
        $fields->replaceField('AllowCustomization', $allowCustomizationField);

        $repoTypeField = $fields->dataFieldByName('RepoType')->performReadonlyTransformation();
        $fields->replaceField('RepoType', $repoTypeField);

        /** @var Institute $institute */
        $institute = $this->Institute();
        if ($institute && $institute->exists()) {
            $instituteName = $institute->Title;
        } else {
            $instituteName = '- default template -';
            // remove Allow Customization field, as this is the default template
            $fields->removeByName('AllowCustomization');
        }
        $instituteDisplayField = ReadonlyField::create('DisplayInstitute', 'Institute', $instituteName);
        $instituteHiddenField = HiddenField::create('InstituteID', 'Institute', $this->InstituteID);
        $fields->replaceField('InstituteID', $instituteHiddenField);
        $fields->insertBefore('Title', $instituteDisplayField);

        if ($this->isInDB()) {
            /** @var GridField $templateMetaFieldsGridField */
            $templateMetaFieldsGridField = $fields->dataFieldByName('TemplateMetaFields');
            $templateMetaFieldsGridFieldConfig = $templateMetaFieldsGridField->getConfig();
            $templateMetaFieldsGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldDeleteAction(), new GridFieldAddNewButton(), new GridFieldPaginator()]);
            if ($this->InstituteID == 0) {
                $writableSortComponent = new WritingGridFieldOrdereableRows('SortOrder');
                $writableSortComponent->callback = function () {
                    $this->onAfterWrite();
                };
                $templateMetaFieldsGridFieldConfig->addComponent($writableSortComponent);
                $templateMetaFieldsGridFieldConfig->addComponents([new GridFieldDeleteAction(), new GridFieldAddNewButton()]);
            }
            $paginator = new GridFieldPaginator(200);

            $templateMetaFieldsGridFieldConfig->addComponent($paginator);
        }
        return $fields;
    }

    public function setFieldsFromApi($templateMetaFields) {
        if (!is_array($templateMetaFields)) {
            throw new Exception('Attribute fields must contain an array');
        }
        $requiredTemplateMetaFields = $this->TemplateMetaFields()->filter(['IsRemoved' => 0]);
        $requiredTemplateMetaFieldIds = $requiredTemplateMetaFields->column('MetaFieldUuid');

        $getValue = function ($json, $field) {
            if (!key_exists($field, $json)) {
                throw new Exception("Missing field '$field' for field " . ($field === 'key' ? '' : $json['key']));
            }
            return $json[$field];
        };

        foreach ($templateMetaFields as $templateMetaFieldJson) {
            $metaFieldUuid = $getValue($templateMetaFieldJson, 'key');
            if (!in_array($metaFieldUuid, $requiredTemplateMetaFieldIds)) {
                throw new Exception("Field $metaFieldUuid is not a real field");
            }
            $templateMetaField = $this->TemplateMetaFields()->filter(['IsRemoved' => 0, 'MetaFieldUuid' => $metaFieldUuid])->first();

            if ($templateMetaField->IsLocked) {
                throw new Exception("Field $metaFieldUuid is locked, cannot be patched");
            }

            $fieldsToPatch = [
                "titleEN" => 'Label_EN',
                "titleNL" => 'Label_NL',
                "infoTextEN" => 'InfoText_EN',
                "infoTextNL" => 'InfoText_NL',
                "descriptionEN" => 'Description_EN',
                "descriptionNL" => 'Description_NL',
                "enabled" => 'IsEnabled',
                "required" => 'IsRequired'
            ];

            foreach ($fieldsToPatch as $attr => $field) {
                $templateMetaField->$field = $getValue($templateMetaFieldJson, $attr);
                unset($fieldsToPatch[$attr]);
                unset($templateMetaFieldJson[$attr]);
            }

            unset($templateMetaFieldJson['key']);
            if (count($fieldsToPatch)) {
                throw new Exception("Field $metaFieldUuid has missing atttributes: [" . implode(',', array_keys($fieldsToPatch)) . "]");
            }

            if (count($templateMetaFieldJson)) {
                throw new Exception("Field $metaFieldUuid has too many atttributes: [" . implode(',', array_keys($templateMetaFieldJson)) . "]");
            }

            $templateMetaField->write();
        }
    }

    public function providePermissions() {
        $allActionsOnExistingObject = ['VIEW', 'EDIT'];

        return $this->provideRelationaryPermissions(Template::RELATION_TEMPLATE, 'templates', $allActionsOnExistingObject);
    }

    public function isTemplate() {
        return true;
    }

    public function canView($member = null, $context = []) {
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

        return ScopeCache::isViewableFromCache($this);
    }

    public function canEdit($member = null, $context = []) {
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
        foreach ($member->Groups() as $group) {
            if ($this->checkRelationPermission(Template::RELATION_TEMPLATE, 'EDIT', $member, [Group::class => $group])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $member
     * @return bool if the object is part of a scope below that of $member
     */
    public function isLowerlevel($member, $context) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            return InstituteScoper::getScopeLevel($group->InstituteID, $this->InstituteID) == InstituteScoper::LOWER_LEVEL;
        }
        return false;
    }

    /**
     * @param $member
     * @return bool if the object is part of the same scope of that of $member
     */
    public function isSameLevel($member, $context) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            return InstituteScoper::getScopeLevel($group->InstituteID, $this->InstituteID) == InstituteScoper::SAME_LEVEL;
        }
        return false;
    }

    /**
     * @return array all repotype enum possibilties
     */
    public static function getRepoTypes() {
        $fieldsOfClass = DataObjectSchema::create()->databaseFields(Template::class);
        $sqlFieldType = $fieldsOfClass['RepoType'];
        $sqlFieldType = substr($sqlFieldType, 11, strlen($sqlFieldType) - 13);
        $legitEnumValues = explode(', ', $sqlFieldType);
        foreach ($legitEnumValues as $index => $value) {
            $legitEnumValues[$index] = substr($value, 1, strlen($value) - 2); //remove prefixed and suffixed "
        }
        return $legitEnumValues;
    }

    protected function onAfterWrite() {
        parent::onAfterWrite();

        if (!$this->isChanged('ID')) {//if not recently created
            //  $this->downPropagateTemplateMetaFields();
        }

        if ($this->isChanged('InstituteID')) {
            ScopeCache::removeCachedViewable(Template::class);
            ScopeCache::removeCachedDataList(Template::class);
        }

        if ($this->isChanged('Label_NL')){
            $this->Title = $this->Label_NL;
        }

        $this->removeCacheWhereNeeded();
    }

    public function downPropagateTemplateMetaFields($limitToTemplate = null) {
        if (!is_null($limitToTemplate)) {
            $templateTitle = $limitToTemplate->Title;
        } else {
            $templateTitle = 'null';
        }
        Logger::debugLog("downPropagateTemplateMetaFields " . $this->Title . ' limitToTemplate = ' . $templateTitle);
        /** @var TemplateMetaField $templateMetaField */
        foreach ($this->TemplateMetaFields() as $templateMetaField) {
            $templateMetaField->downPropagateFast($limitToTemplate);
        }
    }

    /***
     * @return DataObject|null
     * @throws Exception
     * Returns the template of institute of higher order
     */
    public function getParent() {
        // return if this is already the default template;
        if ($this->InstituteID == 0) {
            return null;
        }
        if (isset($this->parentIDMap["$this->ID"])) {
            $parent = $this->parentIDMap["$this->ID"];
            if ($parent && $parent->exists()) {
                return $parent;
            }
        }

        $parentialInstitute = $this->Institute()->Institute();
        if ($parentialInstitute && $parentialInstitute->Exists()) {
            $parentialTemplate = $parentialInstitute->Templates()->filter(['RepoType' => $this->RepoType])->first();
            if (!$parentialTemplate) {
                throw new Exception('Missing ' . $this->RepoType . ' template for institute ' . $parentialInstitute->Title);
            }
            $this->parentIDMap["$this->ID"] = $parentialTemplate;
            return $parentialTemplate;
        } else {
            $defaultTemplate = Template::get()->filter(['RepoType' => $this->RepoType, 'InstituteID' => 0])->first();
            if (!($defaultTemplate && $defaultTemplate->exists())) {
                return null;
            }
            $this->parentIDMap["$this->ID"] = $defaultTemplate;
            return $defaultTemplate;
        }
        return null;
    }

    function getInstituteTitle() {
        return $this->Institute()->Title;
    }

    function getInstituteLevel() {
        return $this->Institute()->Level;
    }

    function getLoggedInUserPermissions() {
        $loggedInMember = Security::getCurrentUser();
        return [
            'canEdit' => $this->canEdit($loggedInMember)
        ];
    }

    function getStepsForJsonApi($contextualRepoItem = null) {
        $templateSteps = [];

        foreach (TemplateStep::get() as $templateStep) {
            $templateSections = [];

            foreach (TemplateSection::get() as $templateSection) {
                $fieldsInSection = [];
                foreach ($this->TemplateMetaFields()->filter(['IsRemoved' => 0, 'TemplateSectionID' => $templateSection->ID]) as $templateMetaField) {
                    $fieldsInSection[] = $templateMetaField->getJsonAPIDescription($contextualRepoItem);
                }
                if (count($fieldsInSection)) {
                    $templateSections[] = [
                        'sortOrder' => $templateSection->SortOrde,
                        'titleNL' => $templateSection->Title_NL,
                        'titleEN' => $templateSection->Title_EN,
                        'id' => DataObjectJsonApiEncoder::getJSONAPIID($templateSection),
                        'subtitleNL' => $templateSection->Subtitle_NL,
                        'subtitleEN' => $templateSection->Subtitle_EN,
                        'iconKey' => $templateSection->IconKey,
                        'isUsedForSelection' => $templateSection->IsUsedForSelection,
                        'icon' => $templateSection->Icon,
                        'fields' => $fieldsInSection
                    ];
                }
            }
            $templateSteps[] = [
                'id' => $templateStep->Uuid,
                'titleNL' => $templateStep->Title_NL,
                'titleEN' => $templateStep->Title_EN,
                'subtitleNL' => $templateStep->Subtitle_NL,
                'subtitleEN' => $templateStep->Subtitle_EN,
                'sortOrder' => $templateStep->SortOrder,
                'templateSections' => $templateSections
            ];
        }

        return $templateSteps;
    }

    function requireDefaultRecords() {
        parent::requireDefaultRecords();
        $repoTypes = self::getRepoTypes();
        // check for default template records
        foreach ($repoTypes as $repoType) {
            $defaultTemplateOfType = Template::get()->filter(['RepoType' => $repoType, 'InstituteID' => 0])->first();

            if (is_null($defaultTemplateOfType)) {
                $defaultTemplateOfType = Template::create();
                $defaultTemplateOfType->InstituteID = 0; // default template
                $defaultTemplateOfType->RepoType = $repoType;
                $defaultTemplateOfType->Title = "$repoType Template (Default)";
                $defaultTemplateOfType->Description = "Automatisch gegenereerd standaard template";

                // set uuid for default template for reference in defaul template metafields
                if (array_key_exists($repoType, self::$defaultTemplateUuids)) {
                    $defaultTemplateOfType->Uuid = self::$defaultTemplateUuids[$repoType];
                }

                $defaultTemplateOfType->write();
            }
        }
    }

    static function getPermissionCases() {
        return [
            'TEMPLATE_VIEW_TEMPLATE' => "1",
        ];
    }

    public function removeCacheWhereNeeded() {
        foreach ($this->TemplateMetaFields() as $templateMetaField) {
            $templateMetaField->removeCacheWhereNeeded();
        }
    }

    public function getSteps($contextualRepoItem = null) {
        $templateSteps = [];

        foreach (TemplateStep::get() as $templateStep) {
            $templateSections = [];
            foreach ($templateStep->TemplateSections() as $templateSection) {

                $fieldsInSection = [];
                foreach ($this->TemplateMetaFields()->filter(['IsRemoved' => 0, 'IsEnabled' => 1, 'TemplateSectionID' => $templateSection->ID]) as $templateMetaField) {
                    $fieldsInSection[] = $templateMetaField->getJsonAPIDescription($contextualRepoItem);
                }
                if (count($fieldsInSection)) {
                    $templateSections[] = [
                        'sortOrder' => $templateSection->SortOrder,
                        'titleNL' => $templateSection->Title_NL,
                        'titleEN' => $templateSection->Title_EN,
                        'id' => DataObjectJsonApiEncoder::getJSONAPIID($templateSection),
                        'subtitleNL' => $templateSection->Subtitle_NL,
                        'subtitleEN' => $templateSection->Subtitle_EN,
                        'iconKey' => $templateSection->IconKey,
                        'isUsedForSelection' => $templateSection->IsUsedForSelection,
                        'icon' => $templateSection->Icon,
                        'fields' => $fieldsInSection
                    ];
                }
            }
            $templateSteps[] = [
                'id' => $templateStep->Uuid,
                'titleNL' => $templateStep->Title_NL,
                'titleEN' => $templateStep->Title_EN,
                'subtitleNL' => $templateStep->Subtitle_NL,
                'subtitleEN' => $templateStep->Subtitle_EN,
                'sortOrder' => $templateStep->SortOrder,
                'templateSections' => $templateSections
            ];
        }

        return $templateSteps;
    }
}