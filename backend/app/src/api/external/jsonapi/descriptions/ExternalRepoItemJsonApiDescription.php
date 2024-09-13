<?php

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\ProtocolNode;
use SurfSharekit\Models\RepoItem;

/***
 * This class defines in what way a repoItem should be output to the external api when requested a jsonapi protocol
 */
class ExternalRepoItemJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'repoItem';
    public $type_plural = 'repoItems';

    public $fieldToAttributeMap = [];

    public $attributeToNodeMap = [];
    public $filterCount = 0;

    private $describingProtocol = null;

    /**
     * ExternalRepoItemJsonApiDescription constructor.
     * When created, this class created an fieldToAttribute list for each title of the ProtocolNodes for the external  JsonAPI protocol
     * To map said attributes to the actual values in the RepoItem, a attributeToNodeMap is established as well.
     */
    public function __construct(Channel $channel = null) {
        if ($channel && $channel->exists()) {
            $this->describingProtocol = Protocol::get()->filter(['ID' => $channel->ProtocolID, 'SystemKey' => 'JSON:API'])->first();
        } else {
            $this->describingProtocol = Protocol::get()->filter(['SystemKey' => 'JSON:API'])->first();
        }
        if ($this->describingProtocol && $this->describingProtocol->exists()) {
            foreach ($this->describingProtocol->ProtocolNodes()->filter('ParentNodeID', 0) as $node) {
                $this->fieldToAttributeMap[] = $node->NodeTitle;
                $this->attributeToNodeMap[$node->NodeTitle] = $node;
            }
        }
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
        $objectsToDescribe = parent::applyGeneralFilter($objectsToDescribe);
        return $objectsToDescribe->filter(['RepoType' => Constants::MAIN_REPOTYPES, 'IsRemoved' => 0, 'IsArchived' => 0]);
    }

    public function applyFilter(DataList $objectsToDescribe, $attribute, $value): DataList {
        $this->filterCount++;
        //field
        $randomTempTableName = $this->filterCount;
        if ($attribute == 'ID' || $attribute == 'id') {
            // special filter by ID
            return $objectsToDescribe->filter(['Uuid' => $value]);
        }
        $joinedQuery = $objectsToDescribe
            //join answers
            ->leftJoin('SurfSharekit_RepoItemMetaField', "${randomTempTableName}SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID", "${randomTempTableName}SurfSharekit_RepoItemMetaField")
            ->leftJoin('SurfSharekit_RepoItemMetaFieldValue', "${randomTempTableName}SurfSharekit_RepoItemMetaField.ID = ${randomTempTableName}SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID", "${randomTempTableName}SurfSharekit_RepoItemMetaFieldValue")
            ->leftJoin('SurfSharekit_MetaField', "${randomTempTableName}SurfSharekit_RepoItemMetaField.MetaFieldID = ${randomTempTableName}SurfSharekit_MetaField.ID", "${randomTempTableName}SurfSharekit_MetaField")
            ->leftJoin('SurfSharekit_MetaFieldOption', "${randomTempTableName}SurfSharekit_RepoItemMetaFieldValue.MetaFieldOptionID = ${randomTempTableName}SurfSharekit_MetaFieldOption.ID", "${randomTempTableName}SurfSharekit_MetaFieldOption")
            ->leftJoin('SurfSharekit_ProtocolNode', "${randomTempTableName}SurfSharekit_ProtocolNode.MetaFieldID = ${randomTempTableName}SurfSharekit_MetaField.ID", "${randomTempTableName}SurfSharekit_ProtocolNode")
            ->leftJoin('SurfSharekit_Protocol', "${randomTempTableName}SurfSharekit_ProtocolNode.ProtocolID = ${randomTempTableName}SurfSharekit_Protocol.ID", "${randomTempTableName}SurfSharekit_Protocol")
            ->where(["${randomTempTableName}SurfSharekit_Protocol.SystemKey" => 'JSON:API', "${randomTempTableName}SurfSharekit_Protocol.ID" => $this->describingProtocol->ID]);

        $fieldParts = explode('.', $attribute);
        if (count($fieldParts) === 2) {
            $joinedQuery = $joinedQuery
                ->leftJoin('SurfSharekit_ProtocolNode', "${randomTempTableName}SurfSharekit_ProtocolNode.ParentNodeID = ${randomTempTableName}ParentProtocolNode.ID", "${randomTempTableName}ParentProtocolNode")
                ->where(["${randomTempTableName}SurfSharekit_ProtocolNode.NodeTitle" => $fieldParts[1]])
                ->where(["${randomTempTableName}ParentProtocolNode.NodeTitle" => $fieldParts[0]]);
        } else if (count($fieldParts) > 2) {
            throw new Exception("Cannot filter on $attribute, only one nested attribute allowed");
        } else {
            $joinedQuery = $joinedQuery
                ->where(["${randomTempTableName}SurfSharekit_ProtocolNode.NodeTitle" => $attribute])
                ->where(["${randomTempTableName}SurfSharekit_ProtocolNode.ParentNodeID" => 0]);
        }

        $dateComparisonWithModifier = function ($value, $modifier) use (&$dateComparisonWithModifier, $randomTempTableName) {
            $valueParts = explode(',', $value);

            if (count($valueParts) > 1) {
                $orFilterList = [];

                foreach ($valueParts as $valuePart) {
                    $orFilterList = array_merge($orFilterList, $dateComparisonWithModifier($valuePart, $modifier));
                }
                return $orFilterList;
            }

            return ["${randomTempTableName}SurfSharekit_MetaFieldOption.Value $modifier '$value'", "${randomTempTableName}SurfSharekit_RepoItemMetaFieldValue.Value $modifier '$value'",
                "Date(${randomTempTableName}SurfSharekit_MetaFieldOption.Value) $modifier Date('$value')", "Date(${randomTempTableName}SurfSharekit_RepoItemMetaFieldValue.Value) $modifier Date('$value')",
                "${randomTempTableName}SurfSharekit_ProtocolNode.HardcodedValue $modifier '$value'"];
        };

        if (is_array($value)) {
            foreach ($value as $mode => $modeValue) {
                switch ($mode) {
                    case 'EQ':
                        $joinedQuery = $joinedQuery->whereAny($dateComparisonWithModifier($modeValue, '='));
                        break;
                    case 'NEQ':
                        $joinedQuery = $joinedQuery->whereAny($dateComparisonWithModifier($modeValue, '!='));
                        break;
                    case 'LIKE':
                        $joinedQuery = $joinedQuery->whereAny($dateComparisonWithModifier($modeValue, 'LIKE'));
                        break;
                    case 'NOT LIKE':
                        $joinedQuery = $joinedQuery->whereAny($dateComparisonWithModifier($modeValue, 'NOT LIKE'));
                        break;
                    case 'LT':
                        $joinedQuery = $joinedQuery->whereAny($dateComparisonWithModifier($modeValue, '<'));
                        break;
                    case 'LE':
                        $joinedQuery = $joinedQuery->whereAny($dateComparisonWithModifier($modeValue, '<='));
                        break;
                    case 'GT':
                        $joinedQuery = $joinedQuery->whereAny($dateComparisonWithModifier($modeValue, '>'));
                        break;
                    case 'GE':
                        $joinedQuery = $joinedQuery->whereAny($dateComparisonWithModifier($modeValue, '>='));
                        break;
                    default:
                        throw new Exception("$mode is an invalid filter modifier, use on of: [EQ, NEQ, LIKE, NOT LIKE, LT, LE, GT, GE]");
                }
            }

            return $joinedQuery;
        }
        return $joinedQuery->whereAny($dateComparisonWithModifier($value, '='));
    }
}