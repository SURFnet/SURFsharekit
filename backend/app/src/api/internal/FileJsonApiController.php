<?php

namespace SurfSharekit\Api;

use Exception;
use PersonImageJsonApiDescription;
use SilverStripe\Assets\File;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SurfSharekit\Models\InstituteImage;
use SurfSharekit\Models\PersonImage;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\ReportFile;
use SurfSharekit\Models\StatsDownload;

class FileJsonApiController extends JsonApiController {
    protected function getApiURLSuffix() {
        return '/api/v1/files';
    }

    protected function getClassToDescriptionMap() {
        return [PersonImage::class => new PersonImageJsonApiDescription(),
            InstituteImage::class => new \InstituteImageJsonApiDescription(),
            ReportFile::class => new \ReportFileJsonApiDescription(),
            RepoItemFile::class => new \RepoItemFileJsonApiDescription()];
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
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
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
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    /**
     * @param $objectToDescribe
     * @return mixed
     * called when the user requested a single dataobject
     */
    protected function getDataObject($objectToDescribe) {
        /*************************************************************************
         * Track download if user has access to said RepoItemFile. If not, return 403
         */
        if($objectToDescribe instanceof RepoItemFile) {

            if(!$this->canViewObjectToDescribe($objectToDescribe)){
                return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError(null), 403);
            }

            $repoItem = $objectToDescribe->RepoItem();
            $statusDownload = new StatsDownload();
            $statusDownload->RepoItemFileID = $objectToDescribe->ID;
            $statusDownload->RepoItemID = $repoItem->ID;
            $statusDownload->InstituteID = $repoItem->InstituteID;
            $statusDownload->DownloadDate = date('Y-m-d H:i:s');
            $statusDownload->RepoType = $repoItem->RepoType;
            $statusDownload->IsPublic = $objectToDescribe->IsPublic();
            $statusDownload->write();
        }

        /*************************************************************************
         * Set headers to allow browser to force a download
         */
        /**
         * @var $objectToDescribe File
         */
        header('Last-Modified: ' . $objectToDescribe->LastEdited);
        header('Accept-Ranges: ' . 'none');
        header('Content-Length: ' . $objectToDescribe->getAbsoluteSize());
        header('Content-Type: ' . $objectToDescribe->getMimeType());
        header('Content-Disposition: attachment; filename=' . $objectToDescribe->getFilename());

        /*************************************************************************
         * Stream file to the browser
         */

        // Open a stream in read-only mode
        $stream = $objectToDescribe->getStream();

        // Check if the stream has more data to read
        while (!feof($stream)) {
            // Read 1024 bytes from the stream
            echo fread($stream, 1024);
        }
        // Be sure to close the stream resource when you're done with it
        fclose($stream);
    }

    /**
     * @param DataObject|null $objectToDescribe
     * @param string $requestedRelationName
     * @return mixed
     * called when the client requested a description of the relation of a dataobject
     */
    function getObjectsBehindRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    /**
     * @param $objectClass
     * @return mixed
     * Called when the use request all objects of a certain type
     */
    function getDataList($objectClass) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function deleteToObject($objectClass, $requestBody, $prexistingObject, $relationshipToModify = null) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function setUserFromRequest(HTTPRequest $request) {
        try {
            $member = AccessTokenApiController::consumeAccessToken($request->requestVar('accessToken'));
            Security::setCurrentUser($member);
        } catch (Exception $e) {

        }
    }

    function isRedirectToLoginEnabled() {
        return true;
    }

    protected function afterHandleRequest() {
        parent::afterHandleRequest();
        $response = $this->getResponse();
        if ($this->isRedirectToLoginEnabled()) {
            $code = $response->getStatusCode();
            if ($code === 403 || $code === 401) {
                $this->response->setStatusCode(302);
            }
            $frontendUrl = Environment::getEnv('FRONTEND_BASE_URL');
            $response->addHeader('Location', $frontendUrl . '/publicationfiles/' . $this->request->param("ID"));
        }
    }

    protected function canViewObjectToDescribe($objectToDescribe) {
        return $objectToDescribe->canView(Security::getCurrentUser());
    }
}