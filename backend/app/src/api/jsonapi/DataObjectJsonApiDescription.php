<?php

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

const RELATIONSHIP_GET_RELATED_OBJECTS_METHOD = 'RELATIONSHIP_DATA_OBJECT_FIELD'; //method to getRelated object with
const RELATIONSHIP_RELATED_OBJECT_CLASS = 'RELATIONSHIP_CLASS'; //type of dataobject that related object is

const RELATIONSHIP_ADD_PERMISSION_METHOD = 'RELATIONSHIP_ADD'; //method to call to check if a hasmany can be added to with an object
const RELATIONSHIP_REMOVE_PERMISSION_METHOD = 'RELATIONSHIP_REMOVE'; //method to to check if a hasmany can remove from with an object

abstract class DataObjectJsonApiDescription {
    static $filterModeMap = [
        'EQ' => '=',
        'NEQ' => '!=',
        'LIKE' => 'LIKE',
        'LT' => '<',
        'LE' => '<=',
        'GT' => '>',
        'GE' => '>='];
    /**
     * @var string $type_singular singular name of the jsonapi document, used at the object identifier
     */
    public $type_singular = 'dataobject';
    /**
     * @var string $type_plural plural name of the jsonapi document, used to generate an endpoint to access the document
     */
    public $type_plural = 'dataobjects';

    /**
     * @var array of strings or string to string map, to go from DataObject to json, e.g:
     * ['Title' => 'title'] will call DataObject->title
     * ['title'] however, will call @see describeAttribute(DataObject, $title)
     */
    public $fieldToAttributeMap = [
        //'Title => 'title'
    ];

    /**
     * @var array string to array map, use @see RELATIONSHIP_GET_RELATED_OBJECTS_METHOD and @see RELATIONSHIP_RELATED_OBJECT_CLASS as keys in the value array
     * to denote what method should be called on the DataObject and what type the resulting hasOne DataObject will have
     */
    public $hasOneToRelationMap = [
        //=> [
        //            RELATIONSHIP_RELATED_OBJECT_CLASS => Institute::class,
        //            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Institute'
        //        ]
    ];

    /**
     * @var array string to array map, use @see RELATIONSHIP_GET_RELATED_OBJECTS_METHOD and @see RELATIONSHIP_RELATED_OBJECT_CLASS as keys in the value array
     * to denote what method should be called on the DataObject and what type the elements in the resulting hasMany DataList array will have
     */
    public $hasManyToRelationsMap = [
        //=> [
        //            RELATIONSHIP_RELATED_OBJECT_CLASS => Institute::class,
        //            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Institutes'
        //            RELATIONSHIP_ADD_PERMISSION_METHOD => 'canAddInstitute'
        //            RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canRemoveInstitute'
        //        ]
    ];

    /**
     * @var array string to string map, to go from DataObject to json, e.g: 'title' => Title' 'setTitle($value)' will be called on DataObject
     */
    public $attributeToFieldMap = [
        //'title => 'Title'
    ];

    /**
     * @param string $relationshipName
     * @return bool
     * Utility method to check if DataObject has a hasOne or hasMany relation with the requested name of $relationshipName
     */
    function hasRelationship(string $relationshipName): bool {
        return array_key_exists($relationshipName, $this->hasOneToRelationMap) || array_key_exists($relationshipName, $this->hasManyToRelationsMap);
    }

    /**
     * @param DataObject $dataObject
     * @param $attribute
     * @return mixed
     * Alternative method that can be used to describe aspects of DataObject that it cannot describe itself
     */
    function describeAttribute(DataObject $dataObject, $attribute) {
        return $dataObject->$attribute;
    }

    function applyGeneralFilter(DataList $objectsToDescribe): DataList {
        return $objectsToDescribe;
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
                if (isset(self::$filterModeMap[$mode])) {
                    $joinedQuery = $whereFunction($joinedQuery, $modeValue, self::$filterModeMap[$mode]);
                } else {
                    throw new Exception("$mode is an invalid filter modifier, use on of: [EQ, NEQ, LIKE, LT, LE, GT, GE]");
                }
            }
            return $joinedQuery;
        }
        return $whereFunction($objectsToDescribe, $value, self::$filterModeMap['EQ']);
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
     * @param DataObject $dataObject
     * @return array
     * Method to loop through all @see DataObjectJsonApiDescription::$fieldToAttributeMap to describe fields of a single dataobject to JsonApi attributes
     */
    public function describeAttributesOfDataObject(DataObject $dataObject) {
        $attributes = [];
        foreach ($this->fieldToAttributeMap as $field => $attribute) {
            if (is_int($field)) {
                $attributes[$attribute] = $this->describeAttribute($dataObject, $attribute);
            } else {
                $attributes[$attribute] = $dataObject->$field;
            }
        }
        return $attributes;
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

    /**
     * @return array
     * e.g.
     *     ['isRemoved' => '`SurfSharekit_RepoItem`.`IsRemoved`',
     * 'lastEdited' => '`SurfSharekit_RepoItem`.`LastEdited`']
     */
    public function getFilterableAttributesToColumnMap(): array {
        return [];
    }

    /**
     * @return array Array
     * e.g.
     * ['title' => 'Title',
     * 'institute' => 'Institute.Title']
     * 'authorName' => ['Person.Surname', 'Person.FirstName']]
     */
    protected function getSortableAttributesToColumnMap(): array {
        return [];
    }

    public function getCache($dataObject) {
        return null;
    }

    public function cache($dataObject, array $dataDescription) {
    }

    public function describeMetaOfDataObject(DataObject $dataObject) {
        return null;
    }
}