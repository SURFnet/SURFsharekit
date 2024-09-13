<?php

namespace SurfSharekit\Models;

use DataObjectJsonApiEncoder;
use Exception;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Helper\Logger;

/**
 * Class TemplateMetaField
 * @package SurfSharekit\Models
 * A DataObject representing where, which and how a given @see MetaField should be added to a given Template
 * @property Int SortOrder Sort order of field in form, always inherited property, so overwritten by any change of parent
 * @property Int IsRemoved If removed, field will be removed from all forms and child forms, always inherited property, so overwritten by any change of parent
 * @property Int IsRequired If enabled, this field cannot be left empty in the form
 * @property Int IsSmallField If enabled, frontend might show small field, always inherited property, so overwritten by any change of parent
 * @property Int IsLocked If locked, siteadmins cannot change the properties of this field
 * @property Int IsEnabled If enabled, this field will be shown in the form
 * @property Int IsReadOnly If enabled, this field will be readonly for everyone and the default value(s) will be stored
 * @property Int IsHidden If enabled, this field will be hidden for everyone, but can used to store default metadata value(s)
 * @property Int IsCopyable If enabled, this field can be copied
 * @property String Label_EN English label, overrides parent label and/or metafield label
 * @property String Label_NL Dutch label, overrides parent label and/or metafield label
 * @property String Description_EN English description, overrides parent description and/or metafield description
 * @property String Description_NL Dutch description, overrides parent description and/or metafield description
 * @property String InfoText_EN English info text, overrides parent label and/or metafield info text
 * @property String InfoText_NL Dutch info text, overrides parent description and/or metafield info text
 * Relationships
 * @method Template Template()
 */
class TemplateMetaField extends DataObject {

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $table_name = 'SurfSharekit_TemplateMetaField';
    private static $default_sort = 'SortOrder ASC';

    private static $db = [
        'SortOrder' => 'Int(0)',
        'IsRemoved' => 'Int(0)',
        'IsRequired' => 'Int(0)',
        'IsSmallField' => 'Int(0)',
        'IsLocked' => 'Int(0)',
        'IsEnabled' => 'Int(1)',
        'IsReadOnly' => 'Int(0)',
        'IsHidden' => 'Int(0)',
        'IsCopyable' => 'Int(0)',
        'IsReplicatable' => 'Int(0)',
        'Label_EN' => 'Varchar(1024)',
        'Label_NL' => 'Varchar(1024)',
        'Description_EN' => 'Text',
        'Description_NL' => 'Text',
        'InfoText_EN' => 'Varchar(1024)',
        'InfoText_NL' => 'Varchar(1024)'
    ];

    private static $has_one = [
        'Template' => Template::class,
        'MetaField' => MetaField::class,
        'TemplateSection' => TemplateSection::class
    ];

    private static $has_many = [
        'DefaultMetaFieldOptionParts' => DefaultMetaFieldOptionPart::class
    ];

    private static $cascade_deletes = [
        'DefaultMetaFieldOptionParts'
    ];

    private static $indexes = [
        'UniqueMetaField' => [
            'type' => 'unique',
            'columns' => ['TemplateID', 'MetaFieldID']
        ],
        'IsRemoved' => true
    ];

    private static $summary_fields = ['MetaField.Title', 'Label_NL', 'Label_EN', 'TemplateSection.Title'];

    private static $field_labels = [
        'TemplateSection.Title' => 'Section'
    ];

    static $inheritableProperties = [
        "Label_EN" => "Label_EN",
        "Label_NL" => "Label_NL",
        "Description_EN" => "Description_EN",
        "Description_NL" => "Description_NL",
        "InfoText_EN" => "InfoText_EN",
        "InfoText_NL" => "InfoText_NL",
        "IsRequired" => "IsRequired",
        "IsEnabled" => "IsEnabled",
        'IsReadOnly' => 'IsReadOnly',
        'IsHidden' => 'IsHidden'
    ];

    static $alwaysInheritedProperties = ["SortOrder", "TemplateSectionID", "IsRemoved", "IsSmallField", "IsLocked", "IsCopyable", "IsReplicatable"];

