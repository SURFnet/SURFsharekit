<?php

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\View\ViewableData;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Helper\DateHelper;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItem;

class ExternalPersonJsonApiDescription extends DataObjectJsonApiDescription{
    public $type_singular = 'person';
    public $type_plural = 'persons';

    public $channel;
    public $protocol;
    public $personsCurrentlyInChannel = [];

    public function __construct($channel = null) {

        $describingProtocolFilter = ['SystemKey' => 'JSON:API'];
        if (!is_null($channel)) {
            $describingProtocolFilter['ID'] = $channel->ProtocolID;
        }

        $this->channel = $channel;
        $this->protocol = Protocol::get()->filter($describingProtocolFilter)->first();

        $this->fieldToAttributeMap = [
            'Email' => 'email',
            'FirstName' => 'firstName',
            'Surname' => 'surname',
            'SurnamePrefix' => 'surnamePrefix',
            'Initials' => 'initials',
            'FormOfAddress' => 'formOfAddress',
            'AcademicTitle' => 'academicTitle',
            'Position' => 'position',
            'ORCID' => 'orcid',
            'ISNI' => 'isni',
            'PersistentIdentifier' => 'dai',
            'TwitterUrl' => 'twitterUrl',
            'LinkedInUrl' => 'linkedInUrl',
            'ResearchGateUrl' => 'researchGateUrl'
        ];

        $this->attributeToFieldMap = [
            'email' => 'Email',
            'firstName' => 'FirstName',
            'surname' => 'Surname',
            'surnamePrefix' => 'SurnamePrefix',
            'initials' => 'Initials',
            'formOfAddress' => 'FormOfAddress',
            'academicTitle' => 'AcademicTitle',
            'position' => 'Position',
            'orcid' => 'ORCID',
            'isni' => 'ISNI',
            'dai' => 'PersistentIdentifier',
            'twitterUrl' => 'TwitterUrl',
            'linkedInUrl' => 'LinkedInUrl',
            'researchGateUrl' => 'ResearchGateUrl'
        ];
    }

    public function applyGeneralFilter(DataList $objectsToDescribe): DataList {
        return $this->getAllItemsToDescribe($objectsToDescribe, false);
    }

    private function getAllItemsToDescribe(DataList $objectsToDescribe, $includeCachedItems = true) {
        $this->objectsToDescribe = $objectsToDescribe;
        $objectsToDescribe = parent::applyGeneralFilter($objectsToDescribe);

        $description = new ExternalRepoItemChannelJsonApiDescription($this->channel);
        $repoItemsToDescribe = $description->getAllItemsToDescribe(RepoItem::get(), false);
        $repoItemIDList = $repoItemsToDescribe->getIDList();

        $repoItemPersons = $repoItemsToDescribe
            ->leftJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItem.ID = SurfSharekit_RepoItemMetaField.RepoItemID')
            ->leftJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
            ->where([
                "SurfSharekit_RepoItemMetaFieldValue.RepoItemID != (?) OR (?)" => [0, null],
                'SurfSharekit_RepoItemMetaFieldValue.IsRemoved != (?)' => 1,
            ])
            ->column('SurfSharekit_RepoItemMetaFieldValue.RepoItemID');

        $filterArray = $repoItemPersons ? $repoItemPersons : [null];
        $personIDList = RepoItem::get()->filter(['ID' => $filterArray])
            ->leftJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItem.ID = SurfSharekit_RepoItemMetaField.RepoItemID')
            ->leftJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
            ->where(['SurfSharekit_RepoItemMetaFieldValue.PersonID != (?) OR (?)' => [0, null]])
            ->column("SurfSharekit_RepoItemMetaFieldValue.PersonID");

        $filterArray = $personIDList ? $personIDList : [null];
        return $objectsToDescribe->filter(['ID' => $filterArray]);
    }

    public function getCache($dataObject) {
        if(!(isset($this->objectsToDescribe))){
            $this->objectsToDescribe = $dataObject::get();
        }

        if (!$this->personsCurrentlyInChannel) {
            $this->personsCurrentlyInChannel = $this->getAllItemsToDescribe($this->objectsToDescribe, false)->column('ID');
        }
        if (!in_array($dataObject->ID, $this->personsCurrentlyInChannel)) {
            return null;
        }

        $cachedNode = Cache_RecordNode::get()->filter(['Endpoint' => 'JSON:API', 'ProtocolID' => $this->protocol->ID, 'ChannelID' => $this->channel->ID, 'PersonID' => $dataObject->ID, 'CachedLastEdited' => $dataObject->LastEdited])->first();
        return ($cachedNode && $cachedNode->exists()) ? json_decode($cachedNode->Data, true) : null;
    }

    public function describeAttributesOfDataObject(ViewableData $dataObject) {
        if(isset($this->objectsToDescribe)) {
            if (!$this->personsCurrentlyInChannel) {
                $this->personsCurrentlyInChannel = $this->getAllItemsToDescribe($this->objectsToDescribe, false)->column('ID');
            }
            $attributes = [];
            if (!in_array($dataObject->ID, $this->personsCurrentlyInChannel)) {
                return $attributes;
            }
        }
        foreach ($this->fieldToAttributeMap as $field => $attribute) {
            if (is_int($field)) {
                $attributes[$attribute] = $this->describeAttribute($dataObject, $attribute);
            } else {
                $attributes[$attribute] = $dataObject->$field;
            }
        }
        return $attributes;
    }

    public function cache($dataObject, array $dataDescription) {
        Logger::debugLog("Cache : " . $dataObject->Uuid . ', Channel : ' . $this->channel->ID . ', ProtocolVersion : ' . $this->protocol->Version);
        $cachedNode = Cache_RecordNode::get()->filter(['Endpoint' => 'JSON:API', 'ProtocolID' => $this->protocol->ID, 'ChannelID' => $this->channel->ID, 'PersonID' => $dataObject->ID])->first();
        if (!$cachedNode || !$cachedNode->exists()) {
            $cachedNode = Cache_RecordNode::create();
        }
        $cachedNode->Endpoint = 'JSON:API';
        $cachedNode->PersonID = $dataObject->ID;
        $cachedNode->ProtocolID = $this->protocol->ID;
        $cachedNode->ChannelID = $this->channel->ID;
        $cachedNode->Data = json_encode($dataDescription);
        $cachedNode->ProtocolVersion = $this->protocol->Version;
        $cachedNode->CachedLastEdited = $dataObject->LastEdited;
        try {
            $cachedNode->write();
        } catch (ValidationException $e) {
            Logger::debugLog($e->getMessage());
        }
    }

    public function describeMetaOfDataObject(ViewableData $dataObject) {
        if (!in_array($dataObject->ID, $this->personsCurrentlyInChannel)) {
            return ['status' => 'deleted', 'deletedAt' => DateHelper::iso8601zFromString($dataObject->LastEdited)];
        }
        return null;
    }
}