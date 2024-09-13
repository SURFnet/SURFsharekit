<?php

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SurfSharekit\Api\JsonApi;

/**
 * Class DataObjectJsonApiBodyEncoder
 * This class is the entry point to describe a certain DataObject with a DataObjectJsonApiEncoder to result in JsonApi data.
 * Albeit with or without included relationship objects, sparseFields or other jsonapi information
 */
class DataObjectJsonApiBodyEncoder {
    /***
     * @param DataObject $dataObject
     * @param DataObjectJsonApiEncoder $descriptor
     * @param string $contextURL
     * @return array
     * @throws Exception
     * Function to describe a single datobject as the link to the object, the data of the object and included objects the object is related to
     */
    public static function dataObjectToSingleObjectJsonApiBodyArray(Dataobject $dataObject, DataObjectJsonApiEncoder $descriptor, string $contextURL): array {
        if ($descriptor->describeLinksOfObjectsAndRelations) {
            $jsonApiMap[JsonApi::TAG_LINKS] = [
                JsonApi::TAG_LINKS_SELF => $descriptor->getContextURLForDataObject($dataObject, $contextURL)
            ];
        }

        if ($objectDescription = $descriptor->describeDataObjectAsData($dataObject, $contextURL)) {
            $jsonApiMap[JsonApi::TAG_DATA] = $objectDescription;
            if ($listOfIncludedObject = $descriptor->describeDataObjectAsIncluded($dataObject, $contextURL)) {
                $jsonApiMap[JsonApi::TAG_INCLUDED] = $listOfIncludedObject;
            }
        }

        return $jsonApiMap;
    }

    /***
     * @param DataList $dataList
     * @param DataObjectJsonApiEncoder $descriptor
     * @param string $contextURL
     * @return array
     * @throws Exception
     * This method does the same as @see DataObjectJsonApiBodyEncoder::dataObjectToRelationJsonApiBodyArray() but for a collection of dataObjects
     */
    public static function dataListToMultipleObjectsJsonApiBodyArray(DataList $dataList, DataObjectJsonApiEncoder $descriptor, array $possibleFilters, string $contextURL): array {
        if (is_null($descriptor->totalResultCount)) {
            $canViewMethod = function (DataObject $viewMaybeObj) {
                return $viewMaybeObj->canView(Security::getCurrentUser());
            };

            $dataListCanViewArray = $dataList->filterByCallback($canViewMethod);
        } else {
            $dataListCanViewArray = $dataList->toArray();
        }

        $jsonApiMap[JsonApi::TAG_META][JsonApi::TAG_TOTAL_COUNT] = !is_null($descriptor->totalResultCount) ? $descriptor->totalResultCount : count($dataListCanViewArray);

        $jsonApiMap[JsonApi::TAG_FILTERS] = $possibleFilters;

        if ($descriptor->pageNumber && $descriptor->pageSize && $descriptor->totalResultCount) {
            $pageSize = $descriptor->pageSize;
            $pageNumber = $descriptor->pageNumber;
            $linkSizeAddition = "?page[size]=$pageSize";
            $linkNumberAddition = '&page[number]=';

            $jsonApiMap[JsonApi::TAG_LINKS][JsonApi::TAG_LINKS_FIRST] = $descriptor->getContextURLForDataList($dataList, $contextURL) . $linkSizeAddition . $linkNumberAddition . '1';
            if ($descriptor->pageNumber > 1) {
                $jsonApiMap[JsonApi::TAG_LINKS][JsonApi::TAG_LINKS_PREVIOUS] = $descriptor->getContextURLForDataList($dataList, $contextURL) . $linkSizeAddition . $linkNumberAddition . ($pageNumber - 1);
            }

            $jsonApiMap[JsonApi::TAG_LINKS][JsonApi::TAG_LINKS_SELF] = $descriptor->getContextURLForDataList($dataList, $contextURL) . $linkSizeAddition . $linkNumberAddition . $pageNumber;

            $lastpage = ceil($descriptor->totalResultCount / $pageSize);
            if ($pageNumber < $lastpage) {
                $jsonApiMap[JsonApi::TAG_LINKS][JsonApi::TAG_LINKS_NEXT] = $descriptor->getContextURLForDataList($dataList, $contextURL) . $linkSizeAddition . $linkNumberAddition . ($pageNumber + 1);
            }
            $jsonApiMap[JsonApi::TAG_LINKS][JsonApi::TAG_LINKS_LAST] = $descriptor->getContextURLForDataList($dataList, $contextURL) . $linkSizeAddition . $linkNumberAddition . $lastpage;
        } else {
            $jsonApiMap[JsonApi::TAG_LINKS] = [
                JsonApi::TAG_LINKS_SELF => $descriptor->getContextURLForDataList($dataList, $contextURL)
            ];
        }

        $jsonApiMap[JsonApi::TAG_DATA] = [];

        foreach ($dataListCanViewArray as $dataObject) {
            if ($objectDescription = $descriptor->describeDataObjectAsData($dataObject, $contextURL)) {
                $jsonApiMap[JsonApi::TAG_DATA][] = $objectDescription;

                if ($listOfIncludedObject = $descriptor->describeDataObjectAsIncluded($dataObject, $contextURL)) {
                    if (!isset($jsonApiMap[JsonApi::TAG_INCLUDED])) {
                        $jsonApiMap[JsonApi::TAG_INCLUDED] = [];
                    }
                    $jsonApiMap[JsonApi::TAG_INCLUDED] = array_merge($jsonApiMap[JsonApi::TAG_INCLUDED], $listOfIncludedObject);
                }
            }
        }
        return $jsonApiMap;
    }