    public function getCMSValidator() {
        return new RequiredFields([
            'TemplateID', 'MetaFieldID'
        ]);
    }

    public function getTitle() {
        return $this->Label_NL . '(' . $this->Label_EN . ')';
    }

    public function getCMSFields() {
        $cmsFields = parent::getCMSFields();

        $cmsFields->dataFieldByName('InfoText_EN')->setDescription('Max. 1024 tekens');
        $cmsFields->dataFieldByName('InfoText_NL')->setDescription('Max. 1024 tekens');

        if ($this->Template()->InstituteID > 0) {
            // if not default template, disable always inherited properties
            foreach (static::$alwaysInheritedProperties as $field) {
                $cmsFields->dataFieldByName($field)->setDisabled(true)->setDescription('Inherited');
            }
            // if not default template, disable inherited properties if locked or not customizable
            if ($this->IsLocked || $this->Template()->AllowCustomization == 0) {
                foreach (static::$inheritableProperties as $field) {
                    $cmsFields->dataFieldByName($field)->setDisabled(true)->setDescription('Inherited');
                }
            }
        }

        // On acceptance, it default to numericfield, which gives an error on ->setSource
        // 3-5-2021 not a problem on live due to the amount of metafields not reaching the silvertripe dropdown limit
        $cmsFields = MetaField::ensureDropdownField($this, $cmsFields);
        $metaFieldField = $cmsFields->dataFieldByName('MetaFieldID');

        /** @var DropdownField $metaFieldField */
        if ($this->isInDB()) {
            $metaFieldField->setDisabled(true);
            if ($this->Template()->InstituteID > 0) {
                $metaFieldField->setDescription('Inherited');
            }
            $metaFieldField->setSource(MetaField::get()->map('ID', 'Title'));
        } else {
            $sourceList = MetaField::get()->filter(['ID:not' => array_merge([0], $this->Template()->TemplateMetaFields()->column('MetaFieldID'))])->map('ID', 'Title');
            $metaFieldField->setSource($sourceList);
        }
        $cmsFields->removeByName('TemplateID');
        if ($this->MetaField()->DefaultKey) { //Options are automatically generated
            $cmsFields->removeByName('DefaultMetaFieldOptionParts');
        }
        $cmsFields->removeByName('IsCopyable');

        $cmsFields->changeFieldOrder(['TemplateSectionID', 'MetaFieldID', 'Label_NL', 'Label_EN', 'Description_NL', 'Description_EN', 'InfoText_NL', 'InfoText_EN', 'IsLocked', 'IsRequired', 'IsEnabled', 'IsReadOnly', 'IsHidden', 'SortOrder', 'IsSmallField', 'IsRemoved']);

        return $cmsFields;
    }

    function canCreate($member = null, $context = []) {
        return $this->Template()->canEdit($member);
    }

    function canView($member = null) {
        return $this->Template()->canView($member);
    }

    function canEdit($member = null) {
        return $this->Template()->canEdit($member);
    }

