<?php

namespace SurfSharekit\Api;

use DataObjectJsonApiBodyEncoder;
use Exception;
use ExternalRepoItemJsonApiDescription;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SurfSharekit\Models\RepoItem;

/**
 * Class ExternalJsonApiController
 * @package SurfSharekit\Api
 * This class is the entrypoint for the external api to list all repoItems the user has access to
 */
class ExternalJsonApiController extends JsonApiController {
    protected $channel;

    /**
     * @return mixed
     * returns the parts of the url added to base url to denominate this API
     */
    protected function getApiURLSuffix() {
        return '/api/jsonapi/v1';
    }

    /**
     * @return mixed
     * returns a map of DataObjects to their Respective @see DataObjectJsonApiDescription
     */
    protected function getClassToDescriptionMap() {
        return [RepoItem::class => new ExternalRepoItemJsonApiDescription()];
    }

    /**
     * @param $objectClass
     * @param $requestBody
     * @param DataObject $prexistingObject
     * @param $relationshipToPatch
     * @return mixed
     * Called when all error checks have been done
     */
    protected function patchToObject($objectClass, $requestBody, $prexistingObject, $relationshipToPatch = null) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    /**
     * @param int $objectClass
     * @param $requestBody
     * @param $prexistingObject
     * @param $relationshipToPost
     * @return mixed
     * called after all error checks have been done and the request is legitimate
     */
    protected function postToObject($objectClass, $requestBody, $prexistingObject = null, $relationshipToPost = null) {
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

    /**
     * @param $objectClass
     * @return mixed
     * Called when the use request all objects of a certain type
     */
    protected function getDataList($objectClass) {
        $scopedList = InstituteScoper::getAll($objectClass);
        return $scopedList;
    }

    /**
     * @param $objectToDescribe
     * @param $requestedRelationName
     * @return mixed
     * called after all error check have been done and the user request the relationship of a dataobject
     */
    protected function getRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    /**
     * @param DataObject|null $objectToDescribe
     * @param string $requestedRelationName
     * @return mixed
     * called when the client requested a description of the relation of a dataobject
     */
    protected function getObjectsBehindRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function deleteToObject($objectClass, $requestBody, $prexistingObject, $relationshipToModify = null) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function setUserFromRequest(HTTPRequest $request) {
        return parent::setUserFromRequest($request);
    }

    protected function userHasValidLogin(Member $member) {
        if ($member->isDefaultAdmin()) {
            return true;
        } else if (ApiMemberExtension::hasApiUserRole($member)) {
            return true;
        }
        return false;
    }

    /**
     * @param DataObject|null $objectToDescribe
     * @return int
     * called when canView of requested object is called
     */
    protected function canViewObjectToDescribe($objectToDescribe){
        return $objectToDescribe->getField('IsPublic') == true && $objectToDescribe->getField('IsRemoved') == false;
    }
}