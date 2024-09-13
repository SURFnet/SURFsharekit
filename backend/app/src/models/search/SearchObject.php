<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use SurfSharekit\Models\Helper\Constants;

class SearchObject extends DataObject {
    const SPLITTER = ' || ';
    private static $table_name = 'SurfSharekit_SearchObject';

    private static $db = [
        'SearchText' => 'Text',
    ];

    private static $has_one = [
        'Person' => Person::class,
        'RepoItem' => RepoItem::class,
        'Institute' => Institute::class
    ];

    private static $summary_fields = [
        'SearchText' => 'SearchText'
    ];

    private static $indexes = [
        'FulltextSearch' => [
            'type' => 'fulltext',
            'columns' => ['SearchText']
        ]
    ];

    static function generateSearchTextForPerson(Person $person) {
        $searchables = [];
        $searchables[] = $person->Uuid;
        $searchables[] = $person->getName();
        $searchables[] = $person->PersistentIdentifier;
        $searchables[] = $person->ORCID;
        $searchables[] = $person->ISNI;
        $searchables[] = $person->HogeschoolID;

        foreach ($person->Groups() as $group){
            $searchables[] = $group->Title;
            $searchables[] = $group->Label_NL;
            $searchables[] = $group->Label_EN;
        }

        return implode(static::SPLITTER, $searchables);
    }

    /**
     * @param RepoItem $repoItem
     * @param bool $isBeingGeneratedForAnotherRepoItem
     * @return string
     * Generates a string for all findable fields
     */
    static function generateSearchTextForRepoItem(RepoItem $repoItem, $isBeingGeneratedForAnotherRepoItem = false) {
        $searchables = [];
        $searchables[] = $repoItem->Uuid;
        $searchables[] = $repoItem->Title;
        $searchables[] = $repoItem->Owner()->FullName;

        foreach ($repoItem->RepoItemMetaFields()->filter(['MetaField.MakesRepoItemFindable' => true]) as $searchableMetafield) {
            foreach ($searchableMetafield->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]) as $answer) {
                //Add connected items by uuid for search, to make repoitems findable by author id for example
                if (($connectedRepoItem = $answer->RepoItem()) && $connectedRepoItem->exists()) {
                    //Don't index learningobject by its connected learningobject of its connected learningobject
                    if (!$isBeingGeneratedForAnotherRepoItem) {
                        $searchables[] = self::generateSearchTextForRepoItem($connectedRepoItem, true);
                    }
                } else if ($connectedUuid = $answer->getUuidOfConnectedItem()) {
                    $searchables[] = $connectedUuid;
                    $searchables[] = $answer->SummaryFieldValue;
                } else {
                    $searchables[] = $answer->SummaryFieldValue;
                }
            }
        }
        return implode(static::SPLITTER, $searchables);
    }

    public function getUuid() {
        $uuid = null;
        if (($repoItem = $this->RepoItem()) && $repoItem->exists()) {
            $uuid = $repoItem->Uuid;
        } else if (($person = $this->Person()) && $person->exists()) {
            $uuid = $person->Uuid;
        }
        return $uuid;
    }

    public static function updateForPerson(Person $person) {
        $searchObject = SearchObject::get()->filter(['PersonID' => $person->ID])->first();
        if (!$searchObject || !$searchObject->exists()) {
            $searchObject = new SearchObject();
        }
        $searchObject->PersonID = $person->ID;
        $searchObject->updateFromPerson($person);
        $searchObject->write();
    }

    public static function updateForRepoItem(RepoItem $repoItem) {
        if (!in_array($repoItem->RepoType, Constants::MAIN_REPOTYPES)) {
            return;
        }
        $searchObject = SearchObject::get()->filter(['RepoItemID' => $repoItem->ID])->first();
        if (!$searchObject || !$searchObject->exists()) {
            $searchObject = new SearchObject();
        }
        $searchObject->RepoItemID = $repoItem->ID;
        $searchObject->updateFromRepoItem($repoItem);
        $searchObject->write();
    }

    public function updateFromRepoItem($repoItem = null) {
        $repoItem = $repoItem ?: $this->RepoItem();
        $this->SearchText = static::generateSearchTextForRepoItem($repoItem);
    }

    public function updateFromPerson($person = null) {
        $person = $person ?: $this->Person();
        $this->SearchText = static::generateSearchTextForPerson($person);
    }
}