    /**
     * This method uses SimpleCacheItem to cache fields that do not changed based on the repoitem that retrieves it
     * @param $contextualRepoItem RepoItem to filter options for
     * @return array a JsonApi Description of this TemplateMetaField with its type, and all available preexisting options
     * @throws Exception
     */
    function getJsonAPIDescription($contextualRepoItem = null) {
        if ($cachedItem = SimpleCacheItem::getFor($this, 'Description')) {
            return $cachedItem->Value;
        }
        $options = [];
        $metaFieldType = $this->MetaField()->MetaFieldType();
        if (!$metaFieldType || !$metaFieldType->Exists()) {
            throw new Exception($this->Title . ' has no type');
        }
        $cacheItem = true;

        if (in_array($metaFieldType->Key, ['DropdownTag', 'Tag', 'MultiSelectDropdown', 'Tree-MultiSelect'])) {
            $cacheItem = false;
            if ($contextualRepoItem) {
                $selectedOptions = MetaFieldOption::get()
                    ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'rimfv.MetaFieldOptionID = SurfSharekit_MetaFieldOption.ID', 'rimfv')
                    ->innerJoin('SurfSharekit_RepoItemMetaField', 'rimfv.RepoItemMetaFieldID = rimf.ID', 'rimf')
                    ->where(["rimf.RepoItemID = $contextualRepoItem->ID", 'rimfv.IsRemoved = 0']);

                if ($this->MetaField()->RetainOptionOrder){
                    $selectedOptions = $selectedOptions->sort('SortOrder');
                }

                foreach ($selectedOptions as $option) {
                    $options[] = [
                        "key" => DataObjectJsonApiEncoder::getJSONAPIID($option),
                        "value" => $option->Value,
                        "labelNL" => $option->Label_NL,
                        "labelEN" => $option->Label_EN,
                        "coalescedLabelEN" => $option->CoalescedLabel_EN,
                        "coalescedLabelNL" => $option->CoalescedLabel_NL,
                        "isRemoved" => $option->IsRemoved,
                        "description" => $option->Description,
                        "icon" => $option->Icon,
                        "metafieldOptionCategory" => $option->MetaFieldOptionCategory()->exists() ? $option->MetaFieldOptionCategory()->Title : null
                    ];
                }
            }
        } else {
            $optionList = $this->MetaField()->MetaFieldOptions();
            if ($this->MetaField()->RetainOptionOrder){
                $optionList = $optionList->sort('SortOrder');
            }

            // Custom sorting function based on MetaFieldOptionCategory->Sort value
            $optionList = $optionList->sort(function ($a, $b) {
                $categoryA = $a->MetaFieldOptionCategory();
                $categoryB = $b->MetaFieldOptionCategory();

                if ($categoryA->Sort == $categoryB->Sort) {
                    return 0;
                }

                return ($categoryA->Sort < $categoryB->Sort) ? -1 : 1;
            });

            // Now loop through the sorted $optionList
            foreach ($optionList as $option) {
                $cacheItem = false;

                if ($option->IsRemoved) {
                    if ($contextualRepoItem) {
                        $repoItemValueWhichUsesOption = $contextualRepoItem->RepoItemMetaFields()
                            ->filter(['RepoItemMetaFieldValues.MetaFieldOptionID' => $option->ID])
                            ->filter('IsRemoved', 0)->first();
                        if (!($repoItemValueWhichUsesOption && $repoItemValueWhichUsesOption->exists())) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }

                $options[] = [
                    "key" => DataObjectJsonApiEncoder::getJSONAPIID($option),
                    "value" => $option->Value,
                    "labelNL" => $option->Label_NL,
                    "labelEN" => $option->Label_EN,
                    "coalescedLabelEN" => $option->CoalescedLabel_EN,
                    "coalescedLabelNL" => $option->CoalescedLabel_NL,
                    "isRemoved" => $option->IsRemoved,
                    "description" => $option->Description,
                    "icon" => $option->Icon,
                    "metafieldOptionCategory" => $option->MetaFieldOptionCategory()->exists() ? $option->MetaFieldOptionCategory()->Title : null,
                    "categorySort" => $option->MetaFieldOptionCategory()->exists() ? $option->MetaFieldOptionCategory()->Sort : null
                ];
            }

        }

        $metaField = $this->MetaField();

        $overideReadOnly = false;
        if ($contextualRepoItem && ($contextualRepoItem->RepoType == 'LearningObject' && $metaField->SystemKey == 'ContainsParents')) {
            $cacheItem = false;
            $overideReadOnly = $this->getOverrideReadOnly($contextualRepoItem);
        }
        if ($metaFieldType->Title == 'DOI') {
            $cacheItem = false;
        }

        $jsonArray = [
            "key" => DataObjectJsonApiEncoder::getJSONAPIID($metaField),
            "fieldType" => $metaField->MetaFieldType()->Key,
            "titleEN" => $this->Label_EN,
            "titleNL" => $this->Label_NL,
            "isSmallField" => $this->IsSmallField,
            'channelType' => in_array($metaField->SystemKey, ['Archive', 'PrivateChannel', 'PublicChannel']) ? $metaField->SystemKey : null,
            "infoTextEN" => $this->InfoText_EN,
            "infoTextNL" => $this->InfoText_NL,
            "descriptionEN" => $this->Description_EN,
            "descriptionNL" => $this->Description_NL,
            "locked" => $this->IsLocked,
            "enabled" => $this->IsEnabled,
            'readOnly' => $this->IsReadOnly || $overideReadOnly,
            'hidden' => $this->IsHidden ? 1 : 0,
            'copyable' => $this->IsCopyable,
            'replicatable' => $this->IsReplicatable,
            "attributeKey" => $metaField->AttributeKey,
            "options" => $options,
            "validationRegex" => $metaFieldType->ValidationRegex,
            "required" => $this->IsRequired,
            "retainOrder" => $metaField->RetainOptionOrder ? 1 : 0,
        ];
        if ($cacheItem) {
            SimpleCacheItem::cacheFor($this, 'Description', $jsonArray);
        }
        return $jsonArray;
    }

