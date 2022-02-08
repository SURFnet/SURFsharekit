<?php

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SurfSharekit\Api\RepoItemDescribingNode;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Helper\DateHelper;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\ProtocolNode;
use SurfSharekit\Models\RepoItem;

/***
 * This class defines in what way a repoItem should be output to the external api when requested a jsonapi protocol
 */
class ExternalRepoItemChannelJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'repoItem';
    public $type_plural = 'repoItems';

    public $fieldToAttributeMap = [];

    public $attributeToNodeMap = [];
    public $channel;
    public $protocol;
    private $repoItemsCurrentlyInChannel = [];

    /**
     * ExternalRepoItemJsonApiDescription constructor.
     * When created, this class created an fieldToAttribute list for each title of the ProtocolNodes for the external  JsonAPI protocol
     * To map said attributes to the actual values in the RepoItem, a attributeToNodeMap is established as well.
     */
    public function __construct($channel = null) {
        $describingProtocolFilter = ['SystemKey' => 'JSON:API'];
        if (!is_null($channel)) {
            $describingProtocolFilter['ID'] = $channel->ProtocolID;
        }

        $this->channel = $channel;
        $this->protocol = Protocol::get()->filter($describingProtocolFilter)->first();
        if ($this->protocol && $this->protocol->exists()) {
            foreach ($this->protocol->ProtocolNodes()->filter('ParentNodeID', 0) as $node) {
                $this->fieldToAttributeMap[] = $node->NodeTitle;
                $this->attributeToNodeMap[$node->NodeTitle] = $node;
            }
        }
    }

    public function describeAttributesOfDataObject(DataObject $dataObject) {
        if(isset($this->objectsToDescribe)) {
            if (!$this->repoItemsCurrentlyInChannel) {
                $this->repoItemsCurrentlyInChannel = $this->getAllItemsToDescribe($this->objectsToDescribe, false)->column('ID');
            }
            $attributes = [];
            if (!in_array($dataObject->ID, $this->repoItemsCurrentlyInChannel)) {
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

    /**
     * @param DataObject $dataObject
     * @param $attribute
     * @return array|mixed|string|null
     * Seeing $fieldToAttributeMap is filled with attributes names, not mapped to fields of the DataObject,
     * this method is called and used to let ProtocolNodes describe the RepoItems values
     */
    public function describeAttribute(DataObject $dataObject, $attribute) {
        /**
         * @var $node ProtocolNode
         */
        $node = $this->attributeToNodeMap[$attribute];
        /** @var RepoItem $dataObject */
        return $node->describeUsing($dataObject, 'json');
    }

    public $hasOneToRelationMap = [
    ];

    public $hasManyToRelationsMap = [
    ];

    //used to go from json to object
    public $attributeToFieldMap = [
    ];

    public function applyGeneralFilter(DataList $objectsToDescribe): DataList {
        return $this->getAllItemsToDescribe($objectsToDescribe);
    }

    /**
     * @param DataList $objectsToDescribe
     * @param $attribute
     * @param $value
     * @return DataList
     * @throws Exception
     * Method to apply json api filters to data list collections the object type this description describes, should return filtered DataList
     */
    public function applyFilter(DataList $objectsToDescribe, $attribute, $value): DataList {
        $whereFunction = $this->getFilterFunction(explode(',', $attribute)); //can be used to filter both ?filter[Name][Like]=abc AND ?filter[name,email][Like]=abc
        $joinedQuery = $objectsToDescribe;

        if (is_array($value)) {
            foreach ($value as $mode => $modeValue) {
                if (isset(static::$filterModeMap[$mode])) {
                    $joinedQuery = $whereFunction($joinedQuery, $modeValue, static::$filterModeMap[$mode]);
                } else {
                    throw new Exception("$mode is an invalid filter modifier, use on of: [EQ, NEQ, LIKE, LT, LE, GT, GE]");
                }
            }
            return $joinedQuery;
        }
        return $whereFunction($objectsToDescribe, $value, static::$filterModeMap['EQ']);
    }

    /**
     * @param DataList $objectsToDescribe
     * @param $attribute
     * @param $ascOrDesc
     * @return DataList
     * @throws Exception
     * Method to apply json api filters to data list collections the object type this description describes, should return filtered DataList
     */
    public function applySort(DataList $objectsToDescribe, $sortField, $ascOrDesc): DataList {
        $sortableAttributeToColumnMap = $this->getSortableAttributesToColumnMap();
        if (!in_array($sortField, array_keys($sortableAttributeToColumnMap))) {
            throw new Exception("Sort on $sortField not allowed, please try on of: [" . implode(',', array_keys($sortableAttributeToColumnMap)) . ']');
        }
        if (is_array($sortableAttributeToColumnMap[$sortField])) {
            foreach ($sortableAttributeToColumnMap[$sortField] as $field) {
                $objectsToDescribe = $objectsToDescribe->sort($field, $ascOrDesc);
            }
            return $objectsToDescribe;
        } else {
            return $objectsToDescribe->sort($sortableAttributeToColumnMap[$sortField], $ascOrDesc);
        }
    }

    /**
     * @return array
     * e.g.
     *     ['isRemoved' => '`SurfSharekit_RepoItem`.`IsRemoved`',
     * 'lastEdited' => '`SurfSharekit_RepoItem`.`LastEdited`']
     */
    public function getFilterableAttributesToColumnMap(): array {
        return [
            'isRemoved' => '`SurfSharekit_RepoItem`.`IsRemoved`',
            'lastEdited' => '`SurfSharekit_RepoItem`.`LastEdited`',
            'modified' => '`SurfSharekit_RepoItem`.`LastEdited`',
            'title' => '`SurfSharekit_RepoItem`.`Title`',
            'publicationDate' => '`SurfSharekit_RepoItem`.`PublicationDate`',
            'id' => '`SurfSharekit_RepoItem`.`Uuid`',
            'institute' => '`SurfSharekit_RepoItem`.`InstituteUuid`'];
    }

    public function getFilterFunction(array $fieldsToSearchIn) {
        $filterableFields = $this->getFilterableAttributesToColumnMap();
        if (count($filterableFields) === 0) {
            throw new Exception("Search not supported for this object type");
        }

        return function (DataList $datalist, $filterValue, $modifier) use ($fieldsToSearchIn, $filterableFields) {
            $filterAnyArray = [];
            foreach ($fieldsToSearchIn as $searchField) {
                if (isset($filterableFields[$searchField])) {
                    $columnDescription = $filterableFields[$searchField];
                    if ($modifier == '=' && $filterValue == 'NULL') {
                        $filterAnyArray[] = $columnDescription . ' IS NULL';
                    } else {
                        $filterAnyArray[$columnDescription . ' ' . $modifier . ' ?'] = $filterValue;
                    }
                } else {
                    throw new Exception("$searchField is not a supported filter, try filtering on one of: [" . implode(',', array_keys($filterableFields)) . ']');
                }
            }
            return $datalist->whereAny($filterAnyArray);
        };
    }

    public function getCache($dataObject) {
        if(!(isset($this->objectsToDescribe))){
            $this->objectsToDescribe = $dataObject::get();
        }

        if (!$this->repoItemsCurrentlyInChannel) {
            $this->repoItemsCurrentlyInChannel = $this->getAllItemsToDescribe($this->objectsToDescribe, false)->column('ID');
        }
        if (!in_array($dataObject->ID, $this->repoItemsCurrentlyInChannel)) {
            return null;
        }

        $cachedNode = Cache_RecordNode::get()->filter(['Endpoint' => 'JSON:API', 'ProtocolID' => $this->protocol->ID, 'ChannelID' => $this->channel->ID, 'RepoItemID' => $dataObject->ID, 'CachedLastEdited' => $dataObject->LastEdited])->first();
        return ($cachedNode && $cachedNode->exists()) ? json_decode($cachedNode->Data, true) : null;
    }

    public function cache($dataObject, array $dataDescription) {
        Logger::debugLog("Cache : " . $dataObject->Uuid . ', Channel : ' . $this->channel->ID . ', ProtocolVersion : ' . $this->protocol->Version);
        $cachedNode = Cache_RecordNode::get()->filter(['Endpoint' => 'JSON:API', 'ProtocolID' => $this->protocol->ID, 'ChannelID' => $this->channel->ID, 'RepoItemID' => $dataObject->ID])->first();
        if (!$cachedNode || !$cachedNode->exists()) {
            $cachedNode = Cache_RecordNode::create();
        }
        $cachedNode->Endpoint = 'JSON:API';
        $cachedNode->RepoItemID = $dataObject->ID;
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

    private function getAllItemsToDescribe(DataList $objectsToDescribe, $includeCachedItems = true) {
        $this->objectsToDescribe = $objectsToDescribe;
        $objectsToDescribe = parent::applyGeneralFilter($objectsToDescribe);
        //general filter
        //cache filter
        $channelFilterArray = RepoItemDescribingNode::getChannelFilter($this->channel);
        $queryFilterArray = RepoItemDescribingNode::getQueryFilter("", $this->channel);
        $selectionQuery = RepoItemDescribingNode::getSelectionQuery(null, null, null, 'JSON:API', $this->protocol->ID, $this->channel, $channelFilterArray, null, null, $queryFilterArray, $includeCachedItems);

        //Fill subquery with params
        foreach ($selectionQuery[1] as $param) {
            $pos = strpos($selectionQuery[0], "?");
            if ($pos !== false) {
                $selectionQuery[0] = substr_replace($selectionQuery[0], Convert::raw2sql($param, true), $pos, strlen("?"));
            }
        }
        $allObjects = $objectsToDescribe->innerJoin("($selectionQuery[0])", 'sel.ID = SurfSharekit_RepoItem.ID', 'sel');
        //Logger::debugLog($allObjects->sql());
        return $allObjects;
    }

    public function describeMetaOfDataObject(DataObject $dataObject) {
        if (!in_array($dataObject->ID, $this->repoItemsCurrentlyInChannel)) {
            return ['status' => 'deleted', 'deletedAt' => DateHelper::iso8601zFromString($dataObject->LastEdited)];
        }
        return null;
    }
}