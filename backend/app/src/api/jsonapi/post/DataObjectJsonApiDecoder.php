<?php

use Ramsey\Uuid\Uuid;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\ViewableData;
use SurfSharekit\Api\JsonApi;
use Zooma\SilverStripe\Models\ApiObject;

/**
 * Class DataObjectJsonApiDecoder
 * @author Matthijs Hilgers
 * @protocol https://jsonapi.org/
 * This class is used to decode data from a JsonApi PATCH or POST request and apply it on SilverStripe DataObjects
 */
class DataObjectJsonApiDecoder {
    /**
     * @var array $classToDescriptionMap
     * The map of DataObject types to their @see DataObjectJsonApiDescription
     */
    private $classToDescriptionMap = [];
    /**
     * @var array $errorLog A multitude of errors can arise during a single request, this variable hold all of them
     */
    public $errorLog = [];

    public static $REPLACE = 1;
    public static $ADD = 2;
    public static $REMOVE = 3;

    public function __construct($classToDescriptionMap) {
        $this->classToDescriptionMap = $classToDescriptionMap;
    }

    /**
     * @param $objectClass
     * @param $jsonData
     * @param $allowUpdatesToObject
     * @return DataObject|null
     * This method is the entry point during a object update or insertion and handles to update to the object itself and its relations
     */
    public function createOrUpdateObjectWithRelationsFromDataJSON($objectClass, $jsonData, $allowUpdatesToObject) {
        if ($newDataObject = $this->createOrUpdateObjectFromDataJSON($objectClass, $jsonData, $allowUpdatesToObject)) {
            try {
                $newDataObject->write();
                $newDataObject = $this->setRelationsFromObjectDataJSON($newDataObject, $jsonData);
                if (count($this->errorLog) == 0) {
                    $newDataObject->write();
                }
            } catch (Exception $e) {
                try {
                    if ($newDataObject instanceof ApiObject) {
                        throw $e; // ApiObject doesn't have any relations, only attributes
                    }
                    $newDataObject = $this->setRelationsFromObjectDataJSON($newDataObject, $jsonData);
                    if (count($this->errorLog) == 0) {
                        $newDataObject->write();
                    }
                } catch (Exception $e2) {
                    $this->errorLog[] = [
                        JsonApi::TAG_ERROR_TITLE => 'Write exception',
                        JsonApi::TAG_ERROR_DETAIL => $e->getMessage(),
                        JsonApi::TAG_ERROR_CODE => 'DOJAD_028'
                    ];
                }
            }

            if (sizeof($this->errorLog) == 0) {
                return $newDataObject;
            }
        }
    }

