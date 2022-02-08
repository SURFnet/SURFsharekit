<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;

class PersonSummary extends DataObject {
    private static $table_name = 'SurfSharekit_PersonSummary';

    private static $db = [
        'IsPublic' => 'Boolean(0)',
        'IsRemoved' => 'Boolean(0)',
        'Summary' => 'Text'
    ];

    private static $has_one = [
        'Person' => Person::class,
        'Institute' => Institute::class
    ];

    private static $summary_fields = [
        'Summary' => 'Summary'
    ];

    protected function onAfterWrite() {
        parent::onAfterWrite();
        if($this->isChanged('PersonID') || $this->isChanged('InstituteID')) {
            ScopeCache::removeCachedViewable(PersonSummary::class);
            ScopeCache::removeCachedDataList(PersonSummary::class);
        }
    }

    public function updateSummary() {
        $this->Summary = null;
        if (($person = $this->Person()) && $person->exists()) {
            $this->Summary = json_encode(static::generateSummaryFor($person));
        }
    }

    static function generateSummaryFor($person) {
        $summaryValues = [
            'id' => $person->Uuid,
            'name' => $person->Name,
            'surnamePrefix' => $person->SurnamePrefix,
            'surname' => $person->Surname,
            'firstName' => $person->FirstName,
            'persistentIdentifier' => $person->PersistentIdentifier,
            'hogeschoolId' => $person->HogeschoolID,
            "orcid" => $person->ORCID,
            "isni" => $person->ISNI,
            "hasLoggedIn" => $person->HasLoggedIn,
            "position" => $person->Position,
            "groupTitles" => $person->GroupTitles
        ];

        return $summaryValues;
    }

    public function getSummaryJsonDecoded() {
        if (!$this->decodedSummary) {
            $this->decodedSummary = $this->Summary ? json_decode($this->Summary, true) : [];
        }
        return $this->decodedSummary;
    }

    public function getUuid() {
        return $this->Person()->Uuid;
    }

    public function canEdit($member = null) {
        return false;
    }

    public static function updateFor(Person $person) {
        $personSummary = PersonSummary::get()->filter(['PersonID' => $person->ID])->first();
        if (!$personSummary || !$personSummary->exists()) {
            $personSummary = new PersonSummary();
        }
        $personSummary->PersonID = $person->ID;
        $personSummary->updateFromPerson($person);
        $personSummary->write();
    }

    public function updateFromPerson($person = null) {
        $person = $person ?: $this->Person();
        $this->InstituteID = $person->InstituteID;
        $this->updateSummary();
    }

    function getLoggedInUserPermissions() {
        return [
            'canView' => true,
            'canEdit' => true,
            'canDelete' => true
        ];
    }

    public function __get($property) {
        if (stripos($property, 'Summary.') !== false) {
            $summaryDecoded = $this->getSummaryJsonDecoded();
            $accessor = str_replace('Summary.', '', $property);
            return isset($summaryDecoded[$accessor]) ? $summaryDecoded[$accessor] : null;
        } else if (stripos($property, 'Person.') !== false) {
            $accessor = str_replace('Person.', '', $property);
            return $this->Person()->$accessor;
        } else {
            return parent::__get($property);
        }
    }
}