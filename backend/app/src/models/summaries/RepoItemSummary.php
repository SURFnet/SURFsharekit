<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SurfSharekit\Api\PermissionFilter;
use SurfSharekit\Models\Helper\Constants;

class RepoItemSummary extends DataObject implements PermissionProvider {
    private static $table_name = 'SurfSharekit_RepoItemSummary';

    private static $db = [
        'Status' => 'Enum(array("Draft", "Published", "Submitted", "Approved", "Declined", "Embargo", "Migrated"), "Draft")',
        'IsPublic' => 'Boolean(0)',
        'IsRemoved' => 'Boolean(0)',
        'RepoItemLastEdited' => 'Datetime',
        'Summary' => 'Text'
    ];

    private static $has_one = [
        'RepoItem' => RepoItem::class,
        'Owner' => Person::class,
        'Institute' => Institute::class
    ];

    private static $default_sort = 'RepoItemLastEdited DESC';

    private static $indexes = [
        'Status' => true
    ];

    private static $summary_fields = [
        'Summary' => 'Summary'
    ];

    protected function onAfterWrite() {
        parent::onAfterWrite();
        if ($this->isChanged('OwnerID') || $this->isChanged('InstituteID')) {
            ScopeCache::removeCachedViewable(RepoItemSummary::class);
            ScopeCache::removeCachedDataList(RepoItemSummary::class);
        }
    }

    public function updateSummary() {
        $this->Summary = null;
        if (($repoItem = $this->RepoItem()) && $repoItem->exists()) {
            $this->Summary = json_encode(static::generateSummaryFor($repoItem));
        }
    }

