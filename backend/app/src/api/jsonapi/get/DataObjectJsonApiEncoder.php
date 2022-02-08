<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SurfSharekit\Api\JsonApi;

/**
 * Class DataObjectJsonApiDescriptor
 * @author Matthijs Hilgers
 * @protocol https://jsonapi.org/
 * This class is a utility class, used to parse a DataObject into jsonapi data (arrays) using @see DataObjectJsonApiDescription
 */
class DataObjectJsonApiEncoder {
    /**
     * @var array $classToDescriptionMap
     * The map of DataObject types to their @see DataObjectJsonApiDescription
     */
    public $classToDescriptionMap = [];
    /**
     * @var array $listOfObjectsToIncludeRelationsOf
     * A list of all relations needed to describe the request relations (e.g. books?include=author.comments should request both 'author' and 'author.comments')
     */
    private $listOfObjectsToIncludeRelationsOf = [];
    /**
     * @var bool $describeLinksOfObjectsAndRelations
     * A on/off toggle to (also/not) describe the url of the relations
     */
    public $describeLinksOfObjectsAndRelations = false;
    /**
     * @var array $listOfDataObjectsDescribed
     * A list of DataObjects already described in the jsonApi 'included' array to circumvent circular dependencies when describing relations
     * (e.g. else authors.comments.author could produce an infinite loop)
     */
    private $listOfDataObjectsDescribed = [];

    /**
     * @var int $pageNumber
     * Variable used to paginate through a collection
     */
    public $pageNumber = 0;

    /**
     * @var int Used to not have to do a count
     */
    public $totalResultCount = null;

    /**
     * @var int $pageSize
     * Variable used to paginate through a collection
     */
    public $pageSize = 0;

    public function __construct($classToDescriptionMap, $requestedRelations = []) {
        //Defines how we need to map each dataobject to jsonApi output
        $this->classToDescriptionMap = $classToDescriptionMap;
        $this->setRelationsToIncludeObjectsOf($requestedRelations);
    }

    /**
     * @param $requestedRelations
     * Method used to break down dependend relations (e.g. the relation book?include=author.comments depends on books?include=author)
     */
    private function setRelationsToIncludeObjectsOf($requestedRelations) {
        //The client can request article/1?included=comments.author.cars&attachments, we have to get 'comments.author' and 'comments' as before able to get comments.author.cars, the following code ensures we request all needed relations as well
        $relationsToInclude = [];
        foreach ($requestedRelations as $requestedRelation) {
            $parentRelation = $requestedRelation;
            while ($parentRelation != null) {
                $relationsToInclude[] = $parentRelation;
                $parentRelation = substr($requestedRelation, 0, strrpos($parentRelation, '.'));
            }
        }
        $this->listOfObjectsToIncludeRelationsOf = array_unique($relationsToInclude); //if the client request 'comments' and 'comments.author', we don't need 'comments' two times
    }

    /**
     * @param $dataList
     * @param string $contextURL
     * @return string
     * Method used to retrieve the url to acces the dataList to describe at the given contextURL
     */
    public function getContextURLForDataList($dataList, string $contextURL) {
        $desc = $this->getJsonApiDescriptionForClass($dataList->dataClass());
        return "$contextURL/$desc->type_plural";
    }

    /**
     * @param string $className
     * @return DataObjectJsonApiDescription
     * Utility method to easily access the JsonApi description of the DataObject classname given
     */
    public function getJsonApiDescriptionForClass(string $className) {
        if (isset($this->classToDescriptionMap[$className])) {
            return $this->classToDescriptionMap[$className];
        } else {
            throw new Exception('Unsure how to encode ' . $className);
        }
    }

