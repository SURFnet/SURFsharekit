<?php

namespace SurfSharekit\Api;

use Exception;
use PersonImageJsonApiDescription;
use Ramsey\Uuid\Uuid;
use SilverStripe\api\internal\descriptions\BlueprintExportFileJsonApiDesciption;
use SilverStripe\api\internal\descriptions\EnvironmentExportFileJsonApiDesciption;
use SilverStripe\api\internal\descriptions\ExportItemFileJsonApiDescription;
use SilverStripe\Assets\File;
use SilverStripe\constants\UtmContent;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\EnvironmentExport\DataObjects\BlueprintExportFile;
use SilverStripe\EnvironmentExport\DataObjects\EnvironmentExportFile;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SurfSharekit\Models\ExportItem;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\InstituteImage;
use SurfSharekit\Models\PersonImage;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\ReportFile;
use SurfSharekit\Piwik\CustomEventDimension;
use SurfSharekit\Piwik\Events\DownloadFileEvent;
use SurfSharekit\Piwik\Tracker\PiwikTracker;
use UuidExtension;

class FileJsonApiController extends JsonApiController {

    private static $url_handlers = [
        'GET $Action/$ID/$Relations/$RelationName' => 'getJsonApiRequest',
        'POST $Action/$ID/$Relations/$RelationName' => 'postJsonApiRequest',
        'PATCH $Action/$ID/$Relations/$RelationName' => 'patchJsonApiRequest',
        'DELETE $Action/$ID/$Relations/$RelationName' => 'deleteJsonApiRequest',
        'HEAD $Action/$ID/$Relations/$RelationName' => 'headJsonApiRequest'
    ];

    private static $allowed_actions = [
        'getJsonApiRequest',
        'postJsonApiRequest',
        'patchJsonApiRequest',
        'deleteJsonApiRequest',
        'headJsonApiRequest'
    ];

    private static $errorCode;

    public function __construct() {
        parent::__construct();

        $this->setStatusRedirectsTo(403, Environment::getEnv('FRONTEND_BASE_URL') . '/login');
        $this->setStatusRedirectsTo(401, Environment::getEnv('FRONTEND_BASE_URL') . '/login');
    }

    protected function getApiURLSuffix() {
        return '/api/v1/files';
    }

    protected function getClassToDescriptionMap() {
        return [
            PersonImage::class => new PersonImageJsonApiDescription(),
            InstituteImage::class => new \InstituteImageJsonApiDescription(),
            ReportFile::class => new \ReportFileJsonApiDescription(),
            RepoItemFile::class => new \RepoItemFileJsonApiDescription(),
            ExportItem::class => new ExportItemFileJsonApiDescription(),
            EnvironmentExportFile::class => new EnvironmentExportFileJsonApiDesciption(),
            BlueprintExportFile::class => new BlueprintExportFileJsonApiDesciption()
        ];
    }

    /**
     * @return mixed
     * Handles HEAD request to files. This can be used to determine whether a file is publicly available
     * without actually downloading said file.
     */
    public function headJsonApiRequest() {
        $request = $this->getRequest();
        $requestedObjectClass = $request->param('Action');
        $objectClass = null;
        foreach ($this->ClassToDescriptionMap as $class => $description) {
            if ($description->type_plural == $requestedObjectClass) {
                $objectClass = $class;
                break;
            }
        }

        $objectUUID = $request->param("ID");
        if (!$objectClass || !$objectUUID || !Uuid::isValid($objectUUID)) {
            return $this->getResponse()->setStatusCode(400);
        }

        $preexistingObject = self::getObjectOfTypeById($objectClass, $objectUUID);

        if (!$preexistingObject) {
            return $this->getResponse()->setStatusCode(404);
        }

        if (!$this->canViewObjectToDescribe($preexistingObject)) {
            return $this->getResponse()->setStatusCode(403);
        }

        return $this->getResponse()->setStatusCode(200);
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
        if ($objectToDescribe instanceof RepoItemFile) {
            if (!$this->canViewObjectToDescribe($objectToDescribe)) {
                return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError(), 403);
            }

            $repoItem = $objectToDescribe->RepoItem();
            $utmSource = Controller::curr()->getRequest()->getVar("utm_source");
            // check if repoItem exists (repoItem does not exist for just uploaded files)
            if(!is_null($repoItem) && $repoItem->exists()) {
                $downloadEvent = new DownloadFileEvent(
                    $objectToDescribe->Uuid,
                    $repoItem->Uuid,
                    $repoItem->RepoType,
                    $repoItem->Institute->RootInstitute->Uuid,
                    $utmSource ?? ""
                );

                PiwikTracker::trackDownload(
                    Controller::join_links(Environment::getEnv("SS_BASE_URL"), $this->getRequest()->getURL()),
                    $downloadEvent
                );
            }

            if ($objectToDescribe->shouldUseRedirect()) {
                return $this->redirect($objectToDescribe->getRedirectLink(), 301);
            }
        } else if ($objectToDescribe instanceof ExportItem) {
            $objectToDescribe = Versioned::get_by_stage(File::class, Versioned::DRAFT)->where([
                'ID' => $objectToDescribe->FileID
            ])->first();
        }

        // Open a stream in read-only mode
        $stream = $objectToDescribe->getStream();

        if(is_null($stream)){
            Logger::errorLog('File object stream not available! ID=' . $objectToDescribe->Uuid);
            return $this->createJsonApiBodyResponseFrom(static::objectNotFoundJsonApiBodyError(), 404);
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
        header('Content-Disposition: attachment; filename="' . $objectToDescribe->getFilename()) . '"';

        /*************************************************************************
         * Stream file to the browser
         */

        // Check if the stream has more data to read
        while (!is_null($stream) && !feof($stream)) {
            // Read 1024 bytes from the stream
            echo fread($stream, 1024);
        }
        // Be sure to close the stream resource when you're done with it
        fclose($stream);
    }

    public static function getObjectOfTypeById($objectClass, $objectId) {
        if ($objectClass === ExportItem::class || $objectClass === EnvironmentExportFile::class || $objectClass === BlueprintExportFile::class) {
            return Versioned::get_by_stage(File::class, Versioned::DRAFT)->where([
                'Uuid' => $objectId
            ])->first();
        }

        $object = UuidExtension::getByUuid($objectClass, $objectId);
        if ($object && $object->exists()) {
            return $object;
        }
        return null;
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
        if ($this->getRequest()->isHEAD()) {
            return;
        }

        $this->setStatusRedirectsTo(403, Environment::getEnv('FRONTEND_BASE_URL') . '/forbiddenfile', ['errorCode' => static::getErrorCode()], true);
        $this->setStatusRedirectsTo(401, Environment::getEnv('FRONTEND_BASE_URL') . '/unauthorized', ['errorCode' => static::getErrorCode()], true);

        parent::afterHandleRequest();
    }

    protected function canViewObjectToDescribe($objectToDescribe) {
        return $objectToDescribe->canView(Security::getCurrentUser());
    }

    public static function setErrorCode($code) {
        static::$errorCode = $code;
    }

    public static function getErrorCode() {
        return static::$errorCode;
    }
}