    /**
     * @param $objectClass
     * @param $jsonData
     * @param $updateableObject
     * @return DataObject|null
     * This method handles the actual object updating and inserting
     */
    public function createOrUpdateObjectFromDataJSON($objectClass, $jsonData, $updateableObject) {
        $descriptionForObjectClass = $this->getJsonApiDescriptionForClass($objectClass);
        if (isset($jsonData[JsonApi::TAG_TYPE]) && ($type = $jsonData[JsonApi::TAG_TYPE])) {
            if ($type == $descriptionForObjectClass->type_singular) {
                /**
                 * @var $dataObjectToEdit DataObject
                 */
                if ($updateableObject) {
                    if (isset($jsonData[JsonApi::TAG_ID]) && ($sentID = $jsonData[JsonApi::TAG_ID])) {
                        if (!Uuid::isValid($sentID)) {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => 'ID not properly formatted',
                                JsonApi::TAG_ERROR_DETAIL => 'POSTed ID is not a UUID',
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_001'
                            ];
                            return null;
                        }

                        if (DataObjectJsonApiEncoder::getJSONAPIID($updateableObject) != $sentID) {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => 'ID mismatch',
                                JsonApi::TAG_ERROR_DETAIL => 'Mismatch of objectID in request body and URL',
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_019'
                            ];
                            return null;
                        }

                        $dataObjectToEdit = $updateableObject;
                    } else {
                        $this->errorLog[] = [
                            JsonApi::TAG_ERROR_TITLE => 'ID not found in data body',
                            JsonApi::TAG_ERROR_DETAIL => 'Missing ID in data body',
                            JsonApi::TAG_ERROR_CODE => 'DOJAD_018'
                        ];
                        return null;
                    }
                } else {
                    $dataObjectToEdit = new $objectClass();
                    $dataObjectToEdit->populateDefaults();

                    if (isset($jsonData[JsonApi::TAG_ID]) && ($sentID = $jsonData[JsonApi::TAG_ID])) {
                        if (!Uuid::isValid($sentID)) {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => 'ID not properly formatted',
                                JsonApi::TAG_ERROR_DETAIL => 'POSTed ID is not a UUID',
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_001'
                            ];
                            return null;
                        }
                        $preExistingObjectWithClientSentID = UuidExtension::getByUuid($objectClass, $sentID);
                        if ($preExistingObjectWithClientSentID && $preExistingObjectWithClientSentID->Exists()) {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => 'Conflicting ID',
                                JsonApi::TAG_ERROR_DETAIL => 'POSTed ID conflicts with preexisting object',
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_002'
                            ];
                            return null;
                        }
                        try {
                            $dataObjectToEdit->owner->Uuid = Uuid::fromString($sentID)->getBytes();
                        } catch (Exception $e) {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => 'Invalid ID',
                                JsonApi::TAG_ERROR_DETAIL => 'ID is not a valid UUID',
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_027'
                            ];
                            return null;
                        }
                    }
                }

                $attributes = isset($jsonData['attributes']) ? $jsonData['attributes'] : null;
                foreach ($descriptionForObjectClass->attributeToFieldMap as $jsonKey => $fieldKey) {
                    if ($attributes && array_key_exists($jsonKey, $attributes)) {
                        $attributeValue = $attributes[$jsonKey];
                        try {
                            $dataObjectToEdit->$fieldKey = $attributeValue;
                        } catch (Exception $e) {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => "Couldn't set property $jsonKey",
                                JsonApi::TAG_ERROR_DETAIL => $e->getMessage(),
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_003'
                            ];
                        }
                        unset($attributes[$jsonKey]);
                    }
                }

                if (is_array($attributes) && sizeof($attributes) > 0) {
                    $this->errorLog[] = [
                        JsonApi::TAG_ERROR_TITLE => "Couldn't resolve all attributes to fields",
                        JsonApi::TAG_ERROR_DETAIL => implode(', ', array_keys($attributes)) . ' : not part of the POST or PATCH object type',
                        JsonApi::TAG_ERROR_CODE => 'DOJAD_004'
                    ];
                } else {
                    $validationResult = $dataObjectToEdit->validate();
                    foreach ($validationResult->getMessages() as $code => $validationMessage) {
                        $this->errorLog[] = [
                            JsonApi::TAG_ERROR_TITLE => 'Object validation error',
                            JsonApi::TAG_ERROR_DETAIL => $validationMessage['message'],
                            JsonApi::TAG_ERROR_CODE => $code
                        ];
                    }
                    if (sizeof($this->errorLog) == 0) {
                        return $dataObjectToEdit;
                    }
                }
            } else {
                $this->errorLog[] = [
                    JsonApi::TAG_ERROR_TITLE => 'Object type AND URL don\'t match',
                    JsonApi::TAG_ERROR_DETAIL => 'Please check the url and type of object you\'d like to POST',
                    JsonApi::TAG_ERROR_CODE => 'DOJAD_005'
                ];
            }
        } else {
            $this->errorLog[] = [JsonApi::TAG_ERROR_TITLE => 'Object type mismatch',
                JsonApi::TAG_ERROR_DETAIL => "Cannot create new '$objectClass' instance",
                JsonApi::TAG_ERROR_CODE => 'DOJAD_006'];
        }
    }

    /**
     * @param DataObject $dataObject
     * @param $jsonData
     * @return DataObject
     * This method is used to update or create a multitude of relations the inserted or updated object has to other objects
     */
    private function setRelationsFromObjectDataJSON(ViewableData $dataObject, $jsonData) {
        if (isset($jsonData[JsonApi::TAG_RELATIONSHIPS]) && ($postedRelationships = $jsonData[JsonApi::TAG_RELATIONSHIPS])) {
            foreach ($postedRelationships as $postedRelationshipName => $relationshipInfo) {
                $this->setRelationFromRelationJSON($dataObject, $postedRelationshipName, $relationshipInfo, static::$REPLACE);
            }
        }
        if (sizeof($this->errorLog) == 0) {
            return $dataObject;
        }
    }

    /**
     * @param DataObject $dataObject
     * @param $postedRelationshipName
     * @param $relationshipInfo
     * @param $editMode
     * This method updates or creates a single relation to one or more DataObjects for the inserted or updated DataObject
     */

    public function setRelationFromRelationJSON(DataObject $dataObject, $postedRelationshipName, $relationshipInfo, $editMode = null) {
        $editMode = $editMode ?: static::$REPLACE;

        $objectClass = $dataObject->getClassName();
        $descriptionForObjectClass = $this->getJsonApiDescriptionForClass($objectClass);
        $hasOneRelationNames = array_keys($descriptionForObjectClass->hasOneToRelationMap);
        $hasManyRelationNames = array_keys($descriptionForObjectClass->hasManyToRelationsMap);

        if ($relationshipInfo == null) {
            $this->errorLog[] = [
                JsonApi::TAG_ERROR_TITLE => 'Forgot relationship data tag',
                JsonApi::TAG_ERROR_CODE => 'DOJAD_029'
            ];
            return;
        }

        if (array_key_exists('data', $relationshipInfo)) { // 'data' can be null, so isset doesn't work
            $postedRelationshipData = $relationshipInfo['data'];
            $relatedDataObjectClass = $this->getDataObjectClassForRelationship($descriptionForObjectClass, $postedRelationshipName);
            if (!$relatedDataObjectClass) {
                $this->errorLog[] = [
                    JsonApi::TAG_ERROR_TITLE => 'Invalid relationship',
                    JsonApi::TAG_ERROR_DETAIL => 'Please check the relationships you have sent',
                    JsonApi::TAG_ERROR_CODE => 'DOJAD_021'
                ];
                return;
            }
            $relatedDescription = $this->getJsonApiDescriptionForClass($relatedDataObjectClass);

            $descriptionOfRelation = null;
            if (isset($descriptionForObjectClass->hasManyToRelationsMap[$postedRelationshipName])) {
                $descriptionOfRelation = $descriptionForObjectClass->hasManyToRelationsMap[$postedRelationshipName];
            } else if (isset($descriptionForObjectClass->hasOneToRelationMap[$postedRelationshipName])) {
                $descriptionOfRelation = $descriptionForObjectClass->hasOneToRelationMap[$postedRelationshipName];
            }

            $permissionAddMethodName = isset($descriptionOfRelation[RELATIONSHIP_ADD_PERMISSION_METHOD]) ? $descriptionOfRelation[RELATIONSHIP_ADD_PERMISSION_METHOD] : null;
            $permissionAddMethod = function ($relObject) use ($dataObject, $permissionAddMethodName) {
                if ($permissionAddMethodName && $dataObject->hasMethod($permissionAddMethodName)) {
                    return $dataObject->$permissionAddMethodName($relObject);
                }
                return $relObject->canEdit(Security::getCurrentUser());
            };

            $permissionRemoveMethodName = isset($descriptionOfRelation[RELATIONSHIP_REMOVE_PERMISSION_METHOD]) ? $descriptionOfRelation[RELATIONSHIP_REMOVE_PERMISSION_METHOD] : null;
            $permissionRemoveMethod = function ($relObject) use ($dataObject, $permissionRemoveMethodName) {
                if ($permissionRemoveMethodName && $dataObject->hasMethod($permissionRemoveMethodName)) {
                    return $dataObject->$permissionRemoveMethodName($relObject);
                }
                return $relObject->canEdit(Security::getCurrentUser());
            };

            if (in_array($postedRelationshipName, $hasManyRelationNames)) { //posted relation is a hasMany relation
                if ($postedRelationshipData == null && !is_array($postedRelationshipData)) {
                    $this->errorLog[] = [
                        JsonApi::TAG_ERROR_TITLE => 'Posted empty hasOne to a hasMany relation',
                        JsonApi::TAG_ERROR_DETAIL => 'Try posting [] instead of null for relationship with name ' . $postedRelationshipName,
                        JsonApi::TAG_ERROR_CODE => 'DOJAD_007'
                    ];
                    return;
                } else if (DataObjectJsonApiDecoder::isAssocioativeArray($postedRelationshipData)) {
                    $this->errorLog[] = [
                        JsonApi::TAG_ERROR_TITLE => 'Posted hasOne to a hasMany relation',
                        JsonApi::TAG_ERROR_DETAIL => 'Try posting an array of object identifiers instead of single data object relationship with name ' . $postedRelationshipName,
                        JsonApi::TAG_ERROR_CODE => 'DOJAD_007'
                    ];
                    return;
                }

                $hasManyMethodName = $descriptionOfRelation[RELATIONSHIP_GET_RELATED_OBJECTS_METHOD];
                $hasManyMethod = function () use ($dataObject, $hasManyMethodName) {
                    return $dataObject->$hasManyMethodName();
                };

                if ($editMode == static::$REPLACE) {
                    foreach ($hasManyMethod() as $relObject) {
                        if (!$permissionRemoveMethod($relObject)) {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => 'Missing permission to edit this relationship',
                                JsonApi::TAG_ERROR_DETAIL => 'You will need edit permission for both the updated object and related object ' . $relObject->Uuid,
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_024'
                            ];
                            return;
                        }
                    }
                    if ($dataObject instanceof Member && $hasManyMethod == "Groups") {
                        // When removing a person from a group, this has to be done from the Members() relation on Group instead of the Groups() relation on Member
                        // If a Member is removed from a group using the Groups() relation on Member, the ManyManyListExtended logic will nog be triggered
                        // as the Groups() relation on Member is a belongs_many_many relation
                        foreach ($hasManyMethod() as $group) {
                            $group->remove($dataObject);
                        }
                    } else {
                        $hasManyMethod()->removeAll(); //remove preexisting relationships
                    }
                }

                foreach ($postedRelationshipData as $postedRelationshipObjectIdentifier) {
                    if (!$this->validatePostedRelationshipIdentifier($postedRelationshipObjectIdentifier, $relatedDescription->type_plural, $postedRelationshipName)) {
                        return;
                    }
                    $uuid = null;
                    try {
                        $uuid = Uuid::fromString($postedRelationshipObjectIdentifier[JsonApi::TAG_ID]);
                    } catch (Exception $e) {
                        $this->errorLog[] = [
                            JsonApi::TAG_ERROR_TITLE => 'Invalid ID',
                            JsonApi::TAG_ERROR_DETAIL => 'ID is not a valid UUID',
                            JsonApi::TAG_ERROR_CODE => 'DOJAD_027'
                        ];
                        return null;
                    }
                    $relatedObject = UuidExtension::getByUuid($relatedDataObjectClass, $uuid);

                    if ($relatedObject == null || !$relatedObject->Exists()) {
                        $this->errorLog[] = [
                            JsonApi::TAG_ERROR_TITLE => 'Using identifier of none existing object',
                            JsonApi::TAG_ERROR_DETAIL => $relatedDataObjectClass . ' with ID ' . $postedRelationshipObjectIdentifier[JsonApi::TAG_ID] . ' does not exist',
                            JsonApi::TAG_ERROR_CODE => 'DOJAD_009'
                        ];
                        return;
                    }

                    if ($editMode == static::$ADD || $editMode == static::$REPLACE) {
                        if ($permissionAddMethod($relatedObject)) {
                            if ($dataObject instanceof Member && $relatedObject instanceof Group) {
                                // When adding a person to a group, this has to be done from the Members() relation on Group instead of the Groups() relation on Member
                                // If a Member is added to a group using the Groups() relation on Member, the ManyManyListExtended logic will nog be triggered
                                // as the Groups() relation on Member is a belongs_many_many relation
                                $relatedObject->Members()->Add($dataObject);
                            } else {
                                $hasManyMethod()->Add($relatedObject);
                            }
                        } else {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => 'Missing permission to edit this relationship',
                                JsonApi::TAG_ERROR_DETAIL => 'You will need edit permission for both the updated object and related object ' . $relatedObject->Uuid,
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_022B'
                            ];
                            return;
                        }
                    } else if ($editMode == static::$REMOVE) {
                        if (!in_array($relatedObject->ID, $hasManyMethod()->column('ID'))) {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => 'Object is not part of relation',
                                JsonApi::TAG_ERROR_DETAIL => 'You can only remove already existing related objects for relation ' . $relatedObject->Uuid,
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_025'
                            ];
                            return;
                        }
                        if ($permissionRemoveMethod($relatedObject)) {
                            if ($dataObject instanceof Member && $relatedObject instanceof Group) {
                                // When removing a person from a group, this has to be done from the Members() relation on Group instead of the Groups() relation on Member
                                // If a Member is removed from a group using the Groups() relation on Member, the ManyManyListExtended logic will nog be triggered
                                // as the Groups() relation on Member is a belongs_many_many relation
                                $relatedObject->Members()->Remove($dataObject);
                            } else {
                                $hasManyMethod()->Remove($relatedObject);
                            }
                        } else {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => 'Missing permission to edit this relationship',
                                JsonApi::TAG_ERROR_DETAIL => 'You will need edit permission for both the updated object and related object ' . $relatedObject->Uuid,
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_022C'
                            ];
                            return;
                        }
                    }
                }
            } else if (in_array($postedRelationshipName, $hasOneRelationNames)) { //posted relationship is a hasOne relation
                if (is_array($postedRelationshipData) && !DataObjectJsonApiDecoder::isAssocioativeArray($postedRelationshipData)) {
                    $this->errorLog[] = [
                        JsonApi::TAG_ERROR_TITLE => 'Posted hasMany relation to a hasOne relation',
                        JsonApi::TAG_ERROR_DETAIL => 'Try posting a single data object instead of an array for relationship with name ' . $postedRelationshipName,
                        JsonApi::TAG_ERROR_CODE => 'DOJAD_017'
                    ];
                    return;
                }

                $hasOneField = $descriptionOfRelation[RELATIONSHIP_GET_RELATED_OBJECTS_METHOD];

                try {
                    $previouslyRelatedObject = $dataObject->$hasOneField();
                } catch (Exception $e) {
                    $previouslyRelatedObject = $dataObject->extend($hasOneField);
                }

                if ($previouslyRelatedObject && $previouslyRelatedObject->exists() && !$permissionRemoveMethod($previouslyRelatedObject)) {
                    $this->errorLog[] = [
                        JsonApi::TAG_ERROR_TITLE => 'Missing permission to edit this relationship',
                        JsonApi::TAG_ERROR_DETAIL => 'You will need edit permission for both the updated object and related object ',
                        JsonApi::TAG_ERROR_CODE => 'DOJAD_022A'
                    ];
                    return;
                }

                $dataObject->setField($hasOneField . 'ID', null);    //remove preexisting relationship

                if ($postedRelationshipData) {
                    if (!$this->validatePostedRelationshipIdentifier($postedRelationshipData, $relatedDescription->type_plural, $postedRelationshipName)) {
                        return;
                    }

                    try {
                        $uuid = Uuid::fromString($postedRelationshipData[JsonApi::TAG_ID]);
                    } catch (Exception $e) {
                        $this->errorLog[] = [
                            JsonApi::TAG_ERROR_TITLE => 'Invalid ID',
                            JsonApi::TAG_ERROR_DETAIL => 'ID is not a valid UUID',
                            JsonApi::TAG_ERROR_CODE => 'DOJAD_027'
                        ];
                        return null;
                    }
                    $relatedObject = UuidExtension::getByUuid($relatedDataObjectClass, $uuid);
                    if ($relatedObject == null || !$relatedObject->Exists()) {
                        $this->errorLog[] = [
                            JsonApi::TAG_ERROR_TITLE => 'Using identifier of none existing object or incorrect type',
                            JsonApi::TAG_ERROR_DETAIL => $relatedDataObjectClass . ' with ID ' . $postedRelationshipData[JsonApi::TAG_ID] . ' does not exist',
                            JsonApi::TAG_ERROR_CODE => 'DOJAD_011'
                        ];
                        return;
                    }

                    if ($editMode == static::$REMOVE && (!$previouslyRelatedObject || ($previouslyRelatedObject && $previouslyRelatedObject->ID != $relatedObject->ID))) {
                        $this->errorLog[] = [
                            JsonApi::TAG_ERROR_TITLE => 'Object is not part of relation',
                            JsonApi::TAG_ERROR_DETAIL => 'You can only remove already existing related objects for relation ' . $relatedObject->Uuid,
                            JsonApi::TAG_ERROR_CODE => 'DOJAD_025'
                        ];
                        return;
                    }

                    if ($editMode == static::$REPLACE || $editMode == static::$ADD) {
                        if ($permissionAddMethod($relatedObject) && $permissionRemoveMethod($relatedObject)) {
                            $hasOneFieldName = $hasOneField . 'ID';
                            $dataObject->$hasOneFieldName = $relatedObject->ID;
                        } else {
                            $this->errorLog[] = [
                                JsonApi::TAG_ERROR_TITLE => 'Missing permission to edit this relationship',
                                JsonApi::TAG_ERROR_DETAIL => 'You will need edit permission for both the updated object and related object or special relation permissions',
                                JsonApi::TAG_ERROR_CODE => 'DOJAD_023'
                            ];
                            return;
                        }
                    }
                }
            } else {
                $this->errorLog[] = [
                    JsonApi::TAG_ERROR_TITLE => 'None existing relation',
                    JsonApi::TAG_ERROR_DETAIL => 'Describing none existing relationship with ' . $postedRelationshipName,
                    JsonApi::TAG_ERROR_CODE => 'DOJAD_011'
                ];
            }
        } else {
            $this->errorLog[] = [
                JsonApi::TAG_ERROR_TITLE => 'Missing relation data',
                JsonApi::TAG_ERROR_DETAIL => 'Missing data body in relationship with name ' . $postedRelationshipName,
                JsonApi::TAG_ERROR_CODE => 'DOJAD_012'
            ];
        }
    }

    /**
     * @param string $className
     * @return DataObjectJsonApiDescription
     * Utility method to easily access the JsonApi description of the DataObject classname given
     */
    private function getJsonApiDescriptionForClass(string $className) {
        return $this->classToDescriptionMap[$className];
    }

    /**
     * @param DataObjectJsonApiDescription $description
     * @param string $relationshipName
     * @return mixed
     * Utility method to retrieve the DataObject type behind the relation with Relationship
     */
    private function getDataObjectClassForRelationship(DataObjectJsonApiDescription $description, string $relationshipName) {
        if (isset($description->hasOneToRelationMap[$relationshipName]) && $relation = $description->hasOneToRelationMap[$relationshipName]) {
            return $relation[RELATIONSHIP_RELATED_OBJECT_CLASS];
        }

        if (isset($description->hasManyToRelationsMap[$relationshipName]) && $relation = $description->hasManyToRelationsMap[$relationshipName]) {
            return $relation[RELATIONSHIP_RELATED_OBJECT_CLASS];
        }
    }

    /**
     * @param array $arr
     * @return bool
     * Utility method to check if array only uses integers as keys or not
     */
    function isAssocioativeArray(array $arr) {
        if ([] === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * @param array $postedRelationship
     * @param $forceType
     * @param $relationshipName
     * @return bool
     * Utility method to check whether or not posted relationship information is correctly formatted
     */
    private function validatePostedRelationshipIdentifier(array $postedRelationship, $forceType, $relationshipName) {
        $isCorrect = true;
        $postedRelationshipType = isset($postedRelationship[JsonApi::TAG_TYPE]) ? isset($postedRelationship[JsonApi::TAG_TYPE]) : null;
        if (!$postedRelationshipType) {
            $this->errorLog[] = [
                JsonApi::TAG_ERROR_TITLE => 'Missing type for relationship identifier',
                JsonApi::TAG_ERROR_DETAIL => 'Missing type in object of relationship: ' . $relationshipName,
                JsonApi::TAG_ERROR_CODE => 'DOJAD_013'
            ];
            $isCorrect = false;
        } else if ($forceType != $postedRelationshipType) {
            $this->errorLog[] = [
                JsonApi::TAG_ERROR_TITLE => 'Posted incorrectly typed object',
                JsonApi::TAG_ERROR_DETAIL => 'Type in object incorrect in relationship: ' . $relationshipName,
                JsonApi::TAG_ERROR_CODE => 'DOJAD_014'
            ];
            $isCorrect = false;
        }
        if (!isset($postedRelationship[JsonApi::TAG_ID])) {
            $this->errorLog[] = [
                JsonApi::TAG_ERROR_TITLE => 'Missing id for relationship identifier',
                JsonApi::TAG_ERROR_DETAIL => 'Missing id in object of relationship: ' . $relationshipName,
                JsonApi::TAG_ERROR_CODE => 'DOJAD_015'
            ];
            $isCorrect = false;
        } else if ($postedId = $postedRelationship[JsonApi::TAG_ID]) {
            try {
                Uuid::fromString($postedId);
            } catch (Exception $exception) {
                $this->errorLog[] = [
                    JsonApi::TAG_ERROR_TITLE => 'Id is invalid UUID',
                    JsonApi::TAG_ERROR_DETAIL => 'Id is not a valid UUID in object of relationship: ' . $relationshipName,
                    JsonApi::TAG_ERROR_CODE => 'DOJAD_016'
                ];
                $isCorrect = false;
            }
        }
        return $isCorrect;
    }

    /**
     * @param $requestedObjectType
     * @return int|string|null
     * Utility method to easily retrieve the @see DataObjectJsonApiDescription for a certain ObjectType
     */
    public function getDataObjectClassFromType($requestedObjectType) {
        foreach ($this->classToDescriptionMap as $class => $description) {
            if ($description->type_plural == $requestedObjectType) {
                return $class;
            }
        }
        return null;
    }

    public function updateObjectRelationFromDataJson($objectClass, $jsonData, $updateableObject, $relationshipUpdating, $editMode) {
        try {
            $this->setRelationFromRelationJSON($updateableObject, $relationshipUpdating, $jsonData, $editMode);
        } catch (Exception $e) {
            $this->errorLog[] = [
                JsonApi::TAG_ERROR_TITLE => 'Invalid relationship editing action',
                JsonApi::TAG_ERROR_DETAIL => $e->getMessage(),
                JsonApi::TAG_ERROR_CODE => 'DOJAD_026'
            ];
        }
        return $updateableObject;
    }
}