    /***
     * @param DataObject $dataObject
     * @param string $contextURL
     * @param array $relationaryContext
     * @return array
     * @throws Exception
     * Describes ALL relations as included DataObjects if needed for a single requested DataObject (e.g. book?included=author)
     * It is possible these included DataObjects also have included DataObjects (e.g. book?included=author.comments)
     * In said case, recursion will occur (e.g. the included 'Author' dataObject will call this function for its 'Comments')
     */
    public function describeDataObjectAsIncluded(DataObject $dataObject, string $contextURL, $relationaryContext = []) {
        $objectsToIncludeForDataObject = [];

        $descriptionForDataObject = $this->getJsonApiDescriptionForClass((string)$dataObject);

        foreach ($descriptionForDataObject->hasOneToRelationMap as $hasOneRelationshipName => $relationshipDefinition) {
            if ($this->doesIncludeQueryParameterIncludeRelationship($hasOneRelationshipName, $relationaryContext)) {
                $getHasOneMethod = $relationshipDefinition[RELATIONSHIP_GET_RELATED_OBJECTS_METHOD];
                if ($relatedHasOneObject = $dataObject->$getHasOneMethod()) {
                    if (!$relatedHasOneObject->canView(Security::getCurrentUser())) {
                        throw new Exception('Not allowed to view object in ' . $hasOneRelationshipName . ' relation');
                    }
                    $relatedObjectURLDescriptor = self::getDataObjectURLDescriptorForDataObject($relatedHasOneObject, $descriptionForDataObject);
                    if ($relatedObject = $this->describeDataObjectAsData($relatedHasOneObject, $contextURL . '/', array_merge($relationaryContext, [$hasOneRelationshipName]))) {
                        $objectsToIncludeForDataObject[] = $relatedObject;
                    }
                    $includedRelatedObjectsFromOneRecursionDeeper = $this->describeDataObjectAsIncluded($relatedHasOneObject, "$contextURL/$relatedObjectURLDescriptor", array_merge($relationaryContext, [$hasOneRelationshipName]));
                    if (sizeof($includedRelatedObjectsFromOneRecursionDeeper) > 0) {
                        foreach ($includedRelatedObjectsFromOneRecursionDeeper as $relatedObjectFromOneRecursionDeeper) {
                            $objectsToIncludeForDataObject[] = $relatedObjectFromOneRecursionDeeper;
                        }
                    }
                }
            }
        }

        foreach ($descriptionForDataObject->hasManyToRelationsMap as $hasManyRelationshipName => $relationshipDefinition) {
            if ($this->doesIncludeQueryParameterIncludeRelationship($hasManyRelationshipName, $relationaryContext)) {
                $getHasManyMethod = $relationshipDefinition[RELATIONSHIP_GET_RELATED_OBJECTS_METHOD];

                $canViewMethod = function (DataObject $viewMaybeObj) use ($hasManyRelationshipName) {
                    return $viewMaybeObj->canView(Security::getCurrentUser());
                };

                foreach ($dataObject->$getHasManyMethod()->filterByCallback($canViewMethod) as $relatedHasManyObject) {
                    if ($relatedObject = $this->describeDataObjectAsData($relatedHasManyObject, $contextURL . '/', $relationaryContext, array_merge($relationaryContext, [$hasManyRelationshipName]))) {
                        $objectsToIncludeForDataObject[] = $relatedObject;
                    }
                    $relatedObjectURLDescriptor = self::getDataObjectURLDescriptorForDataObject($relatedHasManyObject, $descriptionForDataObject);

                    $includedRelatedObjectsFromOneRecursionDeeper = $this->describeDataObjectAsIncluded($relatedHasManyObject, "$contextURL/$relatedObjectURLDescriptor", array_merge($relationaryContext, [$hasManyRelationshipName]));
                    if (sizeof($includedRelatedObjectsFromOneRecursionDeeper) > 0) {
                        foreach ($includedRelatedObjectsFromOneRecursionDeeper as $relatedObjectFromOneRecursionDeeper) {
                            $objectsToIncludeForDataObject[] = $relatedObjectFromOneRecursionDeeper;
                        }
                    }
                }
            }
        }
        return $objectsToIncludeForDataObject;
    }

    /**
     * @param string $relationshipName
     * @param array $relationaryContext
     * @return bool
     * Utility function to check if a relation needs to be described
     */
    private function doesIncludeQueryParameterIncludeRelationship(string $relationshipName, array $relationaryContext): bool {
        $relationshipNameInContext = implode('.', $relationaryContext);
        if ($relationshipNameInContext) {
            $relationshipNameInContext .= '.';
        }
        $relationshipNameInContext .= $relationshipName;

        return in_array($relationshipNameInContext, $this->listOfObjectsToIncludeRelationsOf);
    }