    static function generateSummaryFor($repoItem) {
        $summaryValues = [
            'id' => $repoItem->Uuid,
            'status' => $repoItem->Status,
            'title' => $repoItem->Title,
            'repoType' => $repoItem->RepoType,
            'subtitle' => $repoItem->Subtitle,
            'lastEdited' => $repoItem->LastEdited,
            'created' => $repoItem->Created,
            'authorName' => $repoItem->Owner()->FullName,
            'permissions' => $repoItem->LoggedInUserPermissions,
            'isRemoved' => $repoItem->IsRemoved,
            'isArchived' => $repoItem->IsArchived,
            'publicationDate' => $repoItem->PublicationDate,
            'accessRight' => $repoItem->AccessRight,
            'embargoDate' => $repoItem->EmbargoDate
        ];

        if ($repoItem->RepoType == 'RepoItemPerson') {
            $personRepoItemSubtitleMetaField = $repoItem->RepoItemMetaFields()->filter(['MetaField.AttributeKey' => 'Subtitle'])->first();
            if ($personRepoItemSubtitleMetaField && $personRepoItemSubtitleMetaField->exists()) {
                $valuePart = $personRepoItemSubtitleMetaField->RepoItemMetaFieldValues()->filter('IsRemoved', 0)->first();
                if ($valuePart && $valuePart->exists()) {
                    $summaryValues['subtitleNL'] = $valuePart->MetaFieldOption()->Label_NL;
                    $summaryValues['subtitleEN'] = $valuePart->MetaFieldOption()->Label_EN;
                }
            }

            $personRepoItemExternalMetaField = $repoItem->RepoItemMetaFields()->filter(['MetaField.AttributeKey' => 'External'])->first();
            if ($personRepoItemExternalMetaField && $personRepoItemExternalMetaField->exists()) {
                /** @var RepoItemMetaFieldValue $valuePart */
                $valuePart = $personRepoItemExternalMetaField->RepoItemMetaFieldValues()->filter('IsRemoved', 0)->first();
                $summaryValues['external'] = !!($valuePart && $valuePart->exists());
            }

            $personRepoItemTitleMetaField = $repoItem->RepoItemMetaFields()->filter(['MetaField.AttributeKey' => 'Title'])->first();
            if ($personRepoItemTitleMetaField && $personRepoItemTitleMetaField->exists()) {
                $valuePart = $personRepoItemTitleMetaField->RepoItemMetaFieldValues()->filter('IsRemoved', 0)->first();
                if ($valuePart && $valuePart->exists() && $person = $valuePart->Person()) {
                    $summaryValues['person'] = $valuePart->RelatedObjectSummary;
                    $summaryValues['person']['id'] = $person->Uuid;
                    $summaryValues['person']['permissions'] = $person->LoggedInUserPermissions;
                }
            }
        }

        if ($repoItem->RepoType == 'RepoItemLink') {
            $urlRepoItemMetaField = $repoItem->RepoItemMetaFields()->filter(['MetaField.MetaFieldType.Title' => 'URL'])->first();
            if ($urlRepoItemMetaField && $urlRepoItemMetaField->exists()) {
                $linkValue = $urlRepoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
                if ($linkValue && $linkValue->exists()) {
                    $summaryValues['url'] = $linkValue->Value;
                }
            }
        }

        if ($repoItem->RepoType == 'RepoItemRepoItemFile') {
            $fileRepoItemMetaField = $repoItem->RepoItemMetaFields()->filter(['MetaField.MetaFieldType.Title' => 'File'])->first();
            if ($fileRepoItemMetaField && $fileRepoItemMetaField->exists()) {
                $fileValue = $fileRepoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
                if ($fileValue && $fileValue->exists()) {
                    $summaryValues['url'] = $fileValue->RepoItemFile()->getStreamURL();
                    $summaryValues['publicUrl'] = $fileValue->RepoItemFile()->getPublicStreamURL();
                }
            }

            $rightOfUseMetaField = $repoItem->RepoItemMetaFields()->filter(['MetaField.MetaFieldType.Key' => 'RightOfUseDropdown'])->first();
            if ($rightOfUseMetaField && $rightOfUseMetaField->exists()) {
                $value = $rightOfUseMetaField->RepoItemMetaFieldValues()->filter('IsRemoved', 0)->first();
                if ($value && $value->exists()) {
                    $summaryValues['rightOfUse'] = $value->MetaFieldOption()->Value;
                    $summaryValues['rightOfUseNL'] = $value->MetaFieldOption()->Label_NL;
                    $summaryValues['rightOfUseEN'] = $value->MetaFieldOption()->Label_EN;
                }
            }

            $multiSelectInstituteField = $repoItem->RepoItemMetaFields()->filter(['MetaField.MetaFieldType.Key' => 'MultiSelectInstitute'])->first();
            if ($multiSelectInstituteField && $multiSelectInstituteField->exists()) {
                $multiSelectInstituteFieldValues = $multiSelectInstituteField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]);

                $instituteTitles = [];
                foreach ($multiSelectInstituteFieldValues as $multiSelectInstituteFieldValue) {
                    $institute = Institute::get()->byID($multiSelectInstituteFieldValue->InstituteID);
                    if ($institute && $institute->exists()) {
                        $instituteTitles[] = $institute->Title;
                    }
                }
                $summaryValues['institutes'] = $instituteTitles;
            }
        }

        if (in_array($repoItem->RepoType, ['RepoItemLink', 'RepoItemRepoItemFile'])) {
            $importantMetaField = $repoItem->RepoItemMetaFields()->filter(['MetaField.AttributeKey' => 'Important'])->first();
            if ($importantMetaField && $importantMetaField->exists()) {
                /** @var RepoItemMetaFieldValue $valuePart */
                $valuePart = $importantMetaField->RepoItemMetaFieldValues()->filter('IsRemoved', 0)->first();
                $summaryValues['important'] = !!($valuePart && $valuePart->exists());
            }
        }

        if ($repoItem->RepoType == 'RepoItemLearningObject' || $repoItem->RepoType == 'RepoItemResearchObject') {
            $repoItemRepoItemTitleMetaField = $repoItem->RepoItemMetaFields()->filter(['MetaField.AttributeKey' => 'Title'])->first();
            if ($repoItemRepoItemTitleMetaField && $repoItemRepoItemTitleMetaField->exists()) {
                $valuePart = $repoItemRepoItemTitleMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
                if ($valuePart && $valuePart->exists() && $repoItem = $valuePart->RepoItem()) {
                    $summaryValues['repoItem'] = $repoItem->Summary;
                }
            }
        }

        if (in_array($repoItem->RepoType, ["PublicationRecord", "LearningObject", "ResearchObject", "Dataset"])) {
            /** @var RepoItem $repoItem */
            $summaryValues['includesFile'] = !!$repoItem->RepoItemMetaFields()->filter('RepoItemMetaFieldValues.RepoItem.RepoType', 'RepoItemRepoItemFile')->count();
            $summaryValues['includesUrl'] = !!$repoItem->RepoItemMetaFields()->filter('RepoItemMetaFieldValues.RepoItem.RepoType', 'RepoItemLink')->count();
        }

        $summaryValues['extra'] = [];

        foreach (MetaField::get()->filter(['SummaryKey:not' => null]) as $summaryMetaField) {
            $repoItemMetaField = RepoItemMetaField::get()->filter(["RepoItemID" => $repoItem->ID, "MetaFieldID" => $summaryMetaField->ID])->first();
            $extraSummaryKey = $summaryMetaField->SummaryKey;
            if ($extraSummaryKey != '') {
                $summaryValue = $repoItemMetaField && $repoItemMetaField->exists() ? $summaryMetaField->Values : null;
                if (!$summaryValue) {
                    //If no answers given for summary field, use default answers as summary
                    $templateMetafield = $repoItem->Template()->TemplateMetaFields()->filter('MetaFieldID', $summaryMetaField->ID)->first();
                    if ($templateMetafield && $templateMetafield->exists() && ($defaultAnswers = $templateMetafield->getDefaultJsonApiAnswerDescription()) && count($defaultAnswers['values'])) {
                        if (isset($defaultAnswers['values'][0]['value'])) {
                            $summaryValue = $defaultAnswers['values'][0]['value'];
                        }
                        if (!$summaryValue && isset($defaultAnswers['values'][0]['summary']['title'])) {
                            $summaryValue = $defaultAnswers['values'][0]['summary']['title'];
                        }
                        if (!$summaryValue && isset($defaultAnswers['values'][0]['summary']['name'])) {
                            $summaryValue = $defaultAnswers['values'][0]['summary']['name'];
                        }
                        if (!$summaryValue && isset($defaultAnswers['values'][0]['summary']['name'])) {
                            $summaryValue = $defaultAnswers['values'][0]['summary']['name'];
                        }
                        if (!$summaryValue && isset($defaultAnswers['values'][0]['summary']['value'])) {
                            $summaryValue = $defaultAnswers['values'][0]['summary']['value'];
                        }
                    }
                }
                $summaryValues['extra'][$extraSummaryKey] = $summaryValue;
            }
        }

        foreach ($repoItem->RepoItemMetaFields()->filter(['MetaField.SummaryKey:not' => null]) as $summaryMetaField) {
            $extraSummaryKey = $summaryMetaField->MetaField()->SummaryKey;
            if ($extraSummaryKey != '') {
                $summaryValues['extra'][$extraSummaryKey] = $summaryMetaField->Values;
            }
        }
        return $summaryValues;
    }

    public function getSummaryJsonDecoded() {
        if (!$this->decodedSummary) {
            $this->decodedSummary = $this->Summary ? json_decode($this->Summary, true) : [];
        }
        return $this->decodedSummary;
    }

    public function getUuid() {
        return $this->RepoItem()->Uuid;
    }

    public function canEdit($member = null) {
        return false;
    }

    public static function updateFor(RepoItem $repoItem) {
        if (!in_array($repoItem->RepoType, Constants::MAIN_REPOTYPES)) {
            return;
        }
        $repoItemSummary = RepoItemSummary::get()->filter(['RepoItemID' => $repoItem->ID])->first();
        if (!$repoItemSummary || !$repoItemSummary->exists()) {
            $repoItemSummary = new RepoItemSummary();
        }
        $repoItemSummary->RepoItemID = $repoItem->ID;
        $repoItemSummary->updateFromRepoItem($repoItem);
        $repoItemSummary->write();
    }

    public function updateFromRepoItem($repoItem = null) {
        $repoItem = $repoItem ?: $this->RepoItem();
        $this->InstituteID = $repoItem->InstituteID;
        $this->OwnerID = $repoItem->OwnerID;
        $this->IsPublic = $repoItem->IsPublic;
        $this->RepoItemLastEdited = $repoItem->LastEdited;
        $this->Status = $repoItem->Status;
        $this->IsRemoved = $repoItem->IsRemoved;
        $this->updateSummary();
    }

    public function __get($property) {
        if (stripos($property, 'Summary.') !== false) {
            $summaryDecoded = $this->getSummaryJsonDecoded();
            $accessor = str_replace('Summary.', '', $property);
            return isset($summaryDecoded[$accessor]) ? $summaryDecoded[$accessor] : null;
        } else if (stripos($property, 'RepoItem.') !== false) {
            $accessor = str_replace('RepoItem.', '', $property);
            return $this->RepoItem()->$accessor;
        } else {
            return parent::__get($property);
        }
    }

    function getLoggedInUserPermissions() {
        return [
            'canView' => true,
            'canEdit' => true,
            'canDelete' => true,
            'canCopy' => $this->RepoItem()->canCopy(Security::getCurrentUser())
        ];
    }

    public function providePermissions() {
        return [
            'REPOITEM_VIEW_SUMMARY' => [
                'name' => 'View all published RepoItemSummaries',
                'category' => 'RepoItem'
            ]
        ];
    }

    public function canView($member = null) {
        return Permission::check('REPOITEM_VIEW_SUMMARY');
    }

    static function getPermissionCases() {
        $repoItemSummaryCases = RepoItem::getPermissionCases();
        //   unset($repoItemSummaryCases['REPOITEM_VIEW_PUBLISHED']);

        if (Permission::check('REPOITEM_VIEW_SUMMARY')) {
            $repoItemSummaryCases[PermissionFilter::NO_CODE] = "(SurfSharekit_RepoItem.IsPublic = 1 AND SurfSharekit_RepoItem.Status = 'Published')";
        }
        return $repoItemSummaryCases;
    }
}