    function getOverrideReadOnly($repoItem) {
        if ($this->MetaField()->MetaFieldType()->Title === 'DOI') {
            $repoItemAnswer = $repoItem->RepoItemMetaFields()->filter(['MetaFieldID' => $this->MetaFieldID])->first();
            if ($repoItemAnswer && $repoItemAnswer->exists()) {
                $answerValue = $repoItemAnswer->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
                if ($answerValue && $answerValue->exists()) {
                    $generatedDoi = GeneratedDoi::get()->filter(['DOI' => $answerValue->Value])->first();
                    if ($generatedDoi && $generatedDoi->exists()) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    //Automatically called via checkRelationPermissionForObjectName
    function isAuthor(Member $member, $context = []) {
        if ($repoItemContext = $context[RepoItem::class]) {
            return $repoItemContext->PersonID == $member->ID;
        }
        return false;
    }

    protected function onBeforeWrite() {
        parent::onBeforeWrite();
        if (!$this->isInDB() && !$this->SkipPropagation) {
            if ($template = $this->Template()) {
                if ($template->InstituteID == 0) {
                    // copy metafield defaults
                    if ($metaField = $this->MetaField()) {
                        if ($this->Label_NL == '') {
                            $this->Label_NL = $metaField->Label_NL;
                        }
                        if ($this->Label_EN == '') {
                            $this->Label_EN = $metaField->Label_EN;
                        }
                        if ($this->Description_NL == '') {
                            $this->Description_NL = $metaField->Description_NL;
                        }
                        if ($this->Description_EN == '') {
                            $this->Description_EN = $metaField->Description_EN;
                        }
                    }
                }
            }
        }

    }

    protected function onAfterWrite() {
        parent::onAfterWrite();
        if ($this->isInDB() && !$this->SkipPropagation) {
            $this->downPropagateFast();
        }
        $this->removeCacheWhereNeeded();
    }

    /***
     * @throws \SilverStripe\ORM\ValidationException
     * THis method makes sure all templates on a lower institute level will be updated with the new information
     */
    public function downPropagateFast($limitToTemplate = null) {
        $currentMetaField = $this;
        if (!is_null($limitToTemplate)) {
            $templateTitle = $limitToTemplate->Title;
        } else {
            $templateTitle = 'null';
        }
        Logger::debugLog('down propagate fast ' . $currentMetaField->Title . ' limitToTemplate = ' . $templateTitle . ' changed = ' . print_r($this->getChangedFields(), true));
        // if this is default template, than find all top level institutes
        $template = $currentMetaField->Template();
        if (!($template && $template->exists())) {
            return;
        }

        $member = Security::getCurrentUser();
        if ($member && $member->exists()) {
            $memberID = $member->ID;
        } else {
            $memberID = 0;
        }

        $paramArray = [
            $currentMetaField->MetaFieldID,
            $currentMetaField->MetaFieldUuid,
            $currentMetaField->TemplateSectionID,
            $currentMetaField->TemplateSectionUuid,
            $currentMetaField->IsRemoved,
            $currentMetaField->IsSmallField,
            $currentMetaField->SortOrder,
            $currentMetaField->IsRequired,
            $currentMetaField->IsLocked,
            $currentMetaField->IsCopyable,
            $currentMetaField->IsReplicatable,
            $currentMetaField->IsEnabled,
            $currentMetaField->IsReadOnly,
            $currentMetaField->IsHidden,
            $currentMetaField->Label_EN,
            $currentMetaField->Label_NL,
            $currentMetaField->Description_EN,
            $currentMetaField->Description_NL,
            $currentMetaField->InfoText_EN,
            $currentMetaField->InfoText_NL,
            $memberID,
            $memberID,
            $currentMetaField->MetaFieldID,
            $template->RepoType,
            $template->InstituteID
        ];

        // insert metafield for all templates below
        $insertMetaFieldsQuery = "
INSERT IGNORE INTO SurfSharekit_TemplateMetaField 
(
LastEdited, 
Created, 
Uuid, 
TemplateID, 
TemplateUuid, 
MetaFieldID, 
MetaFieldUuid, 
TemplateSectionID, 
TemplateSectionUuid, 
IsRemoved, 
IsSmallField,
SortOrder,
IsRequired,
IsLocked,
IsCopyable,
IsReplicatable,
IsEnabled,
IsReadOnly,
IsHidden,
Label_EN,
Label_NL,
Description_EN,
Description_NL,
InfoText_EN,
InfoText_NL,
CreatedByID, 
ModifiedByID
)
SELECT 
now() as LastEdited,
now() as Created,
(SELECT LOWER(CONCAT(
    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, '0'), 
    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, '0'), '-',
    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, '0'), '-', 
    '4',
    LPAD(HEX(FLOOR(RAND() * 0x0fff)), 3, '0'), '-', 
    HEX(FLOOR(RAND() * 4 + 8)), 
    LPAD(HEX(FLOOR(RAND() * 0x0fff)), 3, '0'), '-', 
    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, '0'),
    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, '0'),
    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, '0')))) as Uuid,