    /**
     * @param DataObject $dataObject
     * @param DataObjectJsonApiDescription $desc
     * @return string
     * Utility fucntion to utility to describe the base url for a single DataObject (e.g. Books/123 for a Book with id 123)
     */
    private static function getDataObjectURLDescriptorForDataObject(DataObject $dataObject, DataObjectJsonApiDescription $desc) {
        return $desc->type_plural . '/' . static::getJSONAPIID($dataObject);
    }

    /**
     * @param DataObject $dataObject
     * @return mixed
     * Utility function to use a UUID instead of SilverStripe's ID
     */
    public static function getJSONAPIID(DataObject $dataObject) {
        return $dataObject->Uuid;//;dbObject('Uuid')->getValue();
    }

    /**
     * @param DataObject $dataObject
     * @param string $contextURL
     * @param array $relationaryContext
     * @return array|null
     * Method to describe a single DataObject as its type, id, attributes and relationsships, i.e. the 'data'-tag of JsonApi
     */
    public function describeDataObjectAsData(DataObject $dataObject, string $contextURL, array $relationaryContext = []) {
        $descriptionForDataObject = $this->getJsonApiDescriptionForClass((string)$dataObject);
        $directUrlToDataObject = static::getDataObjectURLDescriptorForDataObject($dataObject, $descriptionForDataObject);
        if (sizeof($relationaryContext) != 0 && in_array($directUrlToDataObject, $this->listOfDataObjectsDescribed)) {
            return null;
        }
        $this->listOfDataObjectsDescribed[] = $directUrlToDataObject;

        $cachedAttributesOfObject = $descriptionForDataObject->getCache($dataObject);

        if ($cachedAttributesOfObject) {
            $dataDescription = $cachedAttributesOfObject;
        } else {
            $dataDescription = [];
            $dataDescription[JsonApi::TAG_ATTRIBUTES] = $descriptionForDataObject->describeAttributesOfDataObject($dataObject);
            $dataDescription[JsonApi::TAG_TYPE] = static::describeTypeOfDataObject($descriptionForDataObject);
            if ($metaInformation = $descriptionForDataObject->describeMetaOfDataObject($dataObject)){
                $dataDescription[JsonApi::TAG_META] = $metaInformation;
            }
            $dataDescription[JsonApi::TAG_ID] = static::describeIdOfDataObject($dataObject);
            $descriptionForDataObject->cache($dataObject, $dataDescription);
        }



        $relationsOfDataObject = static::describeRelationshipsOfDataObject($dataObject, $descriptionForDataObject, $contextURL, $this);
        if (!empty($relationsOfDataObject)) {
            $dataDescription[JsonApi::TAG_RELATIONSHIPS] = $relationsOfDataObject;
        }
        if ($this->describeLinksOfObjectsAndRelations) {
            $dataDescription[JsonApi::TAG_LINKS] = static::describeLinkOfDataObject($dataObject, $descriptionForDataObject, "$contextURL/");
        }
        return $dataDescription;
    }

    /**
     * @param DataObjectJsonApiDescription $descriptionForDataObject
     * @return string
     * Utility method to return the JsonApi 'Type' for a @see DataObjectJsonApiDescription
     */
    public static function describeTypeOfDataObject(DataObjectJsonApiDescription $descriptionForDataObject) {
        return $descriptionForDataObject->type_singular;
    }

    /**
     * @param DataObject $dataObject
     * @return string
     * JsonApi ID's are always a String, this utility method is used to describe the JsonApi ID as such
     */
    public static function describeIdOfDataObject(DataObject $dataObject) {
        return "" . static::getJSONAPIID($dataObject);
    }

