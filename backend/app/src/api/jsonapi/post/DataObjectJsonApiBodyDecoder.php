<?php

use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use SurfSharekit\Api\InternalJsonApiController;
use SurfSharekit\Api\JsonApi;
use SurfSharekit\Api\JsonApiOperations;
use SurfSharekit\Models\Helper\Logger;

/**
 * Class DataObjectJsonApiBodyDecoder
 * This class is the entry point to POST or PATCH a certain JSON Api described DataObject with a DataObjectJsonApiDecoder.
 */
class DataObjectJsonApiBodyDecoder {

    /**
     * @param $postedOperation
     * @param DataObjectJsonApiDecoder $deco
     * @return array|bool|\SilverStripe\ORM\DataObject|null
     * Handles a single operation of the JSONAPI Operation extension
     */
    public static function handleOperation($postedOperation, DataObjectJsonApiDecoder $deco) {
        $operationType = isset($postedOperation[JsonApiOperations::TAG_OPERATION]) ? $postedOperation[JsonApiOperations::TAG_OPERATION] : null;
        if (in_array($operationType, [JsonApiOperations::TAG_OPERATION_ADD, JsonApiOperations::TAG_OPERATION_UPDATE, JsonApiOperations::TAG_OPERATION_REMOVE])) {
            $postedObjectID = null;
            $postedObjectType = null;

            if (array_key_exists(JsonApiOperations::TAG_HYPERLINK_REFERENCE, $postedOperation) && array_key_exists(JsonApiOperations::TAG_REFERENCE, $postedOperation)) {
                return [
                    JsonApi::TAG_ERRORS => [[
                        JsonApi::TAG_ERROR_TITLE => 'Duplicate reference',
                        JsonApi::TAG_ERROR_DETAIL => 'Add either a href or a ref object, not both',
                        JsonApi::TAG_ERROR_CODE => 'DOJABD_006'
                    ]]
                ];
            } else if (array_key_exists(JsonApiOperations::TAG_REFERENCE, $postedOperation)) {
                $postedObjectRef = $postedOperation[JsonApiOperations::TAG_REFERENCE];
                if (isset($postedObjectRef[JsonApi::TAG_TYPE])) {
                    $postedObjectType = $postedObjectRef[JsonApi::TAG_TYPE];
                    if (isset($postedObjectRef[JsonApi::TAG_ID])) {
                        $postedObjectID = $postedObjectRef[JsonApi::TAG_ID];
                    }
                } else {
                    return [
                        JsonApi::TAG_ERRORS => [[
                            JsonApi::TAG_ERROR_TITLE => 'Incorrectly formatted ref, missing type',
                            JsonApi::TAG_ERROR_DETAIL => 'example:  {type:"comments", id:"12321-42232-2222"}}',
                            JsonApi::TAG_ERROR_CODE => 'DOJABD_004'
                        ]]
                    ];
                }
            } else if (array_key_exists(JsonApiOperations::TAG_HYPERLINK_REFERENCE, $postedOperation)) {
                $postedObjectHref = $postedOperation[JsonApiOperations::TAG_HYPERLINK_REFERENCE];
                $hrefParts = explode("/", $postedObjectHref);

                if (sizeof($hrefParts) == 2) {
                    $postedObjectID = $hrefParts[0];
                    $postedObjectType = $hrefParts[1];
                }

                if (!$postedObjectID || !$postedObjectType) {
                    return [
                        JsonApi::TAG_ERRORS => [[
                            JsonApi::TAG_ERROR_TITLE => 'Incorrectly formatted href, or accessing relationship',
                            JsonApi::TAG_ERROR_DETAIL => 'example:  comments/12321-42232-2222',
                            JsonApi::TAG_ERROR_CODE => 'DOJABD_003'
                        ]]
                    ];
                }
            } else {
                return [
                    JsonApi::TAG_ERRORS => [[
                        JsonApi::TAG_ERROR_TITLE => 'Missing reference',
                        JsonApi::TAG_ERROR_DETAIL => 'Add either a href or a ref object to the operation',
                        JsonApi::TAG_ERROR_CODE => 'DOJABD_007'
                    ]]
                ];
            }

            if ($operationType == JsonApiOperations::TAG_OPERATION_ADD) {
                if ($postedObjectID) {
                    return [
                        JsonApi::TAG_ERRORS => [[
                            JsonApi::TAG_ERROR_TITLE => 'Cannot POST to an already existing object',
                            JsonApi::TAG_ERROR_DETAIL => 'Post to a collection, not an object, example: comments, NOT comments/12321-42232-2222',
                            JsonApi::TAG_ERROR_CODE => 'DOJABD_005'
                        ]]
                    ];
                } else {
                    $dataObjectClass = $deco->getDataObjectClassFromType($postedObjectType);
                    return static::changeObjectWithTypeFromRequestBody($dataObjectClass, $postedOperation, $deco, null, false);
                }
            } else if ($operationType == JsonApiOperations::TAG_OPERATION_UPDATE) {
                if (!$postedObjectID) {
                    return [
                        JsonApi::TAG_ERRORS => [[
                            JsonApi::TAG_ERROR_TITLE => 'Missing ID in ref or href',
                            JsonApi::TAG_ERROR_DETAIL => 'Cannot update without ID',
                            JsonApi::TAG_ERROR_CODE => 'DOJABD_009'
                        ]]
                    ];
                }
                $dataObjectClass = $deco->getDataObjectClassFromType($postedObjectType);
                if ($objectToUpdate = UuidExtension::getByUuid($dataObjectClass, $postedObjectID)) {
                    return DataObjectJsonApiBodyDecoder::changeObjectWithTypeFromRequestBody($dataObjectClass, $postedOperation, $deco, $objectToUpdate);
                } else {
                    return InternalJsonApiController::objectNotFoundJsonApiBodyError();
                }
            } else if ($operationType == JsonApiOperations::TAG_OPERATION_REMOVE) {
                if (!$postedObjectID) {
                    return [
                        JsonApi::TAG_ERRORS => [[
                            JsonApi::TAG_ERROR_TITLE => 'Missing ID in ref or href',
                            JsonApi::TAG_ERROR_DETAIL => 'Cannot remove without ID',
                            JsonApi::TAG_ERROR_CODE => 'DOJABD_010'
                        ]]
                    ];
                }
                $dataObjectClass = $deco->getDataObjectClassFromType($postedObjectType);
                if ($objectToRemove = UuidExtension::getByUuid($dataObjectClass, $postedObjectID)) {
                    $objectToRemove->delete();
                    return true;
                } else {
                    return InternalJsonApiController::objectNotFoundJsonApiBodyError();
                }
            }
        } else {
            return [JsonApi::TAG_ERRORS =>
                [[JsonApi::TAG_ERROR_TITLE => 'Missing or incorrect operation type',
                    JsonApi::TAG_ERROR_DETAIL => 'Add [add, update, remove] as operation type',
                    JsonApi::TAG_ERROR_CODE => 'DOJABD_002']]];
        }
    }

