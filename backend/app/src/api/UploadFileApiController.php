<?php

namespace SurfSharekit\Api;

use DataObjectJsonApiBodyEncoder;
use Exception;
use GroupJsonApiDescription;
use Mimey\MimeTypes;
use PersonImageJsonApiDescription;
use PersonJsonApiDescription;
use Ramsey\Uuid\Uuid;
use RepoItemFileJsonApiDescription;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SurfSharekit\Models\InstituteImage;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonImage;
use SurfSharekit\Models\RepoItemFile;

/**
 * Class UploadFileApiController
 * @package SurfSharekit\Api
 * Controller used as the endpoint for a binary upload using HTTP POST using the $_FILES ability of PHP
 * If successful, will respond with the a JsonApi encoded url of the uploaded file
 */
class UploadFileApiController extends JsonApiController {
    private static $url_handlers = [
        'POST $Action/$ID/$Relations/$RelationName' => 'postJsonApiRequest'
    ];

    private static $allowed_actions = [
        'postJsonApiRequest'
    ];

    protected function getApiURLSuffix() {
        return '/api/v1/upload';
    }

    protected function getClassToDescriptionMap() {
        return [
            RepoItemFile::class => new RepoItemFileJsonApiDescription(),
            PersonImage::class => new PersonImageJsonApiDescription(),
            InstituteImage::class => new \InstituteImageJsonApiDescription(),
            Person::class => new PersonJsonApiDescription(),
            Group::class => new GroupJsonApiDescription()
        ];
    }

    protected function patchToObject($objectClass, $requestBody, $prexistingObject, $relationshipToPatch = null) {
        return InternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    public function postJsonApiRequest() {
        $request = $this->getRequest();
        $this->classToDescriptionMap = $this->getClassToDescriptionMap();
        $this->getResponse()->addHeader("content-type", "application/vnd.api+json");
        $mb = 1000000;// bytes
        foreach ($_FILES as $postFile) {
            $internaFileType = $this->request->param('Action');
            $this->postFile = $postFile;
            $bytesOfFile = $this->postFile['size'];
            if ($bytesOfFile > (500 * $mb)) {
                return JsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction('File too large, please keep it max 500mb supported'), 400);
            }
            $response = $this->postToObject($internaFileType, $request, null, null);
            return JsonApiController::createJsonApiBodyResponseFrom($response, 200);
        }
        return JsonApiController::createJsonApiBodyResponseFrom(
            [
                JsonApi::TAG_ERRORS => [[
                    JsonApi::TAG_ERROR_TITLE => 'No File given or filetype not supported',
                    JsonApi::TAG_ERROR_CODE => 'UFAC_1'
                ]]
            ], 400);
    }

    protected function getDataObject($objectToDescribe) {
        $dataObjectJsonApiDescriptor = $this->getDataObjectJsonApiEncoder();
        try {
            $response = DataObjectJsonApiBodyEncoder::dataObjectToSingleObjectJsonApiBodyArray($objectToDescribe, $dataObjectJsonApiDescriptor, (BASE_URL . $this->getApiURLSuffix()));
            return $this->createJsonApiBodyResponseFrom($response, 200);
        } catch (Exception $e) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError($e->getMessage()), 403);
        }
    }

    protected function getDataList($objectClass) {
        return InternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return InternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getObjectsBehindRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        $dataObjectJsonApiDescriptor = $this->getDataObjectJsonApiEncoder();
        try {
            $response = DataObjectJsonApiBodyEncoder::dataObjectToRelationJsonApiBodyArray($objectToDescribe, $requestedRelationName, $dataObjectJsonApiDescriptor, (BASE_URL . $this->getApiURLSuffix()));
            return $this->createJsonApiBodyResponseFrom($response, 404);
        } catch (Exception $e) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError($e->getMessage()), 403);
        }
    }

    protected function postToObject($objectClass, $requestBody, $prexistingObject = null, $relationshipToPost = null) {
        switch ($objectClass) {
            case 'personImages':
                $fileTypeToCreate = PersonImage::class;
                break;
            case 'instituteImages':
                $fileTypeToCreate = InstituteImage::class;
                break;
            case 'repoItemFiles':
                $fileTypeToCreate = RepoItemFile::class;
                break;
            default:
                return [
                    JsonApi::TAG_ERRORS =>
                        [[
                            JsonApi::TAG_ERROR_TITLE => 'Incorrect type of upload',
                            JsonApi::TAG_ERROR_DETAIL => 'Try upload/personImages for profile pictures, upload/repoItemFiles for files during editing a repoItem, or upload/insituteImages to upload an institute banner',
                            JsonApi::TAG_ERROR_CODE => 'UFAC_3'
                        ]]
                ];
        }

        /***
         * @var $fileTypeToCreate File
         */
        $file = $fileTypeToCreate::create();
        $file->publishFile();
        $fileTypeExtension = null;

        if ($contentType = $this->postFile['type']) {
            // whitelist
            //    - ppsx
            //    - mhtml
            //    - mht
            //    - odt
            //    - sib

            $mimes = new MimeTypes();
            $fileTypeExtension = $mimes->getExtension($contentType);
        }

        if(is_null($fileTypeExtension)){
            $ext = pathinfo($this->postFile['name'], PATHINFO_EXTENSION);
            $allowedExtensions = Config::inst()->get(File::class, 'allowed_extensions');
//            Logger::debugLog($allowedExtensions);
//            Logger::debugLog($ext);
            if(in_array($ext, $allowedExtensions)){
                $fileTypeExtension = $ext;
            }
        }

        if ($fileTypeExtension) {
            $uuid = Uuid::uuid4()->toString();
            $fileName = FileNameFilter::singleton()->filter($this->postFile['name']);
            $file->setFromLocalFile($this->postFile['tmp_name'], "file/" . $uuid . '/' . $fileName);
            $file->setField('Uuid', $uuid);
            $file->write();

            $this->getResponse()->setStatusCode(200);

            try {
                return DataObjectJsonApiBodyEncoder::dataObjectToSingleObjectJsonApiBodyArray($file, $this->getDataObjectJsonApiEncoder(), (BASE_URL . $this->getApiURLSuffix()));
            } catch (Exception $e) {
                return [
                    JsonApi::TAG_ERRORS => [[
                        JsonApi::TAG_ERROR_TITLE => $e->getMessage(),
                        JsonApi::TAG_ERROR_CODE => 'UFAC_4'
                    ]]
                ];
            }
        } else {
            return [
                JsonApi::TAG_ERRORS => [[
                    JsonApi::TAG_ERROR_TITLE => 'Missing Content-Type',
                    JsonApi::TAG_ERROR_DETAIL => 'Missing a valid Content-Type header for uploaded binary',
                    JsonApi::TAG_ERROR_CODE => 'UFAC_2'
                ]]
            ];
        }
    }

    protected function deleteToObject($objectClass, $requestBody, $prexistingObject, $relationshipToModify = null) {
        return InternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function canViewObjectToDescribe($objectToDescribe) {
        return $objectToDescribe->canView(Security::getCurrentUser());
    }
}