    /**
     * @param DataObject $dataObject
     * @param DataObjectJsonApiDescription $descriptionForDataObject
     * @param string $contextURL
     * @param DataObjectJsonApiEncoder $descr
     * @return array
     * Method to describe all Relations of a DataObject using
     * @see DataObjectJsonApiDescription::$hasOneToRelationMap
     * and
     * @see DataObjectJsonApiDescription::$hasManyToRelationsMap
     * as JsonApi objectIdentifiers
     */
    private static function describeRelationshipsOfDataObject(DataObject $dataObject,
                                                              DataObjectJsonApiDescription $descriptionForDataObject,
                                                              string $contextURL,
                                                              DataObjectJsonApiEncoder $descr) {

        $relationships = [];
        foreach ($descriptionForDataObject->hasOneToRelationMap as $hasOneRelationshipName => $relationshipDefinition) {
            $relationships[$hasOneRelationshipName] = $descr->describeHasOneRelationshipDescriptionForDataObject($dataObject, $hasOneRelationshipName, $relationshipDefinition, $contextURL, $descr);
        }

        foreach ($descriptionForDataObject->hasManyToRelationsMap as $hasManyRelationShipName => $relationshipDefinition) {
            $relationships[$hasManyRelationShipName] = $descr->describeHasManyRelationshipDescriptionForDataObject($dataObject, $hasManyRelationShipName, $relationshipDefinition, $contextURL, $descr);
        }

        return $relationships;
    }

    /**
     * @param DataObject $dataObject
     * @param $relationshipName
     * @param string $contextURL
     * @return string
     * Utility method to retrieve the url needed to access the actual objects behind a relationship
     */
    public function getContextURLForDataObjectRelationshipDescription(DataObject $dataObject, $relationshipName, string $contextURL) {
        $dataObjectURL = $this->getContextURLForDataObject($dataObject, $contextURL);
        return "$dataObjectURL/relationships/$relationshipName";
    }

    /**
     * @param DataObject $dataObject
     * @param string $contextURL
     * @return string
     * Utility method to retrieve the url of the dataObject described
     */
    public function getContextURLForDataObject(DataObject $dataObject, string $contextURL) {
        $desc = $this->getJsonApiDescriptionForClass((string)$dataObject);
        return "$contextURL/$desc->type_plural/" . static::getJSONAPIID($dataObject);
    }

    /**
     * @param DataObject $dataObject
     * @param $relationshipName
     * @param string $contextURL
     * @return string
     * Utility method to retrieve the url needed to access the relationship description of a certain relation
     */
    public function getContextURLForDataObjectRelationship(DataObject $dataObject, $relationshipName, string $contextURL) {
        $dataObjectURL = $this->getContextURLForDataObject($dataObject, $contextURL);
        return "$dataObjectURL/$relationshipName";
    }

    /**
     * @param DataObject $dataObject
     * @param string $type
     * @return array
     * Utility to method to get a JsonApi ObjectIdentifier for a single DataObject with type $type
     */
    private static function describeResourceIdentifierObjectOfDataObject(DataObject $dataObject, string $type) {
        return [
            JsonApi::TAG_TYPE => $type,
            JsonApi::TAG_ID => static::getJSONAPIID($dataObject)
        ];
    }

    /**
     * @param DataObject $dataObject
     * @param string $hasOneRelationshipName
     * @param array $relationshipDefinition
     * @param string $contextURL
     * @param DataObjectJsonApiEncoder $descr
     * @return array
     * Method to describe a single hasOne relation of the requested DataObject
     */
    private static function describeHasOneRelationshipDescriptionForDataObject(DataObject $dataObject,
                                                                               string $hasOneRelationshipName,
                                                                               array $relationshipDefinition,
                                                                               string $contextURL,
                                                                               DataObjectJsonApiEncoder $descr) {
        $relationDataObjectClassDescription = $descr->getJsonApiDescriptionForClass((string)$relationshipDefinition[RELATIONSHIP_RELATED_OBJECT_CLASS]);
        $getHasOneMethod = $relationshipDefinition[RELATIONSHIP_GET_RELATED_OBJECTS_METHOD];
        $relationshipDescription = [];
        $relatedHasOneObject = $dataObject->$getHasOneMethod();
        if ($relatedHasOneObject != null && $relatedHasOneObject->Exists()) {
            if ($descr->describeLinksOfObjectsAndRelations) {
                $relationshipDescription[JsonApi::TAG_LINKS] = [
                    JsonApi::TAG_LINKS_SELF => $descr->getContextURLForDataObjectRelationshipDescription($dataObject, $hasOneRelationshipName, $contextURL),
                    JsonApi::TAG_LINKS_RELATED => $descr->getContextURLForDataObjectRelationship($dataObject, $hasOneRelationshipName, $contextURL),
                ];
            }
            $relationshipDescription[JsonApi::TAG_DATA] = static::describeResourceIdentifierObjectOfDataObject($relatedHasOneObject, $relationDataObjectClassDescription->type_singular);
        } else {
            $relationshipDescription = [
                JsonApi::TAG_DATA => null
            ];
        }
        return $relationshipDescription;
    }