    /**
     * @param $objectClass
     * @param $requestBody
     * @param DataObjectJsonApiDecoder $deco
     * @param null $updateableObject
     * @return array|\SilverStripe\ORM\DataObject|null
     * Function to insert a single JsonApi described DataObject.
     * If $allowUpdateToObject is not null, the object already exists, this function is used to update the DataObject
     */
    public static function changeObjectWithTypeFromRequestBody($objectClass, $requestBody, DataObjectJsonApiDecoder $deco, $updateableObject = null, $relationshipUpdating = null, $editMode = null) {
        if (array_key_exists(JsonApi::TAG_DATA, $requestBody)) {
            if ($relationshipUpdating) {
                DB::get_conn()->transactionStart();
                $dataObject = $deco->updateObjectRelationFromDataJson($objectClass, $requestBody, $updateableObject, $relationshipUpdating, $editMode ?: DataObjectJsonApiDecoder::$REPLACE);
                if (count($deco->errorLog) > 0) {
                    DB::get_conn()->transactionRollback();
                    return [
                        JsonApi::TAG_ERRORS => $deco->errorLog
                    ];
                }
                try {
                    //Also force-write the 'changed' object to make sure the onafter write is called for  related objects
                    Logger::debugLog("Force write $dataObject->Uuid");
                    $dataObject->write(false, false, true);
                } catch (Exception $e) {
                    return [JsonApi::TAG_ERRORS => [[
                        JsonApi::TAG_ERROR_TITLE => 'Relationship update exception',
                        JsonApi::TAG_ERROR_DETAIL => $e->getMessage(),
                        JsonApi::TAG_ERROR_CODE => 'DOJABD_014'
                    ]]];
                }
                DB::get_conn()->transactionEnd();
                return $dataObject::get_by_id($dataObject->ID); //to make sure nothing is done from cache
            } else if ($jsonData = $requestBody[JsonApi::TAG_DATA]) {
                DB::get_conn()->transactionStart();
                if ($dataObject = $deco->createOrUpdateObjectWithRelationsFromDataJSON($objectClass, $jsonData, $updateableObject)) {
                    if (!$updateableObject) {
                        try {
                            if (!$dataObject->canCreate(Security::getCurrentUser())) {
                                DB::get_conn()->transactionRollback();
                                return InternalJsonApiController::noPermissionJsonApiBodyError("You do not have the correct create permissions for this action");
                            }
                        } catch (Exception $e) {
                            DB::get_conn()->transactionRollback();
                            return [JsonApi::TAG_ERRORS => [[
                                JsonApi::TAG_ERROR_TITLE => 'Creation exception',
                                JsonApi::TAG_ERROR_DETAIL => $e->getMessage(),
                                JsonApi::TAG_ERROR_CODE => 'DOJABD_013'
                            ]]];
                        }
                    }
                    DB::get_conn()->transactionEnd();
                    return $dataObject::get_by_id($dataObject->ID); //to make sure nothing is done from cache
                }
                DB::get_conn()->transactionRollback();
                return [
                    JsonApi::TAG_ERRORS => $deco->errorLog
                ];
            } else {
                return [
                    JsonApi::TAG_ERRORS => [[
                        JsonApi::TAG_ERROR_TITLE => 'Empty data body found',
                        JsonApi::TAG_ERROR_DETAIL => 'Please add information to your data tag',
                        JsonApi::TAG_ERROR_CODE => 'DOJABD_008'
                    ]]
                ];
            }
        } else {
            return [
                JsonApi::TAG_ERRORS => [[
                    JsonApi::TAG_ERROR_TITLE => 'Missing request body data',
                    JsonApi::TAG_ERROR_DETAIL => 'Missing a data JSON body inside the JSON BODY',
                    JsonApi::TAG_ERROR_CODE => 'DOJABD_001'
                ]]
            ];
        }
    }
}