<?php

namespace SurfSharekit\Models;

use DataObjectJsonApiEncoder;
use Exception;
use ExternalRepoItemChannelJsonApiDescription;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\RequestOptions;
use LogicException;
use NathanCox\HasOneAutocompleteField\Forms\HasOneAutocompleteField;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Ramsey\Uuid\Uuid;
use RelationaryPermissionProviderTrait;
use RepoItemModelAdmin;
use SilverStripe\Assets\File;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SimpleXMLElement;
use SurfSharekit\Api\DoiCreator;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\ForbiddenException;
use SurfSharekit\Api\GetRecordNode;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Api\JsonApi;
use SurfSharekit\Api\PermissionFilter;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\DateHelper;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\MemberHelper;
use SurfSharekit\Models\Helper\NotificationEventCreator;
use SurfSharekit\models\tasks\TaskRemover;
use UuidExtension;

/**
 * Class RepoItem
 * @package SurfSharekit\Models
 * @property string RepoType
 * @property string Status
 * @property string DeclineReason
 * @property string Title
 * @property string Alias
 * @property string SubType
 * @property string Subtitle
 * @property string Language
 * @property boolean IsRemoved
 * @property boolean PendingForDestruction
 * @property boolean IsArchived
 * @property boolean IsPublic
 * @property boolean IsHistoricallyPublished
 * @property boolean UploadedFromApi
 * @property string EmbargoDate
 * @property string PublicationDate
 * @property string AccessRight
 * @property Int OwnerID
 * @property Int InstituteID
 * @method Person Owner
 * @method Institute Institute
 * @method HasManyList<RepoItemMetaField> RepoItemMetaFields
 * DataObject representing the collections of answers on a @see Template (i.e. a collection of MetaData)
 */
class RepoItem extends DataObject implements PermissionProvider {
    use RelationaryPermissionProviderTrait;

    const RELATION_ARCHIVED = 'Archived';

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    const RELATION_AUTHOR = 'Author';
    const RELATION_REPORT = 'REPORT';
    const RELATION_ALL = "ALL";

    private static $table_name = 'SurfSharekit_RepoItem';
    private static $default_sort = 'LastEdited DESC';

    private static $db = [
        'RepoType' => 'Enum(array("PublicationRecord", "LearningObject", "ResearchObject", "Dataset", "Project", "RepoItemRepoItemFile", "RepoItemLearningObject", "RepoItemLink", "RepoItemPerson", "RepoItemResearchObject"))',
        'Status' => 'Enum(array("Draft", "Published", "Submitted", "Approved", "Declined", "Revising", "Embargo", "Migrated"), "Draft")',
        'DeclineReason' => 'Text',
        'Title' => 'Varchar(255)',
        'Alias' => 'Varchar(255)',
        'SubType' => 'Varchar(255)',
        'Subtitle' => 'Varchar(255)',
        'Language' => 'Varchar(255)',
        'IsRemoved' => 'Boolean(0)',
        'PendingForDestruction' => 'Boolean(0)',
        'IsArchived' => 'Boolean(0)',
        'IsPublic' => 'Boolean(0)',
        'IsHistoricallyPublished' => 'Boolean(0)',
        'NeedsToBeFinished' => 'Boolean(0)',
        'UploadedFromApi' => 'Boolean(0)',
        'EmbargoDate' => 'Datetime',
        'PublicationDate' => 'Datetime',
        'AccessRight' => 'Varchar(255)'
    ];

    private static $has_one = [
        'Owner' => Person::class,
        'Institute' => Institute::class
    ];

    private static $has_many = [
        'RepoItemMetaFields' => RepoItemMetaField::class,
        "Tasks" => Task::class
    ];

    private static $cascade_deletes = [
        'RepoItemMetaFields'
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'getMemberFullName' => 'Owner',
        'RepoType' => 'RepoType',
        'Status' => 'Status',
        'Created' => 'Created',
        'LastEdited' => 'Last Edited',
    ];

    private static $indexes = [
        'FulltextTitle' => [
            'type' => 'fulltext',
            'columns' => ['Title']
        ],
        'FulltextSubtitle' => [
            'type' => 'fulltext',
            'columns' => ['Subtitle']
        ],
        'FulltextSearch' => [
            'type' => 'fulltext',
            'columns' => ['Title', 'Subtitle']
        ],
        'RepoType' => true,
        'Status' => true
    ];

    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter',
        'RepoType' => 'ExactMatchFilter',
        'Status' => 'ExactMatchFilter',
        'Created' => ' LessThanOrEqualFilter',
        'LastEdited' => 'LessThanOrEqualFilter',
        'Institute.Title' => 'ExactMatchFilter',
        'Uuid' => [
            'title' => 'Identifier',
            'filter' => 'ExactMatchFilter'
        ]
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        if ($this->ModifiedByID) {
            $modifiedByField = ReadonlyField::create('ModifiedByDisplay', 'Modified by', $this->ModifiedBy()->Title);
            $fields->insertBefore('RepoType', $modifiedByField);
        }
        $modifiedField = ReadonlyField::create('ModifiedDisplay', 'Modified at', $this->LastEdited);
        $fields->insertBefore('RepoType', $modifiedField);
        if ($this->CreatedByID) {
            $createdByField = ReadonlyField::create('CreatedByDisplay', 'Created by', $this->CreatedBy()->Title);
            $fields->insertBefore('RepoType', $createdByField);
        }

        $createdField = ReadonlyField::create('CreatedDisplay', 'Created at', $this->Created);
        $fields->insertBefore('RepoType', $createdField);

        $belongsToInstitute = $this->Institute();
        if ($belongsToInstitute && $belongsToInstitute->exists()) {
            $belongsToInstituteName = $belongsToInstitute->Title;
        } else {
            $belongsToInstituteName = '- none -';
        }

        $identifierField = ReadonlyField::create('Uuid', 'Identifier');
        $fields->insertBefore('', $identifierField);

        /** @var GridField $repoItemMetaFieldsGridField */
        $repoItemMetaFieldsGridField = $fields->dataFieldByName('RepoItemMetaFields');
        $repoItemMetaFieldsGridFieldConfig = $repoItemMetaFieldsGridField->getConfig();
        $repoItemMetaFieldsGridFieldConfig->removeComponentsByType(new GridFieldAddExistingAutocompleter());

        $fields = $fields->makeReadonly();

        $belongsToInstituteDisplayField = HasOneAutocompleteField::create('InstituteID', 'Belongs to institute', Institute::class);
        $belongsToInstituteDisplayField->setSearchFields(['Title']);
        $belongsToInstituteDisplayField->setValue($belongsToInstituteName);
        $fields->replaceField('InstituteID', $belongsToInstituteDisplayField);

        $belongsToOwnerDisplayField = HasOneAutocompleteField::create('OwnerID', 'Owner', Person::class);
        $belongsToOwnerDisplayField->setSearchFields(['FirstName', 'Surname']);
        $belongsToOwnerDisplayField->setValue($this->OwnerID);
        $fields->replaceField('OwnerID', $belongsToOwnerDisplayField);