    /**
     * @param DataObject $dataObject
     * @param string $hasManyRelationShipName
     * @param array $relationshipDefinition
     * @param string $contextURL
     * @param DataObjectJsonApiEncoder $descr
     * @return array
     * Method to describe a single hasMany relation of the requested DataObject
     */
    private static function describeHasManyRelationshipDescriptionForDataObject(DataObject $dataObject,
                                                                                string $hasManyRelationShipName,
                                                                                array $relationshipDefinition,
                                                                                string $contextURL,
                                                                                DataObjectJsonApiEncoder $descr) {

        $relationDataObjectClassDescription = $descr->getJsonApiDescriptionForClass((string)$relationshipDefinition[RELATIONSHIP_RELATED_OBJECT_CLASS]);
        $getHasManyMethod = $relationshipDefinition[RELATIONSHIP_GET_RELATED_OBJECTS_METHOD];
        $relationshipDescription = [];
        if ($descr->describeLinksOfObjectsAndRelations) {
            $relationshipDescription[JsonApi::TAG_LINKS] = [
                JsonApi::TAG_LINKS_SELF => $descr->getContextURLForDataObjectRelationshipDescription($dataObject, $hasManyRelationShipName, $contextURL),
                JsonApi::TAG_LINKS_RELATED => $descr->getContextURLForDataObjectRelationship($dataObject, $hasManyRelationShipName, $contextURL)
            ];
        }
        $relationshipDescription[JsonApi::TAG_DATA] = static::describeDataListAsResourceIdentifierObjects($dataObject->$getHasManyMethod(), $relationDataObjectClassDescription->type_singular);
        return $relationshipDescription;
    }

    /**
     * @param $dataList
     * @param string $type
     * @return array
     * Utility method to describe an entire list of DataObjects, i.e. a DataList, as an arrray of ObjectIdentifiers
     */
    private static function describeDataListAsResourceIdentifierObjects($dataList, string $type) {
        $relationship = [];
        foreach ($dataList->toArray() as $dataObject) {
            if ($dataObject->canView(Security::getCurrentUser())) {
                $relationship[] = self::describeResourceIdentifierObjectOfDataObject($dataObject, $type);
            }
        }
        return $relationship;
    }

    /**
     * @param DataObject $dataObject
     * @param DataObjectJsonApiDescription $desc
     * @param $contextURL
     * @return array
     * Utility method to get the self url of a DataObject
     */
    private static function describeLinkOfDataObject(DataObject $dataObject, DataObjectJsonApiDescription $desc, $contextURL) {
        return [JsonApi::TAG_LINKS_SELF => $contextURL . static::getDataObjectURLDescriptorForDataObject($dataObject, $desc)];
    }

    /**
     * @param DataObject $dataObject
     * @param string $relationshipName
     * @param string $contextURL
     * @return array|null
     * Describes a relation with name $relationshipName of DataObject $dataObject as part of the requested data
     */
    public function describeRelationshipForDataObject(DataObject $dataObject,
                                                      string $relationshipName,
                                                      string $contextURL) {
        $descriptionForDataObject = $this->getJsonApiDescriptionForClass((string)$dataObject);
        if (array_key_exists($relationshipName, $descriptionForDataObject->hasOneToRelationMap)) {
            return DataObjectJsonApiEncoder::describeHasOneRelationshipForDataObject($dataObject, $descriptionForDataObject->hasOneToRelationMap[$relationshipName], $contextURL, $this);
        }
        if (array_key_exists($relationshipName, $descriptionForDataObject->hasManyToRelationsMap)) {
            return DataObjectJsonApiEncoder::describeHasManyRelationshipForDataObject($dataObject, $descriptionForDataObject->hasManyToRelationsMap[$relationshipName], $contextURL, $this);
        }

        return null;
    }

