<?php

namespace SurfSharekit\Api;

use DataObjectJsonApiBodyDecoder;
use DataObjectJsonApiDecoder;
use PersonMergeJsonApiDescription;
use BulkActionJsonApiDescription;
use RequestDeleteRepoItemActionDescription;
use SurfSharekit\Models\PersonMerge;

/**
 * Class InternalJsonApiController
 * @package SurfSharekit\Api
 * This class is the entry point for the internal json api to GET,POST and PATCH DataObjects inside the logged in member's scope
 */
class ActionJsonApiController extends JsonApiController {
    /**
     * @return mixed
     * returns the parts of the url added to base url to denominate this API
     */
    protected function getApiURLSuffix() {
        return '/api/v1';
    }

    /**
     * @return mixed
     * returns a map of DataObjects to their Respective @see DataObjectJsonApiDescription
     */
    protected function getClassToDescriptionMap() {
        return [
            PersonMerge::class => new PersonMergeJsonApiDescription(),
            RequestDeleteRepoItemAction::class => new RequestDeleteRepoItemActionDescription()
        ];
    }

    /**
     * @param $objectClass
     * @param $requestBody
     * @param $prexistingObject
     * @param $relationshipToPatch
     * @return mixed
     * Called when all error checks have been done
     */
    protected function patchToObject($objectClass, $requestBody, $prexistingObject, $relationshipToPatch = null) {
        return InternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    /**
     * @param $objectClass
     * @param $requestBody
     * @param $prexistingObject
     * @param $relationshipToPost
     * @return mixed
     * called after all error checks have been done and the request is legitimate
     */
    protected function postToObject($objectClass, $requestBody, $prexistingObject = null, $relationshipToPost = null) {
        $decoder = new DataObjectJsonApiDecoder($this->classToDescriptionMap);
        try {
            $response = DataObjectJsonApiBodyDecoder::changeObjectWithTypeFromRequestBody($objectClass, $requestBody, $decoder, null, null, DataObjectJsonApiDecoder::$ADD);
            return $this->createJsonApiBodyResponseFrom($response, 201);
        } catch (\Exception $e) {
            return $this->createJsonApiBodyResponseFrom(
                [JsonApi::TAG_ERRORS => [
                    [
                        JsonApi::TAG_ERROR_TITLE => 'Bad request',
                        JsonApi::TAG_ERROR_DETAIL => $e->getMessage(),
                        JsonApi::TAG_ERROR_CODE => 'AJAC_001'
                    ]
                ]], 400);
        }
    }

    protected function getRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return InternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);

    }

    protected function getDataObject($objectToDescribe) {
        return InternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    function getObjectsBehindRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return InternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    function getDataList($objectClass) {
        return InternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function deleteToObject($objectClass, $requestBody, $prexistingObject, $relationshipToModify = null) {
        return InternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function canViewObjectToDescribe($objectToDescribe) {
        return false;
    }
}