        return $fields;
    }

    function getActiveParent() {
        $parentRepoItemValue = RepoItemMetaFieldValue::get()->filter('RepoItemID', $this->ID)->filter('IsRemoved', 0)->first();
        if (!$parentRepoItemValue || !$parentRepoItemValue->exists()) {
            return null;
        }
        $parentRepoItemAnswer = $parentRepoItemValue->RepoItemMetaField();
        if (!$parentRepoItemAnswer || !$parentRepoItemAnswer->exists()) {
            return null;
        }
        return $parentRepoItemAnswer->RepoItem();
    }

    /**
     * @return array
     * Utility method to list all TemplateMetaField this RepoItem should be based on
     */
    function getTemplateSteps() {
        return $this->Template()->getSteps($this);
    }

    /**
     * @return array
     * Utility Method to describe each @see RepoItemMetaFieldValue of this RepoItem
     */
    function getAnswersForJsonAPI() {
        $answers = [];
        $repoItemMetaFields = $this->RepoItemMetaFields()->toArray();
        $getRepoItemMetaField = function ($metaFieldID) use ($repoItemMetaFields) {
            foreach ($repoItemMetaFields as $repoItemMetaField) {
                if ($repoItemMetaField->MetaFieldID == $metaFieldID) {
                    return $repoItemMetaField;
                }
            }
            return null;
        };

        foreach ($this->Template()->TemplateMetaFields() as $templateMetaField) {
            $repoItemMetaField = $getRepoItemMetaField($templateMetaField->MetaFieldID);
            if ($repoItemMetaField && $repoItemMetaField->exists()) {
                $answersForMetaField = $repoItemMetaField->getJsonAPIDescription($this);
            } else {
                $answersForMetaField = $templateMetaField->getDefaultJsonApiAnswerDescription($this);
            }
            if (count($answersForMetaField['values'])) {
                $answers[] = $answersForMetaField;
            }
        }
        return $answers;
    }

    function getLastEditorSummary() {
        // Only check for canView permission as the others are irrelevant in this case
        $lastEditor = $this->ModifiedBy();
        return [
            'id' => $lastEditor->Uuid,
            'name' => $lastEditor->Name,
            'permissions' => $lastEditor->LoggedInUserCanViewPermission,
        ];
    }

    function getCreatorSummary() {
        // Only check for canView permission as the others are irrelevant in this case
        $creator = $this->CreatedBy();
        return [
            'id' => $creator->Uuid,
            'name' => $creator->Name,
            'permissions' => $creator->LoggedInUserCanViewPermission,
        ];
    }

    function getCreatedLocal() {
        return DateHelper::localDatetimeFromUTC($this->Created);
    }

    function getLastEditedLocal() {
        return DateHelper::localDatetimeFromUTC($this->LastEdited);
    }

    /**
     * Utility method to set repo item metafield value for metafield uuid
     */
    function setRepoItemMetaFieldValue($metaFieldUuid, $values) {
        /** @var MetaField $metaField */
        $metaField = MetaField::get()->filter(['Uuid' => $metaFieldUuid])->first();
        if (!is_null($metaField)) {
            $repoItemMetaField = $this->RepoItemMetaFields()->filter(['MetaFieldId' => $metaField->ID])->first();
            if (is_null($repoItemMetaField) || $repoItemMetaField === false) {
                $repoItemMetaField = new RepoItemMetaField();
                $repoItemMetaField->setField('RepoItemID', $this->ID);
                $repoItemMetaField->setField('MetaFieldID', $metaField->ID);
                $repoItemMetaField->write();
            } else {
                foreach ($repoItemMetaField->RepoItemMetaFieldValues() as $answer) {
                    $answer->delete();
                }
            }
            foreach ($values as $value) {
                if (is_null($value) || trim(strval($value)) == '') {
                    continue;
                } else {
                    $value = trim(strval($value));
                }

                $repoItemMetaFieldValue = RepoItemMetaFieldValue::create();
                if ($metaField->MetaFieldOptions()->count() > 0) {
                    // option field, select option based on value
                    $metaFieldOption = $metaField->MetaFieldOptions()->filter(['value' => $value])->first();
                    if (!is_null($metaFieldOption) && $metaFieldOption->exists()) {
                        $repoItemMetaFieldValue->setField('MetaFieldOptionID', $metaFieldOption->ID);
                    } else {
                        if (in_array(strtolower($metaField->MetaFieldType()->getField('Title')), ['tag', 'dropdowntag'])) {
                            // tag field, so add option
                            $metaFieldOption = MetaFieldOption::create();
                            $metaFieldOption->MetaFieldID = $metaField->ID;
                            $metaFieldOption->Label_EN = $value;
                            $metaFieldOption->Label_NL = $value;
                            $metaFieldOption->Value = $value;
                            try {
                                $metaFieldOption->write();
                            } catch (ValidationException $e) {
                                Logger::debugLog("Repoitem ValidationException: " . $e->getMessage());
                            }
                            $repoItemMetaFieldValue->setField('MetaFieldOptionID', $metaFieldOption->getField('ID'));
                        } else {
                            continue; // item not found, do not set
                        }
                    }
                }
                $repoItemMetaFieldValue->setField('Value', $value);
                $repoItemMetaFieldValue->setField('RepoItemMetaFieldID', $repoItemMetaField->ID);
                try {
                    $repoItemMetaFieldValue->write();
                } catch (ValidationException $e) {
                    // TODO, catch validation exception
                }
            }
        }
    }

    function getTitle() {
        return $this->Alias ?: parent::getTitle();
    }

    /**
     * @param $answerArray
     * @throws Exception
     * Method to remove all and create new @see RepoItemMetaField for each answer given
     */
    function setAnswersFromAPI($answerArray) {
        DB::get_conn()->transactionStart();
        $this->updateRepoItemMetaFieldsWith($answerArray);
        DB::get_conn()->transactionEnd();
    }

    /**
     * @param $answerArray
     * @throws \SilverStripe\ORM\ValidationException
     * Method to create new @see RepoItemMetaField for each answer given
     */
    private function updateRepoItemMetaFieldsWith($answerArray) {
        if (!(is_array($answerArray) && $answerArray !== null)) {
            throw new Exception('Answers should be an array and cannot be null');
        }

        $answerValuesIdsChanged = [];
        $template = $this->Template();

        foreach ($answerArray as $answerObject) {
            if (!isset($answerObject['fieldKey'])) {
                throw new Exception('Missing FieldKey');
            }

            $metaField = UuidExtension::getByUuid(MetaField::class, $answerObject['fieldKey']);
            if (!($metaField && $metaField->Exists())) {
                throw new Exception('Field ' . $answerObject['fieldKey'] . ' does not exist');
            }

            $templateMetaField = $template->TemplateMetaFields()->filter(['IsRemoved' => 0, 'IsEnabled' => 1])->filter('MetaFieldID', $metaField->ID)->first();
            if (!($templateMetaField && $templateMetaField->Exists())) {
                throw new Exception('Cannot find field for fieldKey: ' . $answerObject['fieldKey']);
            }

            $preexistingRepoItemMetaField = $this->RepoItemMetaFields()->filter(['MetaFieldID' => $metaField->ID])->first();
            if ($preexistingRepoItemMetaField && $preexistingRepoItemMetaField->exists() && ($templateMetaField->IsReadOnly || $templateMetaField->getOverrideReadOnly($this))) {
                $answerValuesIdsChanged = array_merge($answerValuesIdsChanged, $preexistingRepoItemMetaField->RepoItemMetaFieldValues()->column('ID'));
                continue;
            }

            $repoItemMetaField = ($preexistingRepoItemMetaField && $preexistingRepoItemMetaField->exists()) ? $preexistingRepoItemMetaField : new RepoItemMetaField();
            if (!$preexistingRepoItemMetaField || !$preexistingRepoItemMetaField->exists()) {
                $repoItemMetaField->RepoItemID = $this->ID;
                $repoItemMetaField->MetaFieldID = $metaField->ID;
            }
            $repoItemMetaField->write(); //Always write for an audit entry

            if (!isset($answerObject['values'])) {
                throw new Exception('Missing answer values for answer for fieldKey: ' . $answerObject['fieldKey']);
            }
            $answerValueArray = $answerObject['values'];
            if (!is_array($answerValueArray)) {
                throw new Exception('Missing answer values array for fieldKey: ' . $answerObject['fieldKey']);
            }

            $valueHolderOffsets = [
                'Value' => 0,
                'MetaFieldOptionID' => null,
                'RepoItemID' => null,
                'RepoItemFileID' => null,
                'PersonID' => null,
                'InstituteID' => null
            ];

            foreach ($answerValueArray as $answerValueObject) {
                $repoItemMetaFieldValue = new RepoItemMetaFieldValue();
                $repoItemMetaFieldValue->RepoItemMetaFieldID = $repoItemMetaField->ID;

                if (isset($answerValueObject['value']) && $value = $answerValueObject['value']) {
                    $repoItemMetaFieldValue->Value = $metaField->MetaFieldType()->JSONEncodedStorage ? json_encode($value) : $value;
                }

                if (isset($answerValueObject['sortOrder'])) {
                    $sortOrder = $answerValueObject['sortOrder'];
                    if (!is_int($sortOrder) || $sortOrder < 0) {
                        throw new Exception("Sortorder must be a positive integer");
                    }
                    $repoItemMetaFieldValue->SortOrder = $sortOrder;
                }

                if (isset($answerValueObject['optionKey']) && $optionKey = $answerValueObject['optionKey']) {
                    $option = UuidExtension::getByUuid(MetaFieldOption::class, $optionKey);
                    if (!($option && $option->Exists() && $option->MetaFieldID == $metaField->ID)) {
                        Logger::debugLog($metaField->Title);
                        throw new Exception('Wrong optionKey given as answer for field ' . $answerObject['fieldKey']);
                    }
                    if ($option->IsRemoved) {
                        throw new Exception('Invalid, \'isRemoved\' optionKey given as answer for field ' . $answerObject['fieldKey']);
                    }
                    $repoItemMetaFieldValue->MetaFieldOptionID = $option->ID;
                } else if (isset($answerValueObject['labelEN']) && $answerValueObject['labelNL']) {
                    $caseSensitive = '';
                    if (in_array(strtolower($metaField->MetaFieldType()->getField('Title')), ['tag', 'dropdowntag'])) {
                        $caseSensitive = ':ExactMatch:case';
                    }

                    $metafieldOption = MetaFieldOption::get()->filter(['MetaFieldID' => $metaField->ID, "Label_EN$caseSensitive" => $answerValueObject['labelEN'], "Label_NL$caseSensitive" => $answerValueObject['labelNL']])->first();
                    if (!$metafieldOption || !$metafieldOption->Exists()) {
                        $metafieldOption = MetaFieldOption::create();
                        $metafieldOption->MetaFieldID = $metaField->ID;
                        $metafieldOption->Label_EN = $answerValueObject['labelEN'];
                        $metafieldOption->Label_NL = $answerValueObject['labelNL'];
                        $metafieldOption->Value = $answerValueObject['labelNL'];
                        $metafieldOption->write();
                    }
                    $repoItemMetaFieldValue->MetaFieldOptionID = $metafieldOption->ID;
                }

                if (isset($answerValueObject['repoItemID']) && $repoItemUuid = $answerValueObject['repoItemID']) {
                    if (!$childRepoItem = UuidExtension::getByUuid(RepoItem::class, $repoItemUuid)) {
                        throw new Exception('Trying to attach nonexisting repo item for field ' . $answerObject['fieldKey']);
                    }

                    if (!$childRepoItem->canEdit()) {
                        throw new Exception("Insufficient permissions for RepoItem $repoItemUuid");
                    }
                    $repoItemMetaFieldValue->RepoItemID = $childRepoItem->ID;
                }

                if (isset($answerValueObject['personID']) && $personUuid = $answerValueObject['personID']) {
                    if (!$personItem = UuidExtension::getByUuid(Person::class, $personUuid)) {
                        throw new Exception('Trying to attach nonexisting person for field ' . $answerObject['fieldKey']);
                    }
                    $repoItemMetaFieldValue->PersonID = $personItem->ID;
                }

                if (isset($answerValueObject['instituteID']) && $instituteUuid = $answerValueObject['instituteID']) {
                    if (!$instituteItem = UuidExtension::getByUuid(Institute::class, $instituteUuid)) {
                        throw new Exception('Trying to attach nonexisting institute for field ' . $answerObject['fieldKey']);
                    }
                    $repoItemMetaFieldValue->InstituteID = $instituteItem->ID;
                }

                if (isset($answerValueObject['repoItemFileID']) && $repoItemFileUuid = $answerValueObject['repoItemFileID']) {
                    if (!$repoItemFile = UuidExtension::getByUuid(RepoItemFile::class, $repoItemFileUuid)) {
                        throw new Exception('Trying to attach nonexisting repoItemFile for field ' . $answerObject['repoItemFileID']);
                    }
                    if (!$repoItemFile->canView()) {
                        throw new Exception("Insufficient permissions for RepoItemFile $repoItemFileUuid");
                    }
                    $repoItemMetaFieldValue->RepoItemFileID = $repoItemFile->ID;
                }

                if (!$metaField->isValidMetaFieldValue($repoItemMetaFieldValue)) {
                    $metaFieldType = $metaField->MetaFieldType();
                    $errorMessage = "No valid answer given for '$templateMetaField->Label_NL'";
                    $errorMessageAddition = "";
                    if($metaFieldType->ValidationRegexErrorMessage) {
                        $errorMessageAddition = ": $metaFieldType->ValidationRegexErrorMessage";
                    }
                    throw new Exception("$errorMessage$errorMessageAddition");
                }

                $preexistingRepoItemMetaFieldValue = null;
                $rpmfv = null;

                foreach ($valueHolderOffsets as $fieldName => $offset) {
                    if ($repoItemMetaFieldValue->$fieldName) {
                        if (is_null($offset)) { //Don't use offset, but the value itself
                            $rpmfv = $repoItemMetaField->RepoItemMetaFieldValues()->filter([$fieldName => $repoItemMetaFieldValue->$fieldName])->first();
                        } else {
                            $rpmfv = $repoItemMetaField->RepoItemMetaFieldValues()->where("$fieldName IS NOT NULL")->limit(1, $offset)->first();
                        }

                        if ($rpmfv && $rpmfv->exists()) {
                            $preexistingRepoItemMetaFieldValue = $rpmfv;
                            if (!is_null($offset)) {
                                $valueHolderOffsets[$fieldName] = $offset + 1;
                            }
                            break;
                        }
                    }
                }

                if ($preexistingRepoItemMetaFieldValue) {
                    $preexistingRepoItemMetaFieldValue->Value = $repoItemMetaFieldValue->Value;
                    $preexistingRepoItemMetaFieldValue->SortOrder = $repoItemMetaFieldValue->SortOrder;
                    $preexistingRepoItemMetaFieldValue->RepoItemID = $repoItemMetaFieldValue->RepoItemID;
                    $preexistingRepoItemMetaFieldValue->MetaFieldOptionID = $repoItemMetaFieldValue->MetaFieldOptionID;
                    $preexistingRepoItemMetaFieldValue->RepoItemMetaFieldID = $repoItemMetaFieldValue->RepoItemMetaFieldID;
                    $preexistingRepoItemMetaFieldValue->RepoItemFileID = $repoItemMetaFieldValue->RepoItemFileID;
                    $preexistingRepoItemMetaFieldValue->PersonID = $repoItemMetaFieldValue->PersonID;
                    $preexistingRepoItemMetaFieldValue->InstituteID = $repoItemMetaFieldValue->InstituteID;
                    $repoItemMetaFieldValue = $preexistingRepoItemMetaFieldValue;
                }
                $repoItemMetaFieldValue->IsRemoved = false;
                $repoItemMetaFieldValue->DisableForceWriteRepoItem = true;
                $repoItemMetaFieldValue->write();
                $repoItemMetaFieldValue->DisableForceWriteRepoItem = false;
                $answerValuesIdsChanged[] = $repoItemMetaFieldValue->ID;

                $repoItemMetaField->RepoItemMetaFieldValues()->Add($repoItemMetaFieldValue);
            }
        }

        //set all attribute keys that may be set to null, to null
        $this->Title = null;
        $this->Alias = null;
        $this->Subtitle = null;
        $this->EmbargoDate = null;
        $this->SubType = null;
        $this->PublicationDate = null;
        $this->AccessRight = null;

        $allRepoItemValues = RepoItemMetaFieldValue::get()
            ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.ID = SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID')
            ->where("SurfSharekit_RepoItemMetaField.RepoItemID = $this->ID");

        foreach ($allRepoItemValues as $repoItemMetaFieldValue) {
            if (!in_array($repoItemMetaFieldValue->ID, $answerValuesIdsChanged)) {
                $repoItemMetaFieldValue->IsRemoved = true;
                $repoItemMetaFieldValue->write();
            }

            $repoItemMetaFieldValue->updateAttributeOfRepoItems([$this]);
        }
    }

    protected function onAfterWrite() {
        parent::onAfterWrite();
        // Task & Notification logic
        if ($this->isChanged('Status')) {
             if($this->REPO_ITEM_STATUS_CHANGED_EVENT == false) {
                 NotificationEventCreator::getInstance()->create(Constants::REPO_ITEM_STATUS_CHANGED_EVENT, $this);

                 $oldStatus = $this->getChangedFields()['Status']['before'] ?? null;

                 if ($this->Status == 'Submitted' && $oldStatus !== 'Revising') {
                     TaskCreator::getInstance()->createReviewTasks($this);
                 }

                 $this->REPO_ITEM_STATUS_CHANGED_EVENT = true;
             }
        }

        // If existing repoItem got removed, delete all uncompleted review tasks linked to this RepoItem
        if ($this->isChanged("IsRemoved") && $this->IsRemoved) {
            TaskRemover::getInstance()->deleteUncompletedTasksByType($this, Constants::TASK_TYPE_REVIEW);
        }

        // Create fill Tasks when a RepoItem is created through the UploadAPI, only generate once
        if ($this->UploadedFromApi && $this->shouldCreateFillTask) {
            TaskCreator::getInstance()->createFillTasks($this);
        }

        if ($this->isChanged('OwnerID') || $this->isChanged('InstituteID')) {
            ScopeCache::removeCachedViewable(RepoItem::class);
            ScopeCache::removeCachedDataList(RepoItem::class);
        }

        if ($this->RepoType === "RepoItemRepoItemFile") {
            if ($this->Status != 'Embargo' && strtotime($this->EmbargoDate) > time()) {
                $this->setField('Status', 'Embargo');
                $this->forceChange();
                $this->write();
                return;
            }
        }

        if ($this->Status == 'Approved') {
            $this->setField('Status', 'Embargo');
            $this->write();
            return;
        } else if ($this->Status == 'Embargo' && strtotime($this->EmbargoDate) <= time()) {
            $this->publish();
            return;
        }

        $this->updateRelevantRepoItems();

        if ($this->Status == 'Published' && !$this->IsArchivedUpdated) {
            $this->updateArchiveState();
        }

        $this->updateChildrenToIncludeParent($this);
        RepoItemSummary::updateFor($this);
        SearchObject::updateForRepoItem($this);

        if (in_array($this->Status, ['Published']) && $this->IsPublic && DoiCreator::hasDoi($this)) {
            DoiCreator::enrichDoiFor($this);
        }

        if (!(count($this->getChangedFields(true, 2)) == 1 && $this->isChanged('Version')) &&
            ($this->isChanged('Status') || $this->isChanged('IsRemoved'))) {
            // Only push changed item to client when version is not the only changed field
            $this->pushRepoItemToSubscribedClients();
        }
    }

    /**
     * This is an override of the DataObject delete() function. This override doesn't throw an error when parent::onBeforeDelete
     * is not called. Instead, the delete logic is simply not executed when the delete is broken with the exception of onBeforeDelete().
     */
    public function delete() {
        $this->brokenOnDelete = true;
        $this->onBeforeDelete();
        if (!$this->brokenOnDelete) {
            // Deleting a record without an ID shouldn't do anything
            if (!$this->ID) {
                throw new LogicException("DataObject::delete() called on a DataObject without an ID");
            }

            $srcQuery = DataList::create(static::class)
                ->filter('ID', $this->ID)
                ->dataQuery()
                ->query();
            $queriedTables = $srcQuery->queriedTables();
            $this->extend('updateDeleteTables', $queriedTables, $srcQuery);
            foreach ($queriedTables as $table) {
                $delete = SQLDelete::create("\"$table\"", ['"ID"' => $this->ID]);
                $this->extend('updateDeleteTable', $delete, $table, $queriedTables, $srcQuery);
                $delete->execute();
            }
            // Remove this item out of any caches
            $this->flushCache();

            $this->onAfterDelete();

            $this->OldID = $this->ID;
            $this->ID = 0;
        }
    }

    protected function onBeforeDelete() {
        Logger::debugLog("Entered onBeforeDelete() of RepoItem: " . $this->Uuid);
        if ($this->IndirectDelete || $this->canDestroyFromTrash(Security::getCurrentUser())) {
            // RepoItemMetaFields and RepoItemMetaFieldValues are deleted on cascade
            if (in_array($this->RepoType, Constants::SECONDARY_REPOTYPES)) {
                if ($this->RepoType == 'RepoItemRepoItemFile') {
                    $this->deleteFile($this);
                }
                $this->deleteRepoItemSubRepoItems();
                parent::onBeforeDelete();
            } else {
                $this->deleteRepoItemSubRepoItems();
                $this->deleteRepoItemContent();
            }

            // Remove RepoItemSummary
            DB::query("
                DELETE FROM SurfSharekit_RepoItemSummary
                WHERE SurfSharekit_RepoItemSummary.RepoItemID = '$this->ID'
            ");
        } else {
            throw new Exception("You do not have permission to permanently delete this item");
        }

    }

    private function deleteFile($repoItemRepoItemFile) {
        Logger::debugLog("deleting file");
        if (Environment::getenv('APPLICATION_ENVIRONMENT') != 'acc') {

            $links = RepoItemMetaFieldValue::get()->filter(['RepoItemID' => $repoItemRepoItemFile->ID, 'IsRemoved' => false]);
            if ($links->count() > 1) {
                Logger::debugLog("more than 1 active link to this repoitemrepoitemfile");
                // If there's more than 1 active link, do NOT delete file associated with this RepoItem
                return;
            }

            $repoItemMetaField = $repoItemRepoItemFile->RepoItemMetaFields()
                ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaField.ID = SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID')
                ->where(["SurfSharekit_RepoItemMetaFieldValue.RepoItemFileID != '0'"])->first();

            $repoItemMetaFieldValues = $repoItemMetaField->RepoItemMetaFieldValues();

            if ($repoItemMetaFieldValues) {
                foreach ($repoItemMetaFieldValues as $repoItemMetaFieldValue) {
                    Logger::debugLog("repoitemmetafieldvalue is not null");
                    Logger::debugLog("repoitemmetafieldvalue ID: " . $repoItemMetaFieldValue->ID);
                    Logger::debugLog("repoitemmetafieldvalue fileID: " . $repoItemMetaFieldValue->RepoItemFileID);
                    $file = RepoItemFile::get()->byID($repoItemMetaFieldValue->RepoItemFileID);
                    if ($file) {
                        Logger::debugLog("Removing file with id: {$file->ID} from File table and object store.");
                        $folderID = $file->ParentID;
                        $folder = File::get()->byID($folderID);
                        if ($folder) {
                            $folder->delete();
                        }
                        $file->delete();
                        if ($file->Link) {
                            $s3Client = Injector::inst()->create('Aws\\S3\\S3Client');
                            $bucketKey = [
                                'Bucket' => Environment::getEnv("AWS_BUCKET_NAME"),
                                'Key' => $file->S3Key
                            ];
                            $s3Client->deleteObject($bucketKey);
                        }
                    }
                }
            }
        }
    }

    private function deleteRepoItemContent() {
        // Overwrite delete by manually deleting RepoItemMetaFields and RepoItemMetaFieldValues.
        // This way the removed RepoItem can still be harvested as a delete.
        $repoItemMetaFieldIDs = $this->RepoItemMetaFields()->getIDList();
        foreach ($repoItemMetaFieldIDs as $repoItemMetaFieldID) {
            $this->deleteRepoItemMetaFieldValue($repoItemMetaFieldID);
        }
        $this->deleteRepoItemMetaFields($repoItemMetaFieldIDs);
        $this->PendingForDestruction = true;
        $this->write();
    }

    private function deleteRepoItemMetaFieldValue($repoItemMetaFieldID) {
        DB::query("
                    DELETE FROM SurfSharekit_RepoItemMetaFieldValue
                    WHERE SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = '$repoItemMetaFieldID'
                ");
    }

    private function deleteRepoItemMetaFields($repoItemMetaFieldIDs) {
        $queryString = $repoItemMetaFieldIDs ? ('' . implode(',', $repoItemMetaFieldIDs)) : '-1';
        DB::query("
                    DELETE FROM SurfSharekit_RepoItemMetaField
                    WHERE SurfSharekit_RepoItemMetaField.ID IN ($queryString)
                ");
    }

    private function deleteRepoItemSubRepoItems() {
        $links = RepoItemMetaFieldValue::get()->filter(['RepoItemID' => $this->ID]);

        // Loop through all the RepoItemMetaFieldValues (answers) referencing $this RepoItem.
        // These are called links and should be removed because $this is about to be removed.
        foreach ($links as $link) {
            $link->IsRemoved = 1;
            $link->DisableForceWriteRepoItem = true; // disable ForceWriteRepoItem, as this is not needed for a RepoItem that is about to be deleted
            $link->write();

            $repoItemOfLink = $link->RepoItemMetaField()->RepoItem();
            if ($repoItemOfLink && $repoItemOfLink->exists()) {
                $repoType = $repoItemOfLink->RepoType;
                $isSubRepoItem = in_array($repoType, Constants::SECONDARY_REPOTYPES);

                if ($isSubRepoItem) {
                    // if the RepoItem that contains the link is a subRepoItem it can be deleted as this is just a container for the removed link
                    $repoItemOfLink->IndirectDelete = true;
                    $repoItemOfLink->delete();
                } else {
                    // If the RepoItem that contains the link is a main RepoItem it means that $this was a subRepoItem
                    // So now all RepoItemMetaFieldValues referencing this subRepoItem are set to IsRemoved = true
                    $repoItemValuesReferencingRepoItemOfLink = RepoItemMetaFieldValue::get()->filter(['RepoItemID' => $repoItemOfLink->ID]);
                    foreach ($repoItemValuesReferencingRepoItemOfLink as $repoItemValue) {
                        $repoItemValue->IsRemoved = true;
                        $repoItemValue->DisableForceWriteRepoItem = true;
                        $repoItemValue->write();
                    }
                }
            }
        }

        if (in_array($this->RepoType, Constants::MAIN_REPOTYPES)) {
            $linkedRepoItemIds = $this->RepoItemMetaFields()
                ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaField.ID = SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID')
                ->where(["SurfSharekit_RepoItemMetaFieldValue.RepoItemID != '0'"])->column('SurfSharekit_RepoItemMetaFieldValue.RepoItemID');
            if ($linkedRepoItemIds) {
                $linkedRepoItems = RepoItem::get()->filter(["ID" => $linkedRepoItemIds]);

                foreach ($linkedRepoItems as $linkedRepoItem) {
                    $repoType = $linkedRepoItem->RepoType;
                    $isSubRepoItem = in_array($repoType, Constants::SECONDARY_REPOTYPES);

                    if ($isSubRepoItem) {
                        $links = RepoItemMetaFieldValue::get()->filter(['RepoItemID' => $linkedRepoItem->ID, 'IsRemoved' => false]);
                        if ($links->count() > 1) {
                            // If there's more than 1 active link, do NOT delete
                            continue;
                        }
                        if ($linkedRepoItem->RepoType == 'RepoItemRepoItemFile') {
                            $linkedRepoItem->IndirectDelete = true;
                            $this->deleteFile($linkedRepoItem);
                        }
                        $linkedRepoItem->IndirectDelete = true;
                        $linkedRepoItem->delete();
                    }
                }
            }
        }
    }

    /**
     * Update the attributes of repoItems that make use of this object as an attribute via the attributeKey system
     */
    private function updateRelevantRepoItems() {
        if (!$this->isChanged('ID') && $this->isChanged('Title')) {
            RepoItem::updateAttributeBasedOnMetafield($this->Title, "RepoItemID = $this->ID");
        }
    }

    /**
     * @throws Exception
     * Method to ensure all required @see TemplateMetaField have been answered on
     */
    private function validateRequiredFields() {
        if ($this->SkipValidation) {
            return;
        }

        foreach ($this->Template()->TemplateMetaFields()->filter(['IsRemoved' => 0, 'IsEnabled' => 1]) as $templateField) {
            if ($templateField && $templateField->Exists()) {
                if ($templateField->IsRequired) { //need an answer for this templateMetaField
                    $repoItemMetaField = $this->RepoItemMetaFields()->filter('MetaFieldID', $templateField->MetaField()->ID)->first();
                    if (!$repoItemMetaField || !$this->repoItemMetaFieldContainsValues($repoItemMetaField)) {
                        throw new BadRequestException(ApiErrorConstant::GA_BR_004, "{$this->ID} Missing answer for field with fieldKey: " . DataObjectJsonApiEncoder::getJSONAPIID($templateField->MetaField()) . ' ' . $templateField->MetaField()->Title);
                    }
                }
            } else {
                throw new BadRequestException(ApiErrorConstant::GA_BR_004, 'Missing template metaField for repoItem Metafield');
            }
        }
    }

    private
    function validateChannels() {
        $isPrivateEnabled = $this->isChannelTypeEnabled('PrivateChannel');
        $isPublicEnabled = $this->isChannelTypeEnabled('PublicChannel');
        $isArchiveEnabled = $this->isChannelTypeEnabled('Archive');
        if ($isPrivateEnabled && ($isPublicEnabled || $isArchiveEnabled)) {
            return false;
        }
        if ($isPublicEnabled && ($isPrivateEnabled || $isArchiveEnabled)) {
            return false;
        }
        if ($isArchiveEnabled && ($isPrivateEnabled || $isPublicEnabled)) {
            return false;
        }
        if (!$isArchiveEnabled && !$isPrivateEnabled && !$isPublicEnabled) {
            return false;
        }
        return true;
    }

    private
    function isChannelTypeEnabled($channelType) {
        $amountOfEnabledChannels = RepoItemMetaFieldValue::get()
            ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.ID = SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID')
            ->innerJoin('SurfSharekit_MetaField', 'SurfSharekit_MetaField.ID = SurfSharekit_RepoItemMetaField.MetaFieldID')
            ->innerJoin('SurfSharekit_RepoItem', 'SurfSharekit_RepoItem.ID = SurfSharekit_RepoItemMetaField.RepoItemID')
            ->where(['SurfSharekit_RepoItemMetaFieldValue.IsRemoved' => 0, 'SurfSharekit_RepoItemMetaFieldValue.Value' => 1, 'SurfSharekit_RepoItem.ID' => $this->ID, 'SurfSharekit_MetaField.SystemKey' => $channelType])->count();
        return $amountOfEnabledChannels > 0;
    }

    /**
     * @param $repoItemMetaField
     * @return bool
     * Utility method to checked if a @see RepoItemMetaField has at least one value
     */
    private
    function repoItemMetaFieldContainsValues($repoItemMetaField) {
        foreach (RepoItemMetaFieldValue::get()->filter('RepoItemMetaFieldID', $repoItemMetaField->ID)->filter(['IsRemoved' => false]) as $answerPart) {
            if ($answerPart->Value || $answerPart->MetaFieldOptionID || $answerPart->RepoItemID || $answerPart->PersonID || $answerPart->InstituteID || $answerPart->RepoItemFileID) {
                return true;
            }
            if ($answerPart->MetaFieldOptionID && !$answerPart->MetaFieldOption()->IsRemoved) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $value
     * @throws Exception
     * Method to ensure only legitimate enum-values are set for the Status column in the database, as well as to differentiate between 'Status = Publish' and 'Status = Draft'
     * Sends an email to the author when used to publish RepoItem
     */
    function setStatus($value) {
        $states = ["Draft", "Published", "Submitted", "Approved", "Declined", "Revising", "Embargo", "Migrated"];

        if (!in_array($value, $states)) {
            throw new Exception('Setting incorrect Status, can only be one of: ' . json_encode($states));
        }

        if ($this->ignoreSetStatusCheck === true) {
            $this->setField('Status', $value);
        }

        $stateMap = [
            'Draft' => [
                'Published' => 'canPublish',
                'Submitted' => 'canEdit',
                'Approved' => 'canPublish',
                'Draft' => 'canEdit'
            ],
            'Submitted' => [
                'Submitted' => 'canEdit',
                'Approved' => 'canPublish',
                'Declined' => 'canPublish',
                'Draft' => 'canEdit',
                'Revising' => 'canEdit'
            ],
            'Approved' => [
                'Published' => 'canPublish',
                'Declined' => 'canPublish'
            ],
            'Declined' => [
                'Submitted' => 'canEdit',
                'Draft' => 'canEdit',
                'Approved' => 'canPublish'
            ],
            'Revising' => [
                'Submitted' => 'canEdit',
                'Revising' => 'canEdit'
            ],
            'Published' => [
                'Draft' => 'canPublish',
                'Published' => 'canEdit'
            ],
            'Embargo' => [
                'Draft' => 'canPublish',
                'Embargo' => 'canEdit'
            ],
            'Migrated' => [
                'Published' => 'canEdit',
                'Submitted' => 'canEdit',
                'Approved' => 'canEdit',
                'Draft' => 'canEdit',
                'Embargo' => 'canEdit',
                'Declined' => 'canEdit'
            ]
        ];

        $currentState = $this->Status;
        $newState = $value;

        $allowedNewStates = $stateMap[$currentState];
        $canSetState = false;
        foreach ($allowedNewStates as $s => $m) {
            if ($s == $newState) {
                if (!$this->$m(Security::getCurrentUser())) {
                    throw new ForbiddenException(ApiErrorConstant::GA_FB_001, "No permissions to set state from $currentState to $newState");
                } else {
                    $canSetState = true;
                    break;
                }
            }
        }

        if (!$canSetState) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_004, "Cannot set state from $currentState to $newState");
        }

        $this->setField('Status', $value);
    }

    public
    function canGenerateDoi($member = null, $context = []) {
        if (Permission::check('ADMIN', 'any', $member)) {
            return true;
        }
        if (is_null($member)) {
            return false;
        }

        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            if ($this->checkRelationPermission(RepoItem::RELATION_AUTHOR, 'GENERATE DOI', $member, [Group::class => $group])) {
                return true;
            }
            $repoTypes = Constants::MAIN_REPOTYPES;
            foreach ($repoTypes as $repoType) {
                if ($this->checkRelationPermission($repoType, 'GENERATE DOI', $member, [Group::class => $group])) {
                    return true;
                }
            }
        }

        return false;
    }

    public
    function canPublish($member = null, $context = []) {
        if (Permission::check('ADMIN', 'any', $member)) {
            return true;
        }

        if (is_null($member)) {
            return false;
        }

        if ($member->isWorksAdmin()) {
            return true;
        }

        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            if ($this->checkRelationPermission(RepoItem::RELATION_AUTHOR, 'PUBLISH', $member, [Group::class => $group])) {
                return true;
            }
            $repoTypes = Constants::ALL_REPOTYPES;
            foreach ($repoTypes as $repoType) {
                if ($this->checkRelationPermission($repoType, 'PUBLISH', $member, [Group::class => $group])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canDestroyFromTrash($member = null, $context = []) {
        if (is_null($member)) {
            return false;
        }

        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            if ($this->checkRelationPermission(RepoItem::RELATION_AUTHOR, 'DESTROY', $member, [Group::class => $group])) {
                return true;
            }
            if ($this->checkRelationPermission("TRASH", 'DESTROY', $member, [Group::class => $group])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $value
     * @throws Exception
     * Ensures a @see Template exists for the given RepoType
     */
    function setRepoType($value) {
        if ($this->Institute() && $repoType = $this->RepoType) {
            if ($value != $repoType) {
                throw new Exception('Cannot change repoType of repoItem');
            }
        }
        parent::setField('RepoType', $value);
    }

    public
    function providePermissions() {
        $actionsOnExistingObject = ['VIEW', 'DELETE', 'PUBLISH', 'EDIT', 'GENERATE DOI'];

        $repoTypes = Constants::ALL_REPOTYPES;
        $permissionsForAll = [];
        foreach ($repoTypes as $repoType) {
            $permissionsForAll = array_merge($permissionsForAll, $this->provideRelationaryPermissions($repoType, "a $repoType RepoItem", ['CREATE']));
            $permissionsForAll = array_merge($permissionsForAll, $this->provideRelationaryPermissions($repoType, "all $repoType RepoItems", ['DELETE', 'PUBLISH', 'EDIT']));
        }
        $baseRepoTypes = Constants::MAIN_REPOTYPES;
        foreach ($baseRepoTypes as $baseRepoType) {
            $permissionsForAll = array_merge($permissionsForAll, $this->provideRelationaryPermissions($baseRepoType, "for all $baseRepoType RepoItems", ['GENERATE DOI']));
        }

        $statuses = ["Draft", "Published", "Submitted", "Approved", "Declined", "Embargo"];
        foreach ($statuses as $status) {
            $permissionsForAll = array_merge($permissionsForAll, $this->provideRelationaryPermissions($status, "all $status RepoItems", ['VIEW']));
        }
        $permissionsForAll = array_merge($permissionsForAll, $this->provideRelationaryPermissions(RepoItem::RELATION_ARCHIVED, "all archived RepoItems", ['VIEW']));
        $permissionsForAll = array_merge($permissionsForAll, $this->provideRelationaryPermissions("TRASH", "all RepoItems in trash", ['DESTROY']));

        $permissionsForOwn = $this->provideRelationaryPermissions(RepoItem::RELATION_AUTHOR, 'their own RepoItems', $actionsOnExistingObject);

        $reportPermission = $this->provideRelationaryPermissions(RepoItem::RELATION_REPORT, 'reports of RepoItems', ['VIEW']);

        $sanitizePermission = $this->provideRelationaryPermissions(RepoItem::RELATION_ALL, 'all RepoItems', ['SANITIZE']);

        return array_merge(/*$normalPermissions, $scopedPermissions, */ $permissionsForAll, $permissionsForOwn, $reportPermission, $sanitizePermission);
    }

    public
    function canCreate($member = null, $context = []) {
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

        if (is_string($this->RepoType)) {
            foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
                if ($this->checkRelationPermission($this->RepoType, 'CREATE', $member, [Group::class => $group]) &&
                    (in_array(InstituteScoper::getScopeLevel($group->InstituteID, $this->InstituteID), [InstituteScoper::SAME_LEVEL, InstituteScoper::LOWER_LEVEL]))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canDelete($member = null, $context = []) {
        if (parent::canDelete($member)) {
            return true;
        }
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }

        if (!$this->disablePublishedCheckOnDelete && $this->Status == 'Published') {
            return false;
        }

        if ($member->isWorksAdmin()) {
            return true;
        }

        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            if ($this->checkRelationPermission(RepoItem::RELATION_AUTHOR, 'DELETE', $member, [Group::class => $group]) ||
                $this->checkRelationPermission($this->RepoType, 'DELETE', $member, [Group::class => $group])) {
                return true;
            }
        }

        return false;
    }

    public function canView($member = null, $context = []) {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }
        $canView = PermissionFilter::filterThroughCanViewPermissions(InstituteScoper::getAll(RepoItem::class))->filter(['ID' => $this->dataObj()->ID])->exists();

        return $canView;
    }

    public function canReport($member = null, $context = []) {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }

        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            if ($this->checkRelationPermission(RepoItem::RELATION_REPORT, 'VIEW', $member, [Group::class => $group])) {
                return true;
            }
        }
        return false;
    }

    public function canCopy($member = null, $context = []) {
        $repoItemStub = new RepoItem();
        $repoItemStub->InstituteID = $this->InstituteID;
        $repoItemStub->RepoType = $this->RepoType;
        return $repoItemStub->canCreate($member);
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

        if (in_array($this->Status, ['Published', 'Approved', 'Embargo'])) {
            if (!$this->canPublish($member)) {
                return false;
            }
        }

        if ($member->isWorksAdmin()) {
            return true;
        }


        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            if ($this->checkRelationPermission(RepoItem::RELATION_AUTHOR, 'EDIT', $member, [Group::class => $group]) ||
                $this->checkRelationPermission($this->RepoType, 'EDIT', $member, [Group::class => $group])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     * Automatically connects Template of Insitute based on RepoItemType
     */
    protected function onBeforeWrite() {
        Logger::debugLog("Entered onBeforeWrite() of RepoItem: " . $this->Uuid);
        // prevent validation when migrating or when editing in the cms
        if ($this->Status == 'Migrated' || Controller::curr() == RepoItemModelAdmin::class) {
            parent::onBeforeWrite();
            return;
        }
        $member = Security::getCurrentUser();

        // Set owner to current member
        if ($this->OwnerID == 0) {
            $this->OwnerID = $member->ID;
        }

        if (!$this->RepoType) {
            throw new Exception('Cannot have a RepoItem without a RepoType');
        }

        if (!$this->Institute()->exists()) {
            throw new Exception("Cannot have a RepoItem without contextual institute");
        }

        $canCreate = false;
        if (Permission::check('ADMIN', 'any', $member) || $member->isWorksAdmin()) {
            $canCreate = true;
        } else {
            foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
                if ($this->checkRelationPermission($this->RepoType, 'CREATE', $member, [Group::class => $group])) {
                    $canCreate = true;
                }
            }
        }

        if (!$canCreate && $this->ID == 0) {
            throw new Exception("Cannot create a Repoitem without CREATE permissions for " . $this->RepoType);
        }

        // Check if validation needs to be done
        if (in_array($this->Status, ['Published', 'Approved', 'Submitted', 'Embargo']) && $this->IsRemoved == 0 && !$this->SkipValidation) {
            Logger::debugLog("Performing validation for RepoItem: " . $this->Uuid . "; isRemoved: " . $this->IsRemoved . "; skipValidation: " . $this->SkipValidation . ";");
            $this->validateRequiredFields();

            // ignore RepoItemRepoItemFiles because it now has actual statuses instead of draft
            if ($this->RepoType !== "RepoItemRepoItemFile") {
                if (!$this->validateChannels()) {
                    throw new Exception('Cannot publish with this combination of channels');
                }
            }

//            if (!$this->isChanged('Status')) {
//                throw new Exception('Cannot change ' . $this->Status . ' RepoItem, set status to Draft');
//            }
            if (!$this->EmbargoDate) {
                $this->EmbargoDate = date('Y-m-d');
            }
        }

        $this->IsPublic = false;

        if ($this->exists() && $this->Status == 'Published') {
            $amountOfPublicChannels = RepoItemMetaFieldValue::get()
                ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.ID = SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID')
                ->innerJoin('SurfSharekit_MetaField', 'SurfSharekit_MetaField.ID = SurfSharekit_RepoItemMetaField.MetaFieldID')
                ->innerJoin('SurfSharekit_RepoItem', 'SurfSharekit_RepoItem.ID = SurfSharekit_RepoItemMetaField.RepoItemID')
                ->where(['SurfSharekit_RepoItemMetaFieldValue.IsRemoved' => 0, 'SurfSharekit_RepoItemMetaFieldValue.Value' => 1, 'SurfSharekit_RepoItem.ID' => $this->ID, 'SurfSharekit_MetaField.SystemKey' => 'PublicChannel'])->count();
            if ($amountOfPublicChannels) {
                $this->IsPublic = true;
            } else {
                $this->IsPublic = false;
            }
        }

        if (in_array($this->Status, ['Published'])) {
            $this->IsHistoricallyPublished = true;
        }
        parent::onBeforeWrite();
    }

    /**
     * @param Member $member
     * @return bool
     */
    private
    function isPublicationRecord(Member $member) {
        return $this->RepoType == 'PublicationRecord';
    }

    /**
     * @param Member $member
     * @return bool
     */
    private
    function isDataset(Member $member) {
        return $this->RepoType == 'Dataset';
    }

    /**
     * @param Member $member
     * @return bool
     */
    private
    function isProject(Member $member) {
        return $this->RepoType == 'Project';
    }

    /**
     * @param Member $member
     * @return bool
     */
    private
    function isResearchObject(Member $member) {
        return $this->RepoType == 'ResearchObject';
    }

    /**
     * @param Member $member
     * @return bool
     */
    private
    function isLearningObject(Member $member) {
        return $this->RepoType == 'LearningObject';
    }

    /**
     * @param Member $member
     * @return bool
     */
    private
    function isRepoItemPerson(Member $member) {
        return $this->RepoType == 'RepoItemPerson';
    }

    /**
     * @param Member $member
     * @return bool
     */
    private
    function isRepoItemLink(Member $member) {
        return $this->RepoType == 'RepoItemLink';
    }

    /**
     * @param Member $member
     * @return bool
     */
    private
    function isRepoItemLearningObject(Member $member) {
        return $this->RepoType == 'RepoItemLearningObject';
    }

    /**
     * @param Member $member
     * @return bool
     */
    private
    function isRepoItemResearchObject(Member $member) {
        return $this->RepoType == 'RepoItemResearchObject';
    }

    /**
     * @param Member $member
     * @return bool
     */
    private
    function isRepoItemRepoItemFile(Member $member) {
        return $this->RepoType == 'RepoItemRepoItemFile';
    }

    /**
     * @param Member $member
     * @return bool if $member is the author of this RepoItem
     */
    private
    function isAuthor(Member $member) {
        return $this->OwnerID == $member->ID;
    }

    /**
     * @param Member $member
     * @return bool if $member is the CoAuthor of this RepoItem
     */
    private
    function isCoAuthor(Member $member) {
        return false;
    }

    private
    function isEmbargo(Member $member) {
        return $this->Status == 'Embargo';
    }

    private
    function isArchived(Member $member) {
        return $this->IsArchived;
    }

    private function isTrash(Member $member) {
        return $this->IsRemoved;
    }

    private function isAll(Member $member) {
        return true;
    }

    /**
     * @param $member
     * @return bool if the repoitem is part a lower scope of that of $member
     */
    public
    function isLowerlevel($member, $context) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            return InstituteScoper::getScopeLevel($group->InstituteID, $this->InstituteID) == InstituteScoper::LOWER_LEVEL;
        }
        return false;
    }

    /**
     * @param $member
     * @return bool if the repoitem is part the same scope of that of $member
     */

    public
    function isSameLevel($member, $context) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            return InstituteScoper::getScopeLevel($group->InstituteID, $this->InstituteID) == InstituteScoper::SAME_LEVEL;
        }
        return false;
    }

    public
    function isReport($member, $context) {
        return true;
    }

    public
    function getMemberFullName() {
        return MemberHelper::getMemberFullName($this->Owner());
    }

    public
    function getAuthorName() {
        return $this->Owner()->Name;
    }

    public
    function getPublicURL() {
        return Environment::getEnv('FRONTEND_BASE_URL') . '/public/' . $this->Uuid;
    }

    public
    function getFrontEndURL() {
        return Environment::getEnv('FRONTEND_BASE_URL') . '/publications/' . $this->Uuid;
    }

    /**
     * @param $value
     * Can be used during creation to copy meta field values and such
     * @throws Exception
     */
    public
    function setCopyFrom($value) {
        if ($this->isInDB()) {
            throw new Exception('RepoItem already exists, cannot be based on another repoItem');
        }
        if (!UUID::isValid($value)) {
            throw new Exception('copyFrom should include a valid RepoItem ID');
        }
        $repoItemToBaseOn = UuidExtension::getByUuid(RepoItem::class, $value);
        if (!$repoItemToBaseOn || !$repoItemToBaseOn->exists()) {
            throw new Exception('copyFrom is not a valid RepoItem ID');
        }

        if (!$repoItemToBaseOn->canView(Security::getCurrentUser()) || !$repoItemToBaseOn->canCopy(Security::getCurrentUser())) {
            throw new Exception('No permission to copy RepoItem');
        }

        $this->RepoType = $repoItemToBaseOn->RepoType;
        $this->OwnerID = Security::getCurrentUser()->ID;
        $this->InstituteID = $repoItemToBaseOn->InstituteID;
        $this->IsRemoved = $repoItemToBaseOn->IsRemoved;
        $this->write();
        foreach ($repoItemToBaseOn->RepoItemMetaFields()->filter(['MetaField.IsCopyable' => 1]) as $repoItemMetaField) {
            $copyOfRepoItemMetaField = $repoItemMetaField->duplicate(false);
            $copyOfRepoItemMetaField->RepoItemID = $this->ID;
            $copyOfRepoItemMetaField->assignNewUuid();
            $copyOfRepoItemMetaField->write();
            foreach ($repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => false]) as $repoItemMetaFieldValue) {
                $copyOfRepoItemMetaFieldValue = $repoItemMetaFieldValue->duplicate(false);
                $copyOfRepoItemMetaFieldValue->RepoItemMetaFieldID = $copyOfRepoItemMetaField->ID;
                $copyOfRepoItemMetaFieldValue->assignNewUuid();
                if ($copyOfRepoItemMetaField->MetaField()->AttributeKey === 'Title') {
                    switch ($repoItemToBaseOn->Language) {
                        case 'nl':
                            $copyOfRepoItemMetaFieldValue->Value = 'Kopie van: ' . $copyOfRepoItemMetaFieldValue->Value;
                            break;
                        case 'en':
                        default:
                            $copyOfRepoItemMetaFieldValue->Value = 'Copy of: ' . $copyOfRepoItemMetaFieldValue->Value;
                            break;
                    }
                }
                $copyOfRepoItemMetaFieldValue->write();
            }
        }
    }

    /**
     * @return mixed the implied Template due to this RepoItem being connected to an institute
     */
    public function Template() {
        if (!$this->Institute()->exists()) {
            throw new Exception('Cannot have a RepoItem without institute');
        }
        $template = $this->Institute()->Templates()->filter(['RepoType' => $this->RepoType])->first();
        if (!$template) {
            throw new Exception("Institute " . $this->Institute->Title . ' does not have a ' . $this->RepoType . ' template');
        }
        return $template;
    }

    function getLoggedInUserPermissions() {
        $loggedInMember = Security::getCurrentUser();
        $permission = [
            'canView' => $this->canView($loggedInMember),
            'canEdit' => $this->canEdit($loggedInMember),
            'canCopy' => $this->canCopy($loggedInMember),
            'canDelete' => $this->canDelete($loggedInMember),
            'canPublish' => $this->canPublish($loggedInMember),
            'canGenerateDoi' => $this->canGenerateDoi($loggedInMember),
        ];
        return $permission;
    }

    function setIsRemovedFromApi($value) {
        if ($this->IsRemoved != $value) { //Soft removing
            if (!$this->canDelete(Security::getCurrentUser())) {
                throw new Exception('Changing isRemoved would cause a deletion or reset, you have no permission to do so');
            }

            if ($value === 1 && $this->Status == 'Published') {
                throw new Exception('Cannot remove published item, please unpublish beforehand');
            }

            $this->IsRemoved = $value;
        }
    }

    public
    function canConnectToInstitute($institute) {
        return !$this->Institute()->exists();
    }

    public
    function canAddChild($child) {
        return false;
    }

    public
    function canRemoveChild($child) {
        return false;
    }

    public
    function canAddParent($parent) {
        return false;
    }

    public
    function canRemoveParent($parent) {
        return false;
    }

    public
    function Parents() {
        return RepoItem::get()
            ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemID = SurfSharekit_RepoItem.ID')
            ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
            ->innerJoin('SurfSharekit_MetaField', 'SurfSharekit_RepoItemMetaField.MetaFieldID = SurfSharekit_MetaField.ID')
            ->where("SurfSharekit_RepoItemMetaField.RepoItemID = $this->ID")
            ->where("SurfSharekit_RepoItemMetaFieldValue.IsRemoved = 0")
            ->where("SurfSharekit_MetaField.SystemKey = 'ContainsParents'");
    }

    public
    function Children() {
        $connectionRepoItemIds = RepoItem::get()
            ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemID = SurfSharekit_RepoItem.ID')
            ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
            ->innerJoin('SurfSharekit_MetaField', 'SurfSharekit_RepoItemMetaField.MetaFieldID = SurfSharekit_MetaField.ID')
            ->where("SurfSharekit_RepoItemMetaField.RepoItemID = $this->ID")
            ->where("SurfSharekit_RepoItemMetaFieldValue.IsRemoved = 0")
            ->where("SurfSharekit_MetaField.SystemKey = 'ContainsChildren'");
        $childIds = [];
        foreach ($connectionRepoItemIds as $childConnection) {
            $repoItemFieldsAnswer = $childConnection->RepoItemMetaFields()->filter(['RepoItemMetaFieldValues.RepoItemID:not' => 'NULL'])->first();
            if ($repoItemFieldsAnswer && $repoItemFieldsAnswer->exists()) {
                $repoItemFieldsAnswerValue = $repoItemFieldsAnswer->RepoItemMetaFieldValues()->filter('IsRemoved', 0)->first();
                if ($repoItemFieldsAnswerValue && $repoItemFieldsAnswerValue->exists()) {
                    $childIds[] = $repoItemFieldsAnswerValue->ID;
                }
            }
        }
        if (count($childIds)) {
            return RepoItem::get()->filter('ID', $childIds);
        } else {
            return RepoItem::get()->where('1=0');
        }
    }

    public
    static function updateAttributeBasedOnMetafield($value, $repoItemMetaFieldValueWhereFilter) {
        $attributeKeyMap = [
            'Title' => 'Title',
            'Subtitle' => 'Subtitle',
            'InstituteID' => 'InstituteID',
            'Alias' => 'Alias'
        ];

        foreach ($attributeKeyMap as $attributeKey => $repoItemField) {
            DB::prepared_query("UPDATE SurfSharekit_RepoItem as ri 
                                    INNER JOIN SurfSharekit_RepoItemMetaField AS rimf ON rimf.RepoItemID = ri.ID 
                                    INNER JOIN SurfSharekit_RepoItemMetaFieldValue AS rimfv ON rimfv.RepoItemMetaFieldID = rimf.ID
                                    INNER JOIN SurfSharekit_MetaField AS rim ON rim.ID = rimf.MetaFieldID
                                    SET ri.$repoItemField = ?
                                    WHERE rim.AttributeKey = '$attributeKey' AND rimfv.IsRemoved = 0 AND rimfv.$repoItemMetaFieldValueWhereFilter", [$value])->value();
        }
        $updatedRepoItems = RepoItem::get()
            ->innerJoin('SurfSharekit_RepoItemMetaField', 'rimf.RepoItemID = SurfSharekit_RepoItem.ID', 'rimf')
            ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'rimfv.RepoItemMetaFieldID = rimf.ID', 'rimfv')
            ->innerJoin('SurfSharekit_MetaField', 'rim.ID = rimf.MetaFieldID', 'rim')
            ->where(["rim.AttributeKey" => $attributeKey, 'rimfv.IsRemoved' => 0, "rimfv.$repoItemMetaFieldValueWhereFilter"]);

        foreach ($updatedRepoItems as $repoItem) {
            RepoItemSummary::updateFor($repoItem);
            SearchObject::updateForRepoItem($repoItem);
        }
    }

    public function publish() {
        if ($this->RepoType == "RepoItemRepoItemFile") {
            $this->touchParent();
        }

        $this->setField('Status', 'Published');
        $this->write();
    }

    private
    function updateArchiveState() {
        $archiveRepoItemMetaField = $this->RepoItemMetaFields()->filter(['MetaField.SystemKey' => 'Archive'])->first();
        if ($archiveRepoItemMetaField && $archiveRepoItemMetaField->exists()) {
            $archiveValueObj = $archiveRepoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($archiveValueObj && $archiveValueObj->exists()) {
                if ($archiveValueObj->Value === '1') {
                    $this->IsArchived = 1;
                } else {
                    $this->IsArchived = 0;
                }
            } else {
                $this->IsArchived = 0;
            }

            $this->IsArchivedUpdated = true;
            $this->write();
        }
    }

    /**
     * Function to retrieve all persons that can publish this RepoItem
     * Only returns Persons with the following roles: Supporter, Siteadmin
    */
    function getSupportersAndSiteAdminsWhoCanPublish() : DataList {
        if (in_array($this->RepoType, Constants::MAIN_REPOTYPES)) {
            $permission = 'REPOITEM_PUBLISH_' . strtoupper($this->RepoType);
        }
        $permissionsChecks = "(Permission.Code = '$permission' OR PermissionRoleCode.Code = '$permission')";
        $roleChecks = "(PermissionRole.Title = 'Supporter' OR PermissionRole.Title = 'Siteadmin')";
        $persons= Person::get()
            ->innerJoin('Group_Members', 'Group_Members.MemberID = SurfSharekit_Person.ID')
            ->innerJoin('Group', 'Group_Members.GroupID = Group.ID')
            ->innerJoin('(' . InstituteScoper::getInstitutesOfUpperScope([$this->InstituteID])->sql() . ')', 'gi.ID = Group.InstituteID', 'gi')
            ->leftJoin('Group_Roles', 'Group_Roles.GroupID = Group.ID')
            ->leftJoin('PermissionRoleCode', 'PermissionRoleCode.RoleID = Group_Roles.PermissionRoleID')
            ->leftJoin('PermissionRole', 'PermissionRole.ID = Group_Roles.PermissionRoleID')
            ->leftJoin('Permission', 'Permission.GroupID = Group_Roles.GroupID')
            ->where($permissionsChecks . ' AND ' . $roleChecks);
        return $persons;
    }

    static function getPermissionCases() {
        $member = Security::getCurrentUser();

        return [
            'REPOITEM_VIEW_AUTHOR' => "SurfSharekit_RepoItem.OwnerID = $member->ID",

            //View certain Statuses
            'REPOITEM_VIEW_EMBARGO' => "SurfSharekit_RepoItem.Status = 'Embargo'",
            'REPOITEM_VIEW_DECLINED' => "SurfSharekit_RepoItem.Status = 'Declined'",
            'REPOITEM_VIEW_APPROVED' => "SurfSharekit_RepoItem.Status = 'Approved'",
            'REPOITEM_VIEW_SUBMITTED' => "SurfSharekit_RepoItem.Status IN ('Submitted', 'Revising')",
            'REPOITEM_VIEW_DRAFT' => "SurfSharekit_RepoItem.Status = 'Draft'",

            'REPOITEM_VIEW_PUBLISHED' => "SurfSharekit_RepoItem.Status = 'Published' AND SurfSharekit_RepoItem.IsArchived = 0",
            'REPOITEM_VIEW_ARCHIVED' => "SurfSharekit_RepoItem.IsArchived = 1"
        ];
    }

    private function updateChildrenToIncludeParent($repoItem) {
        $repoItemMetaFieldChildren = $this->RepoItemMetaFields()->filter('MetaField.SystemKey', 'ContainsChildren')->first();
        if (!$repoItemMetaFieldChildren || !$repoItemMetaFieldChildren->exists()) {
            return;
        }

        $childIdList = [];
        //Go through each betweemRepoItem connection (i.e. RepoItemLearningObject)
        foreach ($repoItemMetaFieldChildren->RepoItemMetaFieldValues()->filter('IsRemoved', 0) as $betweenRepoItemValues) {
            //Get the corresponding LearningObject behind the RepoItemLearningObject
            $childRepoItemValue = $betweenRepoItemValues->RepoItem()->RepoItemMetaFields()->filter('RepoItemMetaFieldValues.RepoItemID:not', null)->first();
            if ($childRepoItemValue) {
                $childRepoItemValue = $childRepoItemValue->RepoItemMetaFieldValues()->filter('IsRemoved', 0)->first();
            }

            if (!$childRepoItemValue || !$childRepoItemValue->exists()) {
                continue;
            }
            $childRepoItem = $childRepoItemValue->RepoItem();
            $childIdList[] = $childRepoItem->ID;

            //Child LearningObject needs a relation to its parents
            $parentsMetaFieldInChild = RepoItemMetaField::get()->filter('RepoItemID', $childRepoItem->ID)->filter('MetaField.SystemKey', 'ContainsParents')->first();

            if (!$parentsMetaFieldInChild || !$parentsMetaFieldInChild->exists()) {
                //Child doesn't have a list of parentconnections yet, create a new one (i.e. list of RepoItemLearningObjects)

                $parentsMetaFieldInChild = new RepoItemMetaField();
                $parentsMetaFieldInChild->setField('RepoItemID', $childRepoItem->ID);
//                $parentsMetaFieldInChild->setField('MetaFieldID', MetaField::get()->filter('SystemKey', 'ContainsParents')->first()->ID);
                $parentField = MetaField::get()->filter(['ParentRepoType' => $repoItem->RepoType, 'SystemKey' => 'ContainsParents']);
                if ($parentField->count()) {
                    $metaFieldID = $parentField->first()->ID;
                    $parentsMetaFieldInChild->setField('MetaFieldID', $metaFieldID);
                    $parentsMetaFieldInChild->write();
                }
            }

            //Check whether the betweemRepoItem already has a connection to $this as parent
            $thisInChild = $parentsMetaFieldInChild->RepoItemMetaFieldValues()->filter('RepoItemID', $this->ID)->first();
            if (!($thisInChild && $thisInChild->exists())) {
                //Not a connection to this as parent, make new connection
                $thisInChild = new RepoItemMetaFieldValue();
                $thisInChild->RepoItemID = $this->ID;
                $thisInChild->RepoItemMetaFieldID = $parentsMetaFieldInChild->ID;
                $thisInChild->IsRemoved = false;
                $thisInChild->write();
            } else {
                $thisInChild->DisableForceWriteRepoItem = true;
                $thisInChild->IsRemoved = false;
                $thisInChild->write();
                $thisInChild->DisableForceWriteRepoItem = false;
            }
        }

        if (count($childIdList)) {
            foreach (RepoItemMetaFieldValue::get()->filter('RepoItemID', $this->ID)->filter('RepoItemMetaField.RepoItemID:not', $childIdList)->filter('RepoItemMetaField.MetaField.SystemKey', 'ContainsParents') as $oldRelation) {
                $oldRelation->setField('IsRemoved', true);
                $oldRelation->DisableForceWriteRepoItem = true;
                $oldRelation->write();
                $oldRelation->DisableForceWriteRepoItem = false;
            }
        } else {
            foreach (RepoItemMetaFieldValue::get()->filter('RepoItemID', $this->ID)->filter('RepoItemMetaField.MetaField.SystemKey', 'ContainsParents') as $oldRelation) {
                $oldRelation->setField('IsRemoved', true);
                $oldRelation->DisableForceWriteRepoItem = true;
                $oldRelation->write();
                $oldRelation->DisableForceWriteRepoItem = false;
            }
        }
    }

    public function getSummary() {
        return RepoItemSummary::generateSummaryFor($this);
    }

    public
    function getPersonsInvolved() {
        $authors = [];
        foreach ($this->RepoItemMetaFields()->filter('MetaField.MetaFieldType.Title', 'PersonInvolved') as $repoItemMetaField) {
            foreach ($repoItemMetaField->RepoItemMetaFieldValues() as $value) {
                $authorRepoItem = $value->RepoItem();
                if ($authorRepoItem && $authorRepoItem->exists()) {
                    $authors[] = [
                        'name' => $authorRepoItem->Title
                    ];
                }
            }
        }
        return $authors;
    }

    // Todo: Rewrite to make use of the Webhook DataObject instead -> These objects should be processed async
    private function pushRepoItemToSubscribedClients()  {
        $institute = $this->Institute();
        if ($institute) {
            $channels = Channel::get()->filter([
                'PushEnabled' => true
            ]);
            if (count($channels) != 0) {
                $listOfRequests = [];

                foreach ($channels as $channel) {
                    $channelInstitutes = $channel->Institutes()->getIDList();
                    if ((count($channelInstitutes) && array_search($institute->ID, $channelInstitutes)) || !count($channelInstitutes)) {

                        if ($isXML = $channel->Protocol()->SystemKey == 'OAI-PMH') {
                            $responseDate = DateHelper::iso8601zFromString(date('Y-m-d H:i:s'));
                            $rootNode = new SimpleXMLElement('<OAI-PMH></OAI-PMH>');
                            $rootNode->addAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/');
                            $rootNode->addChild('responseDate', $responseDate);
                            GetRecordNode::addTo($rootNode, null, $channel, $this);

                            $postData = $rootNode;

                            $headers = [
                                'content-type' => 'text/xml'
                            ];

                        } else {
                            $description = new ExternalRepoItemChannelJsonApiDescription($channel);

                            // Get all items that can get through this channel's filters (including cached items)
                            $repoItemsExposedToChannel = $description->getAllItemsToDescribe(RepoItem::get(), true);
                            $repoItem = $repoItemsExposedToChannel->filter(["ID" => $this->ID]);
//                            Logger::debugLog($repoItem->sql(), true);
                            if (!($repoItem && $repoItem->exists())) {
                                continue; // Skip if RepoItem cannot get through channel filters and does not exist in cache
                            }

                            $dataDescription = [];
                            $dataDescription[JsonApi::TAG_ATTRIBUTES] = $description->describeAttributesOfDataObject($this);
                            $dataDescription[JsonApi::TAG_TYPE] = DataObjectJsonApiEncoder::describeTypeOfDataObject($description);
                            if ($metaInformation = $description->describeMetaOfDataObject($this)) {
                                $dataDescription[JsonApi::TAG_META] = $metaInformation;

                                $cachedAttributesOfObject = Cache_RecordNode::get()->filter(['Endpoint' => 'JSON:API', 'ProtocolID' => $channel->ProtocolID, 'ChannelID' => $channel->ID, 'RepoItemID' => $this->ID])->first();
                                if ($cachedAttributesOfObject) {
                                    $array = json_decode("$cachedAttributesOfObject->Data", true);
                                    if (array_key_exists('meta', $array)) {
                                        if (array_key_exists('status', $array['meta']) && array_key_exists('status', $metaInformation)) {
                                            if ($array['meta']['status'] == 'deleted' && $metaInformation['status'] == 'deleted') {
                                                // repoItem has deleted status in cache,
                                                // it's description also has status deleted,
                                                // meaning the deleted push has already been send to this channel, continue...
                                                continue;
                                            }
                                        }
                                    }
                                }
                            }
                            $dataDescription[JsonApi::TAG_ID] = DataObjectJsonApiEncoder::describeIdOfDataObject($this);
                            $description->cache($this, $dataDescription);
                            // Update/create cache if this is already cached or if this is an uncached item to describe

                            $postData = $dataDescription;

                            $headers = [
                                'content-type' => 'application/vnd.api+json'
                            ];

                        }
                        $client = new Client();

                        $listOfRequests[] = $client->postAsync(
                            $channel->CallbackUrl,
                            [
                                RequestOptions::HEADERS => $headers,
                                RequestOptions::BODY => $isXML ? $postData->asXML() : json_encode($postData)
                            ]
                        );

                    }
                }

                $responses = Promise\Utils::settle($listOfRequests)->wait();
            }
        }
    }


    public function memberHasRoleWithAssociatedInstitute(array $array): bool {
        if (null === $member = Security::getCurrentUser()) {
            return false;
        }

        if (null !== $institute = $this->Institute) {
            /** @var Institute $institute */
            $rootInstitute = $institute->getRootInstitute();
            $groups = $rootInstitute->Groups()->filter('Roles.Title', $array);

            $inGroup = false;
            foreach ($groups as $group) {
                /** @var Group $group */
                $inGroup = $member->inGroup($group->ID);
                if ($inGroup) {
                    break;
                }
            }

            return $inGroup;
        }

        return false;
    }

    /**
     * @return DataList
     */
    public function getUncompletedReviewTasks(): DataList {
        $member = Security::getCurrentUser();
        if ($member) {
            return $this->Tasks()->filter([
                "Type" => "REVIEW",
                "State" => "INITIAL",
                "OwnerID" => $member->ID
            ]);
        }
        return Task::get()->filter(["ID" => 0]);
    }

    public function getTranslatedType() {
        $types = [
            "PublicationRecord" => [
                "nl" => "Afstudeerwerk of stageverslag",
                "en" => "Thesis or internship report"
            ],
            "LearningObject" => [
                "nl" => "Leermateriaal",
                "en" => "Learning material"
            ],
            "ResearchObject" => [
                "nl" => "Onderzoekspublicatie",
                "en" => "Research publication"
            ],
            "Dataset" => [
                "nl" => "Dataset",
                "en" => "Dataset"
            ],
            "Project" => [
                "nl" => "Project",
                "en" => "Project"
            ],
            "RepoItemRepoItemFile" => [
                "nl" => "Bestand",
                "en" => "File"
            ],
            "RepoItemLearningObject" => [
                "nl" => "Leermateriaal",
                "en" => "Learning object"
            ],
            "RepoItemLink" => [
                "nl" => "Link",
                "en" => "Link"
            ],
            "RepoItemPerson" => [
                "nl" => "Persoon",
                "en" => "Person"
            ],
            "RepoItemResearchObject" => [
                "nl" => "Onderzoekspublicatie",
                "en" => "Research publication"
            ]
        ];

        return $types[$this->RepoType] ?? "";
    }

    public function touchParent() {

        if (null !== $parent = $this->getActiveParent()) {
            $parent->touch();
            $parent->touchParent();
        }
    }

    public function touch() {
        $this->LastEdited = (new \DateTime())->format('Y-m-d H:i:s');

        $this->SkipValidation = true;
        $this->write();
    }

    /**
     * @param bool $excludeDeleted
     * @return DataList
     */
    public function getAllRepoItemMetaFieldValues(bool $excludeDeleted = true): DataList {
        $filter = [
            "ri.ID" => $this->ID,
        ];

        if ($excludeDeleted) {
            $filter["SurfSharekit_RepoItemMetaFieldValue.IsRemoved"] = false;
        }

        return RepoItemMetaFieldValue::get()
            ->innerJoin("SurfSharekit_RepoItemMetaField", "SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = rimf.ID", "rimf")
            ->innerJoin("SurfSharekit_RepoItem", "rimf.RepoItemID = ri.ID", "ri")
            ->where($filter);
    }
}
