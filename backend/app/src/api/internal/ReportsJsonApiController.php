<?php

namespace SurfSharekit\Api;

use DataObjectJsonApiBodyEncoder;
use Exception;
use InstituteReportJsonApiDescription;
use SilverStripe\Security\Security;
use SurfSharekit\Models\InstituteReport;

class ReportsJsonApiController extends JsonApiController {
    /**
     * @return mixed
     * returns the parts of the url added to base url to denominate this API
     */
    protected function getApiURLSuffix() {
        return '/api/v1/reports';
    }

    /**
     * @return mixed
     * returns a map of DataObjects to their Respective @see DataObjectJsonApiDescription
     */
    protected function getClassToDescriptionMap() {
        return [
            InstituteReport::class => new InstituteReportJsonApiDescription()
        ];
    }

    protected function patchToObject($objectClass, $requestBody, $prexistingObject, $relationshipToPatch = null) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function postToObject($objectClass, $requestBody, $prexistingObject = null, $relationshipToPost = null) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function deleteToObject($objectClass, $requestBody, $prexistingObject, $relationshipToModify = null) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    /**
     * @param $objectToDescribe
     * @return mixed
     * called when the user requested a single dataobject
     */
    protected function getDataObject($objectToDescribe) {
        $dataObjectJsonApiDescriptor = $this->getDataObjectJsonApiEncoder();
        try {
            $response = DataObjectJsonApiBodyEncoder::dataObjectToSingleObjectJsonApiBodyArray($objectToDescribe, $dataObjectJsonApiDescriptor, (BASE_URL . $this->getApiURLSuffix()));
            return $this->createJsonApiBodyResponseFrom($response, 200);
        } catch (Exception $e) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError($e->getMessage()), 403);
        }
    }

    protected function getRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getObjectsBehindRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    /**
     * @param $objectClass
     * @return mixed
     * Called when the use request all objects of a certain type
     */
    function getDataList($objectClass) {
        return InstituteScoper::getAll($objectClass);
    }

    protected function canViewObjectToDescribe($objectToDescribe) {
        return $objectToDescribe->canView(Security::getCurrentUser());
    }
}