<?php

namespace SurfSharekit\Models;

use DataObjectJsonApiEncoder;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Versioned\Versioned;

/**
 * Class RepoItemMetaField
 * @package SurfSharekit\Models
 * @method HasManyList RepoItemMetaFieldValues()
 * @method RepoItem RepoItem()
 * DataObject representing an answer on a @see MetaField
 * This Object is a collection of a list of actual values @see RepoItemMetaFieldValue , so to support multiselect answers for example
 */
class RepoItemMetaField extends DataObject {


    private static $extensions = [
        Versioned::class . '.versioned',
    ];


    private static $table_name = 'SurfSharekit_RepoItemMetaField';

    private static $has_one = [
        'RepoItem' => RepoItem::class,
        'MetaField' => MetaField::class
    ];

    private static $has_many = [
        'RepoItemMetaFieldValues' => RepoItemMetaFieldValue::class
    ];

    private static $cascade_deletes = [
        'RepoItemMetaFieldValues'
    ];

    private static $summary_fields = ['Title'];

    public function getTitle() {
        return 'RepoItemMetaField for: ' . $this->MetaField->Title;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $repoItem = $this->RepoItem();
        if ($repoItem && $repoItem->exists() && !empty($repoItem->Title)) {
            $repoItemTitle = $repoItem->Title;
        } else {
            $repoItemTitle = '- no title -';
        }
        $repoItemDisplayField = ReadonlyField::create('DisplayRepoItem', 'RepoItem', $repoItemTitle);
        $repoItemHiddenField = HiddenField::create('RepoItemID', 'RepoItem', $this->RepoItemID);
        $fields->replaceField('RepoItemID', $repoItemDisplayField);
        $fields->insertAfter('RepoItemID', $repoItemHiddenField);

        /** @var GridField $repoItemMetaFieldValuesGridField */
        $repoItemMetaFieldValuesGridField = $fields->dataFieldByName('RepoItemMetaFieldValues');
        if($repoItemMetaFieldValuesGridField) {
            $repoItemMetaFieldValuesGridFieldConfig = $repoItemMetaFieldValuesGridField->getConfig();
            $repoItemMetaFieldValuesGridFieldConfig->removeComponentsByType(new GridFieldAddExistingAutocompleter());
        }

        return $fields;
    }

    /**
     * @return array , JsonApiDescription of all answersValues of this RepoItemMetaField
     */
    public function getJsonAPIDescription() {
        $answerValues = [];
        foreach ($this->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]) as $answer) {
            $summary = $answer->RelatedObjectSummary;
            if (!$summary) {
                $summary = $answer->PersonSummary;
            }
            $selectedOption = $answer->MetaFieldOption();
            $answerValues[] = [
                'repoItemID' => $answer->RepoItemUuid,
                'optionKey' => $selectedOption ? DataObjectJsonApiEncoder::getJSONAPIID($selectedOption) : null,
                'value' => $this->MetaField()->MetaFieldType()->JSONEncodedStorage ? json_decode($answer->Value) : $answer->Value,
                'summary' => $summary,
                'repoItemFileID' => $answer->RepoItemFileUuid,
                'personID' => $answer->PersonUuid,
                'instituteID' => $answer->InstituteUuid,
                'sortOrder' => $answer->SortOrder];
        }
        return [
            "fieldKey" => DataObjectJsonApiEncoder::getJSONAPIID($this->MetaField()),
            'values' => $answerValues
        ];
    }

    /**
     * @return array|mixed|null
     * Method to summarize all values of this RepoItemMetaField
     */
    public function getValues() {
        $answerValues = [];

        foreach ($this->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]) as $answer) {
            $answerValues[] = $answer->SummaryFieldValue;
        }
        if (count($answerValues) > 1) {
            return $answerValues;
        }

        return isset($answerValues[0]) ? $answerValues[0] : null;
    }

    /**
     * @return array
     * Method to summarize all values of this RepoItemMetaField
     */
    public function getObjValues() {
        $answerValues = [];

        /** @var RepoItemMetaFieldValue $answer */
        foreach ($this->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]) as $answer) {
            $answerValues[] = $answer->getRelatedObjectSummary();
        }

        return $answerValues;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }

}