    /***
     * @param DataObject $dataObject
     * @param string $relationshipName
     * @param DataObjectJsonApiEncoder $descriptor
     * @param string $contextURL
     * @return array
     * @throws Exception
     * This method describe the relation of a DataObject as requested resource, for example a HasMany or HasOne relation using object identifiers (e.g. books/123/relationships/authors)
     */
    public static function dataObjectToRelationUsingIdentifiersJsonApiBodyArray(DataObject $dataObject, string $relationshipName, DataObjectJsonApiEncoder $descriptor, string $contextURL): array {
        if ($descriptor->describeLinksOfObjectsAndRelations) {
            $jsonApiMap[JsonApi::TAG_LINKS] = $descriptor->getContextURLForDataObjectRelationshipDescription($dataObject, $relationshipName, $contextURL);
        }
        if ($relationDescription = $descriptor->describeRelationshipDescriptionForDataObject($dataObject, $relationshipName, $contextURL)) {
            $jsonApiMap[JsonApi::TAG_DATA][] = $relationDescription;
        }
        if ($listOfIncludedObject = $descriptor->describeDataObjectAsIncluded($dataObject, $contextURL)) {
            if (!isset($jsonApiMap[JsonApi::TAG_INCLUDED])) {
                $jsonApiMap[JsonApi::TAG_INCLUDED] = [];
            }
            $jsonApiMap[JsonApi::TAG_INCLUDED] = array_merge($jsonApiMap[JsonApi::TAG_INCLUDED], $listOfIncludedObject);
        }
        return $jsonApiMap;
    }

    /***
     * @param DataObject $dataObject
     * @param string $relationshipName
     * @param DataObjectJsonApiEncoder $descriptor
     * @param string $contextURL
     * @return array
     * @throws Exception
     * This method describe actual related DataObjects a relation points to for example a HasMany or HasOne relation (e.g. books/123/authors)
     */
    public static function dataObjectToRelationJsonApiBodyArray(DataObject $dataObject, string $relationshipName, DataObjectJsonApiEncoder $descriptor, string $contextURL): array {
        $jsonApiMap[JsonApi::TAG_LINKS] = $descriptor->getContextURLForDataObjectRelationship($dataObject, $relationshipName, $contextURL);
        $jsonApiMap[JsonApi::TAG_DATA] = $descriptor->describeRelationshipForDataObject($dataObject, $relationshipName, $contextURL);
        if ($listOfIncludedObject = $descriptor->describeDataObjectAsIncluded($dataObject, $contextURL)) {
            if (!isset($jsonApiMap[JsonApi::TAG_INCLUDED])) {
                $jsonApiMap[JsonApi::TAG_INCLUDED] = [];
            }
            $jsonApiMap[JsonApi::TAG_INCLUDED] = array_merge($jsonApiMap[JsonApi::TAG_INCLUDED], $listOfIncludedObject);
        }
        return $jsonApiMap;
    }
}