t.ID AS TemplateID, 
t.UUID as TemplateUuid,
? AS MetaFieldID, 
? as MetaFieldUuid,
? AS TemplateSectionID,
? as TemplateSectionUuid,
? AS IsRemoved,
? AS IsSmallField,
? AS SortOrder,
? AS IsRequired,
? as IsLocked,
? AS IsCopyable,
? AS IsReplicatable,
? as IsEnabled,
? as IsReadOnly,
? as IsHidden,
? as Label_EN,
? as Label_NL,
? as Description_EN,
? as Description_NL,
? as InfoText_EN,
? as InfoText_NL,
? as CreatedByID,
? as ModifiedByID
FROM 
SurfSharekit_Template t
left join SurfSharekit_TemplateMetaField tmf
    on t.ID = tmf.TemplateID and tmf.MetaFieldID = ?
WHERE
	tmf.ID is null AND
t.RepoType = ?
AND t.InstituteID in
(
    SELECT 
        p1.ID
    FROM 
        SurfSharekit_Institute p1
    LEFT JOIN   SurfSharekit_Institute p2 on p2.ID = p1.InstituteID 
    LEFT JOIN   SurfSharekit_Institute p3 on p3.ID = p2.InstituteID 
    LEFT JOIN   SurfSharekit_Institute p4 on p4.ID = p3.InstituteID  
    LEFT JOIN   SurfSharekit_Institute p5 on p5.ID = p4.InstituteID  
    LEFT JOIN   SurfSharekit_Institute p6 on p6.ID = p5.InstituteID
    LEFT JOIN   SurfSharekit_Institute p7 ON p7.ID = p6.InstituteID
    LEFT JOIN   SurfSharekit_Institute p8 ON p8.ID = p7.InstituteID
    LEFT JOIN   SurfSharekit_Institute p9 ON p9.ID = p8.InstituteID
    WHERE       
    ? IN (
        p1.ID,
        p1.InstituteID,
        p2.InstituteID,
        p3.InstituteID,
        p4.InstituteID,
        p5.InstituteID,
        p6.InstituteID,
        p7.InstituteID,
        p8.InstituteID,
        p9.InstituteID
    )
)
";

        DB::prepared_query($insertMetaFieldsQuery, $paramArray);
        Logger::debugLog('insert metafield if not exists ' . $currentMetaField->Title . ' for all templates below = ' . $templateTitle);

        if ($limitToTemplate == null) {
            $updateParamArray = [
                $currentMetaField->TemplateSectionID,
                $currentMetaField->TemplateSectionUuid,
                $currentMetaField->IsRemoved,
                $currentMetaField->IsSmallField,
                $currentMetaField->SortOrder,
                $currentMetaField->IsRequired,
                $currentMetaField->IsLocked,
                $currentMetaField->IsCopyable,
                $currentMetaField->IsReplicatable,
                $currentMetaField->IsEnabled,
                $currentMetaField->IsReadOnly,
                $currentMetaField->IsHidden,
                $currentMetaField->Label_EN,
                $currentMetaField->Label_NL,
                $currentMetaField->Description_EN,
                $currentMetaField->Description_NL,
                $currentMetaField->InfoText_EN,
                $currentMetaField->InfoText_NL,
                $currentMetaField->MetaFieldID,
                $template->RepoType,
                $template->InstituteID,
                $template->InstituteID,
                $template->InstituteID,
                $template->InstituteID,
                $template->InstituteID,
                $template->InstituteID,
                $template->InstituteID,
                $template->InstituteID,
                $template->InstituteID
            ];

            $updateMetaFieldsQuery = '
        UPDATE
SurfSharekit_TemplateMetaField tm
SET
TemplateSectionID = ?,
TemplateSectionUuid = ?,
IsRemoved = ?,
IsSmallField = ?,
SortOrder = ?,
IsRequired = ?,
IsLocked = ?,
IsCopyable = ?,
IsReplicatable = ?,
IsEnabled = ?,
IsReadOnly = ?,
IsHidden = ?,
Label_EN = ?,
Label_NL = ?,
Description_EN = ?,
Description_NL = ?,
InfoText_EN = ?,
InfoText_NL = ?
WHERE
tm.MetaFieldID = ?
AND
tm.TemplateID in
(
SELECT distinct
t.ID
FROM
SurfSharekit_Template t
LEFT JOIN SurfSharekit_Institute ti1 ON ti1.ID = t.InstituteID
LEFT JOIN SurfSharekit_Template pit1 ON pit1.InstituteID = ti1.InstituteID AND pit1.RepoType = t.RepoType
LEFT JOIN SurfSharekit_Institute ti2 ON ti2.ID = pit1.InstituteID
LEFT JOIN SurfSharekit_Template pit2 ON pit2.InstituteID = ti2.InstituteID AND pit2.RepoType = t.RepoType
LEFT JOIN SurfSharekit_Institute ti3 ON ti3.ID = ti3.InstituteID
LEFT JOIN SurfSharekit_Template pit3 ON pit3.InstituteID = ti3.InstituteID AND pit3.RepoType = t.RepoType
LEFT JOIN SurfSharekit_Institute ti4 ON ti4.ID = ti3.InstituteID
LEFT JOIN SurfSharekit_Template pit4 ON pit4.InstituteID = ti3.InstituteID AND pit4.RepoType = t.RepoType 
LEFT JOIN SurfSharekit_Institute ti5 ON ti5.ID = ti4.InstituteID
LEFT JOIN SurfSharekit_Template pit5 ON pit5.InstituteID = ti4.InstituteID AND pit5.RepoType = t.RepoType 
LEFT JOIN SurfSharekit_Institute ti6 ON ti6.ID = ti5.InstituteID
LEFT JOIN SurfSharekit_Template pit6 ON pit6.InstituteID = ti5.InstituteID AND pit6.RepoType = t.RepoType 
LEFT JOIN SurfSharekit_Institute ti7 ON ti7.ID = ti6.InstituteID
LEFT JOIN SurfSharekit_Template pit7 ON pit7.InstituteID = ti6.InstituteID AND pit7.RepoType = t.RepoType 
WHERE t.RepoType = ?
AND (ifnull(t.AllowCustomization,0) = 0 OR t.InstituteID = ?)
AND (ifnull(pit1.AllowCustomization,0) = 0 OR pit1.InstituteID = ?)
AND (ifnull(pit2.AllowCustomization,0) = 0 OR pit2.InstituteID = ?)
AND (ifnull(pit3.AllowCustomization,0) = 0 OR pit3.InstituteID = ?)
AND (ifnull(pit4.AllowCustomization,0) = 0 OR pit4.InstituteID = ?)
AND (ifnull(pit5.AllowCustomization,0) = 0 OR pit5.InstituteID = ?)
AND (ifnull(pit6.AllowCustomization,0) = 0 OR pit6.InstituteID = ?)
AND (ifnull(pit7.AllowCustomization,0) = 0 OR pit7.InstituteID = ?)
AND t.InstituteID in
(
    SELECT 
        DISTINCT p1.ID
    FROM 
        SurfSharekit_Institute p1
    LEFT JOIN   SurfSharekit_Institute p2 on p2.ID = p1.InstituteID 
    LEFT JOIN   SurfSharekit_Institute p3 on p3.ID = p2.InstituteID 
    LEFT JOIN   SurfSharekit_Institute p4 on p4.ID = p3.InstituteID  
    LEFT JOIN   SurfSharekit_Institute p5 on p5.ID = p4.InstituteID  
    LEFT JOIN   SurfSharekit_Institute p6 on p6.ID = p5.InstituteID
    LEFT JOIN   SurfSharekit_Institute p7 ON p7.ID = p6.InstituteID
    LEFT JOIN   SurfSharekit_Institute p8 ON p8.ID = p7.InstituteID
    LEFT JOIN   SurfSharekit_Institute p9 ON p9.ID = p8.InstituteID
    WHERE       
    ? IN (
        p1.ID,
        p1.InstituteID,
        p2.InstituteID,
        p3.InstituteID,
        p4.InstituteID,
        p5.InstituteID,
        p6.InstituteID,
        p7.InstituteID,
        p8.InstituteID,
        p9.InstituteID
    )
)
)';

            Logger::debugLog('update metafields ' . $currentMetaField->Title . ' for all not customized templates below = ' . $templateTitle);
            DB::prepared_query($updateMetaFieldsQuery, $updateParamArray);

            $updateRequiredParamArray = [
                $currentMetaField->TemplateSectionID,
                $currentMetaField->TemplateSectionUuid,
                $currentMetaField->IsRemoved,
                $currentMetaField->IsSmallField,
                $currentMetaField->SortOrder,
                $currentMetaField->IsLocked,
                $currentMetaField->IsCopyable,
                $currentMetaField->IsReplicatable,
                $currentMetaField->MetaFieldID,
                $template->RepoType,
                $template->InstituteID
            ];

            $updateRequiredMetaFieldsQuery = '
        UPDATE
SurfSharekit_TemplateMetaField tm
SET
TemplateSectionID = ?,
TemplateSectionUuid = ?,
IsRemoved = ?,
IsSmallField = ?,
SortOrder = ?,
IsLocked = ?,
IsCopyable = ?,
IsReplicatable = ?
WHERE
tm.MetaFieldID = ?
AND
tm.TemplateID in
(
SELECT distinct
t.ID
FROM
SurfSharekit_Template t
WHERE t.RepoType = ?
AND t.InstituteID in
(
    SELECT 
        DISTINCT p1.ID
    FROM 
        SurfSharekit_Institute p1
    LEFT JOIN   SurfSharekit_Institute p2 on p2.ID = p1.InstituteID 
    LEFT JOIN   SurfSharekit_Institute p3 on p3.ID = p2.InstituteID 
    LEFT JOIN   SurfSharekit_Institute p4 on p4.ID = p3.InstituteID  
    LEFT JOIN   SurfSharekit_Institute p5 on p5.ID = p4.InstituteID  
    LEFT JOIN   SurfSharekit_Institute p6 on p6.ID = p5.InstituteID
    LEFT JOIN   SurfSharekit_Institute p7 ON p7.ID = p6.InstituteID
    LEFT JOIN   SurfSharekit_Institute p8 ON p8.ID = p7.InstituteID
    LEFT JOIN   SurfSharekit_Institute p9 ON p9.ID = p8.InstituteID
    WHERE       
    ? IN (
        p1.ID,
        p1.InstituteID,
        p2.InstituteID,
        p3.InstituteID,
        p4.InstituteID,
        p5.InstituteID,
        p6.InstituteID,
        p7.InstituteID,
        p8.InstituteID,
        p9.InstituteID
    )
)
)';

            Logger::debugLog('update required fields ' . $currentMetaField->Title . ' for all templates below = ' . $templateTitle);
            DB::prepared_query($updateRequiredMetaFieldsQuery, $updateRequiredParamArray);
        }
    }

    public function getDefaultJsonApiAnswerDescription() {
        $defaultAnswers = [];
        if ($defaults = $this->MetaField()->getDefaultValuesFor(Security::getCurrentUser(), $this->Template())) {
            foreach ($defaults as $answer) {

                $defaultAnswers[] = [
                    'repoItemID' => $answer->RepoItemUuid ? DataObjectJsonApiEncoder::getJSONAPIID($answer->RepoItem()) : null,
                    'value' => $this->MetaField()->MetaFieldType()->JSONEncodedStorage ? json_decode($answer->Value) : $answer->Value,
                    'summary' => $answer->getRelatedObjectSummary(),
                    'optionKey' => $answer->MetaFieldOptionID ? DataObjectJsonApiEncoder::getJSONAPIID($answer->MetaFieldOption()) : null,
                    'repoItemFileID' => $answer->RepoItemFileID ? DataObjectJsonApiEncoder::getJSONAPIID($answer->RepoItemFile()) : null,
                    'personID' => $answer->PersonID ? DataObjectJsonApiEncoder::getJSONAPIID($answer->Person()) : null,
                    'instituteID' => $answer->InstituteID ? DataObjectJsonApiEncoder::getJSONAPIID($answer->Institute()) : null,
                    'sortOrder' => count($defaultAnswers)];
            }
        } else {
            foreach ($this->DefaultMetaFieldOptionParts() as $answer) {
                $defaultAnswers[] = [
                    'repoItemID' => $answer->RepoItemUuid ? DataObjectJsonApiEncoder::getJSONAPIID($answer->RepoItem()) : null,
                    'value' => $this->MetaField()->MetaFieldType()->JSONEncodedStorage ? json_decode($answer->Value) : $answer->Value,
                    'summary' => $answer->getRelatedObjectSummary(),
                    'optionKey' => $answer->MetaFieldOptionID ? DataObjectJsonApiEncoder::getJSONAPIID($answer->MetaFieldOption()) : null,
                    'repoItemFileID' => $answer->RepoItemFileID ? DataObjectJsonApiEncoder::getJSONAPIID($answer->RepoItemFile()) : null,
                    'personID' => $answer->PersonID ? DataObjectJsonApiEncoder::getJSONAPIID($answer->Person()) : null,
                    'instituteID' => $answer->InstituteID ? DataObjectJsonApiEncoder::getJSONAPIID($answer->Institute()) : null,
                    'sortOrder' => count($defaultAnswers)];
            }
        }
        return [
            "fieldKey" => DataObjectJsonApiEncoder::getJSONAPIID($this->MetaField()),
            'values' => $defaultAnswers
        ];
    }

    public function removeCacheWhereNeeded() {
        $instituteScopeID = $this->Template()->InstituteID;
        if (!$instituteScopeID) {
            $instituteIDList = Institute::get()->filter(["InstituteID" => 0])->getIDList();
        } else {
            $instituteIDList = [$instituteScopeID];
        }
        $items = InstituteScoper::getDataListScopedTo(SimpleCacheItem::class, $instituteIDList)
            ->innerJoin('SurfSharekit_TemplateMetaField', 'SurfSharekit_TemplateMetaField.ID = SurfSharekit_SimpleCacheItem.DataObjectID')
            ->filter(['Key' => 'Description', 'DataObjectClass' => "SurfSharekit\\Models\\TemplateMetaField"])
            ->where(['SurfSharekit_TemplateMetaField.MetaFieldID' => $this->MetaFieldID]);
        Logger::debugLog("removed items: " . $items->count());
        $items->removeAll();
    }
}