    /**
     * @param DataObject $dataObject
     * @param array $relationshipDefinition
     * @param string $contextURL
     * @param DataObjectJsonApiEncoder $descr
     * @return array|null
     * Describes a single hasOneRelation as part of the requested data
     */
    private static function describeHasOneRelationshipForDataObject(DataObject $dataObject, array $relationshipDefinition, string $contextURL, DataObjectJsonApiEncoder $descr) {
        $relationshipObject = null;
        $getHasOneMethod = $relationshipDefinition[RELATIONSHIP_GET_RELATED_OBJECTS_METHOD];
        $relatedDataObject = $dataObject->$getHasOneMethod();
        if ($relatedDataObject && $objectDescription = $descr->describeDataObjectAsData($relatedDataObject, $contextURL)) {
            $relationshipObject = $objectDescription;
        }
        return $relationshipObject;
    }

    /**
     * @param DataObject $dataObject
     * @param array $relationshipDefinition
     * @param string $contextURL
     * @param DataObjectJsonApiEncoder $descr
     * @return array
     * Describes a single hasManyRelation as part of the requested data
     */
    private static function describeHasManyRelationshipForDataObject(DataObject $dataObject, array $relationshipDefinition, string $contextURL, DataObjectJsonApiEncoder $descr): array {
        $relationshipObjectList = [];

        $getHasManyMethod = $relationshipDefinition[RELATIONSHIP_GET_RELATED_OBJECTS_METHOD];
        foreach ($dataObject->$getHasManyMethod() as $relatedDataObject) {
            if ($relatedDataObject->canView(Security::getCurrentUser())) {
                if ($objectDescription = $descr->describeDataObjectAsData($relatedDataObject, $contextURL)) {
                    $relationshipObjectList[] = $objectDescription;
                }
            }
        }

        return $relationshipObjectList;
    }

    /**
     * @param DataObject $dataObject
     * @param string $relationshipName
     * @param string $contextURL
     * @return array|null
     * Describe a single Relationships with $relationshipName of DataObject $dataobject
     */
    public function describeRelationshipDescriptionForDataObject(DataObject $dataObject,
                                                                 string $relationshipName,
                                                                 string $contextURL) {
        $descriptionForDataObject = $this->getJsonApiDescriptionForClass((string)$dataObject);
        foreach ($descriptionForDataObject->hasOneToRelationMap as $hasOneRelationshipName => $relationshipDefinition) {
            if ($relationshipName == $hasOneRelationshipName) {
                return DataObjectJsonApiEncoder::describeHasOneRelationshipDescriptionForDataObject($dataObject, $hasOneRelationshipName, $relationshipDefinition, $contextURL, $this);
            }
        }

        foreach ($descriptionForDataObject->hasManyToRelationsMap as $hasManyRelationShipName => $relationshipDefinition) {
            if ($relationshipName == $hasManyRelationShipName) {
                return DataObjectJsonApiEncoder::describeHasManyRelationshipDescriptionForDataObject($dataObject, $hasManyRelationShipName, $relationshipDefinition, $contextURL, $this);
            }
        }
        return null;
    }

    /**
     * @param array $sparseFieldsPerType
     * Method that limits what fields are retrieved per Type
     */
    public function setSparseFields(array $sparseFieldsPerType) {
        foreach ($sparseFieldsPerType as $type => $commaSeperatedFields) {
            foreach ($this->classToDescriptionMap as $class => $description) {
                if ($description->type_plural == $type) {
                    $whiteListedAttributes = explode(',', $commaSeperatedFields);

                    $filterFunction = function ($field) use ($whiteListedAttributes) {
                        if (in_array($field, $whiteListedAttributes)) {
                            return true;
                        }
                        return false;
                    };

                    $description->fieldToAttributeMap = array_filter($description->fieldToAttributeMap, $filterFunction);
                    $description->hasOneToRelationMap = array_filter($description->hasOneToRelationMap, $filterFunction, ARRAY_FILTER_USE_KEY);
                    $description->hasManyToRelationsMap = array_filter($description->hasManyToRelationsMap, $filterFunction, ARRAY_FILTER_USE_KEY);
                    $this->classToDescriptionMap[$class] = $description;
                }
            }
        }
    }

    /**
     * @param $pageSize
     * @param $pageNumber
     * Method used paginate through a collection of items
     */
    public function setPagination($pageSize, $pageNumber) {
        $this->pageSize = floor($pageSize);
        $this->pageNumber = floor($pageNumber);
    }

    public function setTotalCount($totalCount) {
        $this->totalResultCount = $totalCount;
    }
}