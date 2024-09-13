<?php

namespace SurfSharekit\Api;

use DataObjectJsonApiBodyEncoder;
use DataObjectJsonApiEncoder;
use Exception;
use Mimey\MimeTypes;
use Ramsey\Uuid\Uuid;
use RepoItemFileJsonApiDescription;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionRole;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SurfSharekit\constants\RepoItemTypeConstant;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\MimetypeHelper;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\InstituteImage;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonImage;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;
use SurfSharekit\Models\RepoItemUploadConfig;
use SurfSharekit\Models\RepoItemUploadField;
use SurfSharekit\Models\TaskCreator;
use SurfSharekit\Models\TemplateMetaField;
use SurfSharekit\Api\Exceptions\JsonNestedFilter;
//use SurfSharekit\Api\Exceptions\SwaggerDocsHelper;
use Zooma\SilverStripe\Models\SwaggerDocsHelper;

class RepoItemUploadApiController extends LoginProtectedApiController {
    private static $url_handlers = [
        'GET format' => 'getFormat',
        'POST create' => 'postCreate',
        'POST upload' => 'postUpload',
        'DELETE delete' => 'postDelete',
        'GET person/$ID'  => 'getPerson',
        'POST person' => 'postPerson',
        'GET docs' => 'getDocs'
    ];

    private static $allowed_actions = [
        'getFormat',
        'postCreate',
        'postUpload',
        'upload',
        'getPerson',
        'postPerson',
        'getDocs',
        'postDelete'
    ];

    private $institute = null;
    private $persons = [];

    function getFormat() {
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        $repoItemConfig = RepoItemUploadConfig::get()->first();
        $output = [];
        /** @var RepoItemUploadField $uploadField */
        foreach ($repoItemConfig->RepoItemUploadFields() as $uploadField) {
            $metafield = $uploadField->MetaField();
            if($metafield) {
                $type = $metafield->MetaFieldType();
                $options = static::getOptionsForMetafield($metafield, strtolower($type->Key));
                $output[$uploadField->Title] = [
                    'type' => strtolower($type->Key),
                    'labelNL' => $metafield->Label_NL,
                    'labelEN' => $metafield->Label_EN,
                    'isRequired' => $uploadField->IsRequired,
                    'regex' => $type->ValidationRegex,
                    'options' => $options
                ];
            }
            $attributeKey = $uploadField->AttributeKey;
            switch ($attributeKey) {
                case "RepoType": {
                    $output[$uploadField->Title] = [
                        'type' => 'string',
                        'labelNL' => 'RepoType',
                        'labelEN' => 'RepoType',
                        'isRequired' => 1,
                        'regex' => null,
                        'options' => ["PublicationRecord", "LearningObject", "ResearchObject", "Dataset", "Project"]
                    ];
                    break;
                }
                case "InstituteID": {
                    $output[$uploadField->Title] = [
                        'type' => 'uuid',
                        'labelNL' => 'InstituteID',
                        'labelEN' => 'InstituteID',
                        'isRequired' => 1,
                        'regex' => null,
                        'options' => self::getOptionsForMetafield(null, 'multiselectpublisher')
                    ];
                    break;
                }
                case "OwnerID": {
                    $output[$uploadField->Title] = [
                        'type' => 'uuid',
                        'labelNL' => 'OwnerID',
                        'labelEN' => 'OwnerID',
                        'isRequired' => 1,
                        'regex' => null,
                        'options' => []
                    ];
                    break;
                }
            }
        }
        return json_encode($output);
    }

    public static function getOptionsForMetafield($metafield, $type) {
        $options = [];
        if ($type == 'dropdown') {
            foreach ($metafield->MetaFieldOptions() as $option) {
                $options[] = [
                    'value' => $option->Value,
                    'labelNL' => $option->Label_NL,
                    'labelEN' => $option->Label_EN
                ];
            }
        } else if ($type == 'multiselectpublisher' || $type == 'multiselectinstitute') {
            foreach (InstituteScoper::getAll(Institute::class)->filter('InstituteID', 0) as $rootInstitute) {
                $options[] = [
                    'value' => $rootInstitute->Uuid,
                    'title' => $rootInstitute->Title,
                ];
            }
        }
        return $options;
    }

    function postCreate($request) {
        $this->getResponse()->setStatusCode(400);
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        $repoItem = new RepoItem();
        try {
            if (!$this->userHasValidLogin(Security::getCurrentUser())) {
                return json_encode(["error" => "user is not an API user"]);
            }
            if (!($requestBody = json_decode($request->getBody(), true))) {
                return json_encode(["error" => "missing body"]);
            }
            $repoItemConfig = RepoItemUploadConfig::get()->first();

            //write attributes, needed for creation of repoitem
            foreach ($repoItemConfig->RepoItemUploadFields()->filter('AttributeKey:not', null) as $uploadField) {
                $this->setRepoItemAttributes($repoItem, $uploadField, $requestBody);
            }
            //write repoitem when done with writing attributes
            if (!$repoItem->InstituteID) {//default institute if not set to user's member group institute
                $userMemberGroup = Security::getCurrentUser()->Groups()->filter(['Roles.Title' => RoleConstant::MEMBER])->first();
                $repoItem->InstituteID = $userMemberGroup->InstituteID;
            }

            $repoItem->NeedsToBeFinished = true;
            $repoItem->UploadedFromApi = true;
            $repoItem->write();

            //write actual meta field answers
            foreach ($repoItemConfig->RepoItemUploadFields() as $uploadField) {
                $this->createRepoItemMetaField($repoItem, $uploadField, $requestBody);
            }

            $repoItem->shouldCreateFillTask = true;
            $repoItem->write(false, false, true);

            if (null !== $institute = $this->getInstitute()) {
                /** @var Institute $institute */
                $rootInstitute = $institute->getRootInstitute();
                foreach ($this->getPersons() as $person) {
                    $permissionRole = PermissionRole::get()->filter('Title', RoleConstant::MEMBER)->first();
                    if ($group = $rootInstitute->Groups()->filter('Roles.ID', $permissionRole->ID)->first()) {
                        $person->addToGroupByCode($group->Code);
                    }
                }
            }

            $this->getResponse()->setStatusCode(201);
            return json_encode(['id' => $repoItem->Uuid]);
        } catch (Exception $e) {
            if ($repoItem->exists()) {
                $repoItem->IndirectDelete = true;
                $repoItem->delete();
            }

            return $this->getResponse()->setBody(json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    function postDelete($request){
        $this->getResponse()->setStatusCode(400);
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        try {

            //First we check if the user is valid
            if (!$this->userHasValidLogin(Security::getCurrentUser())) {
                return json_encode(["error" => "user is not an API user"]);
            }

            if (!($requestBody = json_decode($request->getBody(), true))) {
                return json_encode(["error" => "missing body"]);
            }

            // Check if the repoItemID
            $repoItemID = $requestBody['repoItemID'];
            if (!$repoItemID){
                return json_encode(["error" => "repoItemID missing"]);
            }

            // Check if this repoItem exists
            $repoItem = RepoItem::get()->filter('UUID', $repoItemID)->first();
            if (!($repoItem || $repoItem->exists())) {
                return json_encode(["error" => "repoItem doesn't exist"]);
            }

            // Check on own repo item that you're deleting
            $apiUserID = Security::getCurrentUser()->ID;
            if ($apiUserID !== $repoItem->OwnerID) {
                return json_encode(["error" => "Can't delete other people's repo items"]);
            }

            // Check whether to see if it's already in the bin
            if ($repoItem->IsRemoved){
                return json_encode(["error" => "repoItem already in the bin"]);
            }

            //Process to delete repoItem
            if ($repoItem->exists() && $repoItem instanceof RepoItem) {
                $repoItem->Status = 'Draft';
                $repoItem->UploadedFromApi = true;
                $repoItem->IsRemoved = true;
                $repoItem->write();

                if ($repoItem->IsRemoved){
                    TaskCreator::getInstance()->createRecoverTasks($repoItem);
                }
            }

            $this->getResponse()->setStatusCode(200);

            //Return json encoded response from post
            return json_encode([
                'repoItemID' => $repoItem->Uuid,
                'repoItem' => $repoItem->Title,
                'repoItemInstitute' => $repoItem->Institute()->Title
            ]);
        }  catch (Exception $e){
            Logger::debugLog( $e->getMessage());
            return json_encode(["error" => 'Error occurred ' . $e->getMessage()]);
        }

    }

    function upload($request) {
        return $this->postUpload($request);
    }
    /** @var HTTPRequest $request */
    function postUpload($request) {
        ini_set('upload_max_filesize', '500M');
        ini_set('post_max_size', '500M');
        ini_set('max_input_time', 300);
        ini_set('max_execution_time', 300);
        $this->getResponse()->setStatusCode(400);
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        try {
            if (!$this->userHasValidLogin(Security::getCurrentUser())) {
                Logger::debugLog("user is not an API user");
                return json_encode(["error" => "user is not an API user"]);
            }
            $mb = 1048576;// bytes
            foreach ($_FILES as $postFile) {
                $bytesOfFile = $postFile['size'];
                if ($bytesOfFile > (5000 * $mb)) {
                    Logger::debugLog( 'File too large, please keep it max 5Gb supported');
                    return json_encode(["error" => 'File too large, please keep it max 5Gb supported']);
                }
                $result = $this->postToObject('repoItemFiles', $postFile);
                Logger::debugLog($result);
                return $result;
            }
            Logger::debugLog('No files found ' . print_r($_FILES, true));
            return json_encode(["error" => "No files found"]);
        }
        catch (Exception $e){
            Logger::debugLog( $e->getMessage());
            return json_encode(["error" => 'Error occurred ' . $e->getMessage()]);
        }

    }

    private function postToObject($objectClass, $postFile, $prexistingObject = null, $relationshipToPost = null) {
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
        Logger::debugLog('Start creating file');
        $file = $fileTypeToCreate::create();
        Logger::debugLog('Start publishing file');
        $file->publishFile();
        $fileTypeExtension = null;

        if ($contentType = $postFile['type']) {
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
            $ext = pathinfo($postFile['name'], PATHINFO_EXTENSION);
            $allowedExtensions = MimetypeHelper::getWhitelistedExtensions();

//            Logger::debugLog($allowedExtensions);
//            Logger::debugLog($ext);
            if(in_array($ext, $allowedExtensions)){
                $fileTypeExtension = $ext;
            }
        }

        if ($fileTypeExtension) {
            Logger::debugLog('Start setting from localfile');
            $uuid = Uuid::uuid4()->toString();
            $fileName = FileNameFilter::singleton()->filter($postFile['name']);
            $file->setFromLocalFile($postFile['tmp_name'], "file/" . $uuid . '/' . $fileName);
            $file->setField('Uuid', $uuid);
            Logger::debugLog('Start writing file');
            $file->write();
            Logger::debugLog('End writing file');
            $this->getResponse()->setStatusCode(200);

            try {
                $response = DataObjectJsonApiBodyEncoder::dataObjectToSingleObjectJsonApiBodyArray($file, $this->getDataObjectJsonApiEncoder(), (BASE_URL . $this->getApiURLSuffix()));
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
        return $this->createJsonApiBodyResponseFrom($response, 200);
    }

    protected function getClassToDescriptionMap() {
        return [
            RepoItemFile::class => new RepoItemFileJsonApiDescription()
            ];
    }


    protected function getApiURLSuffix() {
        return '/api/repoitemupload/v1/upload';
    }

    protected function getDataObjectJsonApiEncoder() {
        $dataObjectJsonApiDescriptor = new DataObjectJsonApiEncoder($this->getClassToDescriptionMap(), []);
        if ($this->sparseFields) {
            $dataObjectJsonApiDescriptor->setSparseFields($this->sparseFields);
        }

        if ($this->pageSize) {
            $dataObjectJsonApiDescriptor->setPagination($this->pageSize, $this->pageNumber);
        }
        return $dataObjectJsonApiDescriptor;
    }

    public function createJsonApiBodyResponseFrom($response, int $statusCode) {
        $this->getResponse()->setStatusCode($statusCode);

        if (is_array($response)) {
            if (isset($response[JsonApi::TAG_ERRORS]) && $statusCode < 400) {
                $newHttpCode = 400;
                foreach ($response[JsonApi::TAG_ERRORS] as $errorBody) {
                    if ($errorBody[JsonApi::TAG_ERROR_CODE] == 'DOJAD_002' || $errorBody[JsonApi::TAG_ERROR_CODE] == 'DOJAD_005') {
                        $newHttpCode = 409;
                        break;
                    } else if ($errorBody[JsonApi::TAG_ERROR_CODE] == 'DOJAD_009') {
                        $newHttpCode = 404;
                        break;
                    } else if ($errorBody[JsonApi::TAG_ERROR_CODE] == 'DOJAD_020') {
                        $newHttpCode = 403;
                        break;
                    }
                }
                $this->getResponse()->setStatusCode($newHttpCode);
            }
        } else if (is_object($response)) {//$decoderInformation is a DataObject which we inserted into the database
            $encoder = $this->getDataObjectJsonApiEncoder();
            $dataObject = $response;
            $response = ['data' => $encoder->describeDataObjectAsData($dataObject, BASE_URL . $this->getApiURLSuffix())];
            $this->getResponse()->addHeader('Location', $encoder->getContextURLForDataObject($dataObject, BASE_URL . $this->getApiURLSuffix()));
        } else {
            $this->getResponse()->setStatusCode(400);
        }

        $this->getResponse()->addHeader("content-type", "application/vnd.api+json");
        if (isset($response)) {
            return str_replace('"attributes": []', '"attributes": {}', json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    protected function userHasValidLogin(Member $member) {
        if ($member->isDefaultAdmin()) {
            return true;
        } else if (ApiMemberExtension::hasApiUserRole($member)) {
            return true;
        }
        return false;
    }

    private function setRepoItemAttributes(RepoItem $repoItem, $uploadField, $requestBody) {
        if (!isset($requestBody[$uploadField->Title])) {
            if ($uploadField->IsRequired) {
                throw new Exception("Missing required field '$uploadField->Title'");
            } else {
                return;
            }
        }
        $uploadFieldValue = $requestBody[$uploadField->Title];

        $attributeKey = $uploadField->AttributeKey;
        $allowedAttributeKeys = RepoItemUploadField::get()->dbObject("AttributeKey")->enumValues();

        if (!in_array($attributeKey, $allowedAttributeKeys)) {
            throw new Exception("Attribute key '" . $attributeKey . "' not allowed");
        }

        if ($attributeKey == 'RepoType') {
            $typeKey = 'string';
        } else if ($attributeKey == 'InstituteID'){
            $typeKey = 'uuid';
        } else if ($attributeKey == 'OwnerID') {
            $typeKey = 'uuid';
        }

        if ($typeKey === 'uuid') {
            Uuid::fromString($uploadFieldValue);
        }

        if ($typeKey === 'string' && !is_string($uploadFieldValue)) {
            throw new Exception("field '$uploadField->Title' has to have value of type string");
        }

        $fieldValue = null;
        if ($attributeKey == 'InstituteID' && ($institute = Institute::get()->filter('Uuid', $uploadFieldValue)->first()) && $institute->exists()) {
            $fieldValue = $institute->ID;
        } else if ($attributeKey == 'OwnerID' && ($owner = Person::get()->filter('Uuid', $uploadFieldValue)->first()) && $owner->exists()) {
            $fieldValue = $owner->ID;
        } else {
            $fieldValue = $uploadFieldValue;
        }
        $repoItem->$attributeKey = $fieldValue;
    }

    private function createRepoItemMetaField(RepoItem $repoItem, $uploadField, $requestBody) {
        if (!isset($requestBody[$uploadField->Title])) {
            if ($uploadField->IsRequired) {
                throw new Exception("Missing required field '$uploadField->Title'");
            } else {
                return;
            }
        }

        $answer = $requestBody[$uploadField->Title];
        $metafield = $uploadField->MetaField();
        $type = $metafield->MetaFieldType();

        $typeKey = strtolower($type->Key);
        if (in_array($typeKey, ['text', 'textarea', 'institute', 'dropdown']) && !is_string($answer)) {
            throw new Exception("field '$uploadField->Title' with type $typeKey has to have value of type string");
        } else if (in_array($typeKey, ['multiselectpublisher', 'multiselectinstitute', 'attachment', 'personinvolved']) && !is_array($answer)) {
            throw new Exception("field '$uploadField->Title' with type $typeKey has to have value of type array");
        }

        $repoItemMetaField = new RepoItemMetaField();
        $repoItemMetaField->MetaFieldID = $metafield->ID;
        $repoItemMetaField->RepoItemID = $repoItem->ID;
        $repoItemMetaField->write();

        //force into array for easy creation of repoitemmetafieldvalues
        if (!is_array($answer)) {
            $answer = [$answer];
        }

        $sortOrder = 0;
        foreach ($answer as $answerValue) {
            $repoItemMetaFieldValue = new RepoItemMetaFieldValue();
            $repoItemMetaFieldValue->RepoItemMetaFieldID = $repoItemMetaField->ID;
            $repoItemMetaFieldValue->SortOrder = $sortOrder;
            $repoItemMetaFieldValue->IsRemoved = false;
            if ($typeKey == 'dropdown') {
                if (($chosenOption = MetaFieldOption::get()->filter('MetaFieldID', $metafield->ID)->filter('Value', $answer)->first()) && $chosenOption->exists()) {
                    $repoItemMetaFieldValue->MetaFieldOptionID = $chosenOption->ID;
                } else {
                    throw new Exception("$answerValue is not a valid option for field $uploadField->Title");
                }
            } else if ($typeKey === "dropdowntag") { // This creates new keywords

                $caseSensitive = '';
                if (in_array(strtolower($metafield->MetaFieldType()->getField('Title')), ['tag', 'dropdowntag'])) {
                    $caseSensitive = ':ExactMatch:case';
                }

                $metafieldOption = MetaFieldOption::get()->filter(['MetaFieldID' => $metafield->ID, "Label_EN$caseSensitive" => $answerValue, "Label_NL$caseSensitive" => $answerValue])->first();
                if (!$metafieldOption || !$metafieldOption->Exists()) {
                    $newMetafieldOption = MetaFieldOption::create();
                    $newMetafieldOption->MetaFieldID = $metafield->ID;
                    $newMetafieldOption->Title = $answerValue;
                    $newMetafieldOption->Label_EN = $answerValue;
                    $newMetafieldOption->Label_NL = $answerValue;
                    $newMetafieldOption->Value = $answerValue;
                    try {
                        $newMetafieldOption->write();
                    } catch (ValidationException $e) {
                        Logger::debugLog("Repoitem ValidationException: " . $e->getMessage());
                    }
                    $repoItemMetaFieldValue->setField('MetaFieldOptionID', $newMetafieldOption->getField('ID'));
                } else {
                    $repoItemMetaFieldValue->setField('MetaFieldOptionID', $metafieldOption->getField('ID'));
                }
            } else if (in_array($typeKey, ['lectorate', 'institute', 'discipline', 'multiselectpublisher', "multiselectinstitute"])) {
                if (($chosenInstitute = Institute::get()->filter('Uuid', $answerValue)->first()) && $chosenInstitute->exists()) {
                    $this->setInstitute($chosenInstitute);
                    $repoItemMetaFieldValue->InstituteID = $chosenInstitute->ID;
                } else {
                    throw new Exception("$answerValue is not a valid institute for field $uploadField->Title");
                }
            } else if ($uploadField->RepoItemType === RepoItemTypeConstant::REPOITEM_REPOITEM_FILE) {
                if (!is_array($answerValue) || !isset($answerValue['fileId']) || !isset($answerValue['access'])) {
                    throw new Exception('use format [{"fileId":"","access":""}, {"fileId":"","access":""}] when linking files. acces options are: []');
                }
                if (($chosenFile = RepoItemFile::get()->filter('Uuid', $answerValue['fileId'])->first()) && $chosenFile->exists()) {
                    $subRepoItem = $this->createRepoItemRepoItemFile($repoItem, $chosenFile, $answerValue);
                    $repoItemMetaFieldValue->RepoItemID = $subRepoItem->ID;
                } else {
                    throw new Exception("RepoItem with id " . $answerValue["fileId"] . " does not exist");
                }
            } else if ($uploadField->RepoItemType === RepoItemTypeConstant::REPOITEM_PERSON) {
                if (($person = Person::get()->find('Uuid', $answerValue)) && $person->exists()) {
                    $this->addPerson($person);
                    $subRepoItem = $this->createRepoItemRepoItemAuthor($repoItem, $person, $answerValue);
                    $repoItemMetaFieldValue->RepoItemID = $subRepoItem->ID;
                } else {
                    throw new Exception("$answerValue is not a valid file for field $uploadField->Title");
                }
            } else if ($uploadField->RepoItemType === RepoItemTypeConstant::REPOITEM_LINK) {
                $test = $answerValue;
                $subRepoItem = $this->createRepoItemLink($repoItem, $uploadField, $answerValue);
                $repoItemMetaFieldValue->RepoItemID = $subRepoItem->ID;

            } /* else if (($chosenPeron = Person::get()->filter('Uuid', $answerValue)->first()) && $chosenPeron->exists()) {
                $repoItemMetaFieldValue->PersonID = $chosenPeron->ID;
            } else if (($chosenRepoItem = RepoItem::get()->filter('Uuid', $answerValue)->first()) && $chosenRepoItem->exists()) {
                $repoItemMetaFieldValue->RepoItemID = $chosenRepoItem->ID;
            }*/ else {
                $repoItemMetaFieldValue->Value = $answerValue;
            }

            $repoItemMetaFieldValue->write();
            $sortOrder += 1;
        }
    }

    private function createRepoItemLink(RepoItem $parentRepoItem, RepoItemUploadField $parentRepoItemUploadField, $answerValue) {
        $subRepoItem = new RepoItem();
        $subRepoItem->RepoType = 'RepoItemLink';
        $subRepoItem->OwnerID = $parentRepoItem->OwnerID;
        $subRepoItem->InstituteID = $parentRepoItem->InstituteID;
        $subRepoItem->write();

        if ($parentRepoItemUploadField->RepoItemUploadFields()->count()) {
            foreach ($parentRepoItemUploadField->RepoItemUploadFields() as $repoItemUploadField) {
                $this->createRepoItemMetaField($subRepoItem, $repoItemUploadField, $answerValue);
            }
        }

        return $subRepoItem;
    }

    private function createRepoItemRepoItemFile(RepoItem $parentRepoItem, RepoItemFile $chosenFile, $answerValue) {
        //Create subrepoitem
        $subRepoItem = new RepoItem();
        $subRepoItem->RepoType = 'RepoItemRepoItemFile';
        $subRepoItem->OwnerID = $parentRepoItem->OwnerID;
        $subRepoItem->InstituteID = $parentRepoItem->InstituteID;
        $subRepoItem->write();

        //Create repoitemmetafield linking to file upload metafield
        $repoItemMetaField = new RepoItemMetaField();
        $uploadField = MetaField::get()->filter('MetaFieldType.Key', 'File')->first();
        if (!$uploadField || !$uploadField->exists()) {
            throw new Exception("Couldn't find upload field of sub repoitem");
        }
        $repoItemMetaField->MetaFieldID = $uploadField->ID;
        $repoItemMetaField->RepoItemID = $subRepoItem->ID;
        $repoItemMetaField->write();

        //Connect uploaded file to repoitemmetafield
        $repoItemMetaFieldValue = new RepoItemMetaFieldValue();
        $repoItemMetaFieldValue->RepoItemMetaFieldID = $repoItemMetaField->ID;
        $repoItemMetaFieldValue->RepoItemFileID = $chosenFile->ID;
        $repoItemMetaFieldValue->write();

        //Create acces control reply
        $accesControlField = $this->getAccesControlMetaField();
        $repoItemMetaField = new RepoItemMetaField();
        $repoItemMetaField->MetaFieldID = $accesControlField->ID;
        $repoItemMetaField->RepoItemID = $subRepoItem->ID;
        $repoItemMetaField->write();

        //Connect acces control to repoitemmetafield
        $accesControl = $answerValue['access'];
        $repoItemMetaFieldValue = new RepoItemMetaFieldValue();
        $repoItemMetaFieldValue->RepoItemMetaFieldID = $repoItemMetaField->ID;
        $repoItemMetaFieldValue->MetaFieldOptionID = $this->getAccesControlMetaFieldOption($accesControlField, $accesControl)->ID;
        $repoItemMetaFieldValue->write();

        //Create title
        $titleField = $this->getTitleMetaField();
        $repoItemMetaField = new RepoItemMetaField();
        $repoItemMetaField->MetaFieldID = $titleField->ID;
        $repoItemMetaField->RepoItemID = $subRepoItem->ID;
        $repoItemMetaField->write();

        //Connect acces control to repoitemmetafield
        $title = $answerValue['title'];
        $repoItemMetaFieldValue = new RepoItemMetaFieldValue();
        $repoItemMetaFieldValue->RepoItemMetaFieldID = $repoItemMetaField->ID;
        $repoItemMetaFieldValue->Value = $title;
        $repoItemMetaFieldValue->write();

        return $subRepoItem;
    }

    private function createRepoItemRepoItemAuthor(RepoItem $parentRepoItem, Person $person, $answerValue) {
        //Create subrepoitem
        $subRepoItem = new RepoItem();
        $subRepoItem->RepoType = 'RepoItemPerson';
        $subRepoItem->OwnerID = $parentRepoItem->OwnerID;
        $subRepoItem->InstituteID = $parentRepoItem->InstituteID;
        $subRepoItem->write();

        //Create repoitemmetafield linking to person metafield
        $repoItemMetaField = new RepoItemMetaField();
        $uploadField = MetaField::get()->filter('MetaFieldType.Key', 'Person')->first();
        if (!$uploadField || !$uploadField->exists()) {
            throw new Exception("Couldn't find upload field of sub repoitem");
        }
        $repoItemMetaField->MetaFieldID = $uploadField->ID;
        $repoItemMetaField->RepoItemID = $subRepoItem->ID;
        $repoItemMetaField->write();

        //Connect uploaded file to repoitemmetafield
        $repoItemMetaFieldValue = new RepoItemMetaFieldValue();
        $repoItemMetaFieldValue->RepoItemMetaFieldID = $repoItemMetaField->ID;
        $repoItemMetaFieldValue->PersonID = $person->ID;
        $repoItemMetaFieldValue->write();

        return $subRepoItem;
    }

    public function getAccesControlMetaField() {
        $field = TemplateMetaField::get()->filter(['Template.RepoType' => 'RepoItemRepoItemFile', 'Template.InstituteID' => 0])
            ->innerJoin('SurfSharekit_MetaField', 'SurfSharekit_MetaField.ID = SurfSharekit_TemplateMetaField.MetaFieldID')
            ->innerJoin('SurfSharekit_MetaFieldOption', 'SurfSharekit_MetaField.ID = SurfSharekit_MetaFieldOption.MetaFieldID')
            ->where(['SurfSharekit_MetaFieldOption.Value' => 'openaccess'])->first();
        if (!$field || !$field->exists()) {
            throw new Exception("Could not find access control field");
        }
        return $field->MetaField();
    }


    public function getTitleMetaField() {
        $field = TemplateMetaField::get()->filter(['Template.RepoType' => 'RepoItemRepoItemFile', 'Template.InstituteID' => 0])
            ->innerJoin('SurfSharekit_MetaField', 'SurfSharekit_MetaField.ID = SurfSharekit_TemplateMetaField.MetaFieldID')
            ->where(['SurfSharekit_MetaField.AttributeKey' => 'Title'])->first();
        if (!$field || !$field->exists()) {
            throw new Exception("Could not find title field");
        }
        return $field->MetaField();
    }


    public function getAccesControlMetaFieldOption(MetaField $accesControlMetaField, $accesControlValue) {
        $option = $accesControlMetaField->MetaFieldOptions()->filter(['Value' => $accesControlValue])->first();
        if (!$option || !$option->exists()) {
            $options = json_encode($accesControlMetaField->MetaFieldOptions()->column('Value'));
            throw new Exception("Could not find access control option '$accesControlValue'. Options available: $options");
        }
        return $option;
    }



    /////////////// PERSON
    private $requiredPersonFields = [
        "surname",
        "institute"
    ];

    private $personIdentifiers = [
        "email" => "Email",
        "dai" => "PersistentIdentifier",
        "isni" => "ISNI",
        "orcid" => "ORCID",
        "hogeschoolId" => "HogeschoolID",
//        "sramId" => "SRAMID", TODO: add after sram implementation
    ];

    private $allowedFilters = [
        "institute" => "",
        "surname" => "Surname",
        "email" => "Email",
        "dai" => "PersistentIdentifier",
        "isni" => "ISNI",
        "orcid" => "ORCID",
        "hogeschoolId" => "HogeschoolID"
//        "sramId" TODO: add after sram implementation
    ];

    public function getPerson(HTTPRequest $request) {
        $apiDescription = new RepoItemUploadApiDescription();
        $this->getResponse()->addHeader('Content-Type', 'application/json');

        if (null !== $id = $request->param('ID')) {
            if (null !== $person = Person::get()->find('Uuid', $id)) {
                $this->getResponse()->setStatusCode(200);
                return json_encode($apiDescription->describeAttributesOfDataObject($person));
            }

            $this->getResponse()->setStatusCode(404);
            return json_encode(["error" => "Person not found"]);
        }

        $filter = $request->requestVar('filter');

        if (!empty($filter)) {
            if (is_array($filter)) {
                $persons = Person::get()
                    ->leftJoin('SurfSharekit_Person_RootInstitutes', 'SurfSharekit_Person_RootInstitutes.SurfSharekit_PersonID = Member.ID')
                    ->leftJoin('SurfSharekit_Institute', 'SurfSharekit_Institute.ID = SurfSharekit_Person_RootInstitutes.SurfSharekit_InstituteID');

                foreach ($filter as $field => $value) {
                    try {
                        $persons = $apiDescription->applyFilter($persons, $field, $value);
                    } catch (Exception $e) {
                        return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError($e->getMessage()), 400);
                    }
                }

                $responseData = [];
                foreach ($persons as $person) {
                    $responseData[] = $apiDescription->describeAttributesOfDataObject($person);
                }

                $this->getResponse()->setStatusCode(200);
                return json_encode($responseData);
            } else {
                try {
                    $jsonNestedFilter = new JsonNestedFilter(
                        $this->allowedFilters,
                        json_decode($filter, true)
                    );

                    $query = $jsonNestedFilter->getQuery();
                    $params = $jsonNestedFilter->getParams();

                    $persons = Person::get()->where([$query => $params]);
                } catch (Exception $exception) {
                    $this->getResponse()->setStatusCode(400);
                    return json_encode(["error" => $exception->getMessage()]);
                }
            }

            if (null !== $persons) {
                $responseArray = [];

                foreach ($persons as $person) {
                    $responseArray[] = $apiDescription->describeAttributesOfDataObject($person);
                }

                $this->getResponse()->setStatusCode(200);
                return json_encode($responseArray);
            }

            $this->getResponse()->setStatusCode(404);
            return json_encode(["error" => "Person not found"]);
        }

        $this->getResponse()->setStatusCode(400);
        return json_encode(["error" => "Bad request"]);
    }

    public function postPerson(HTTPRequest $request) {
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        if (!$this->userHasValidLogin(Security::getCurrentUser())) {
            $this->getResponse()->setStatusCode(400);
            return json_encode(["error" => "user is not an API user"]);
        }

        if (!$body = json_decode($request->getBody(), true)) {
            $this->getResponse()->setStatusCode(400);
            return json_encode(["error" => "missing body"]);
        }

        foreach ($this->requiredPersonFields as $field) {
            if (empty($body[$field])) {
                $this->getResponse()->setStatusCode(400);
                return json_encode(["error" => $field . " is required"]);
            }
        }

        foreach ($this->personIdentifiers as $identifier => $field) {
            if (isset($body[$identifier]) && !empty($body[$identifier])) {
                if ($this->checkPersonIdentifier($field, $body[$identifier])) {
                    $this->getResponse()->setStatusCode(400);
                    return json_encode(["error" => "Person already exists with identifier: " . $identifier]);
                }
            }
        }

        if (null === $institute = Institute::get()->filter('InstituteID', 0)->find('Uuid', $body['institute'])) {
            $this->getResponse()->setStatusCode(400);
            return json_encode(["error" => "Institute not found"]);
        }

        if (!empty($body['position']) && !in_array($body['position'], Person::getPositionOptions())) {
            $this->getResponse()->setStatusCode(400);
            return json_encode(["error" => "Invalid function, please choose from this list: " . implode(', ', Person::getPositionOptions()) ]);
        }

        $person = Person::create([
            "FirstName" => $body["firstName"] ?? "",
            "SurnamePrefix" => $body["surnamePrefix"] ?? "",
            "Surname" => $body["surname"] ?? "",
            "Position" => $body["position"] ?? "",
            "Email" => $body["email"] ?? "",
            "PersistentIdentifier" => $body["dai"] ?? "",
            "ISNI" => $body["isni"] ?? "",
            "ORCID" => $body["orcid"] ?? "",
            "HogeschoolID" => $body["hogeschoolId"] ?? "",
        ]);
        $person->setSkipEmail(empty($body['email']));
        $person->setBaseInstitute($institute->Uuid);

        $person->write();

        return json_encode(["id" => $person->Uuid]);
    }

    private function checkPersonIdentifier($identifier, $value): ?Person {
        return Person::get()->find($identifier, $value);
    }

    private function personToJson(Person $person) {
        return [
            "firstName" => $person->FirstName,
            "surnamePrefix" =>  $person->SurnamePrefix,
            "surname" =>  $person->Surname,
            "organisation" =>  "",
            "position" =>  $person->Position,
            "email" =>  $person->Email,
            "dai" =>  $person->PersistentIdentifier,
            "isni" =>  $person->ISNI,
            "orcid" =>  $person->ORCID,
            "hogeschoolId" =>  $person->HogeschoolID,
        ];
    }

    private function buildFilters(array $filter, &$params = []) {
        $filterString = '(';

        if (!isset($filter['fields']) || empty($filter['fields'])) {
            throw new Exception("fields missing in filter");
        }

        foreach ($filter['fields'] as $field => $value) {
            if (!in_array($field, array_keys($this->allowedFilters))) {
                throw new Exception("Invalid filter: " . $field);
            }

            $operator = $filter['operator'] ?? "AND";
            if (!in_array($operator, ['AND', 'OR'])) {
                throw new Exception("Invalid filter operator: " . $operator);
            }

            $translatedField = $this->allowedFilters[$field];
            $filterString .= "$translatedField = ?";
            $params[] = $value;

            if (count($filter['fields']) > 1 && next($filter)) {
                $filterString .= " $operator ";
            }
        }

        if (isset($filter['OR']) && !empty($filter['OR'])) {
            foreach ($filter['OR'] as $or) {
                $filterString .= " OR " . $this->buildFilters($or, $params)['query'];
            }
        }

        if (isset($filter['AND']) && !empty($filter['AND'])) {
            foreach ($filter['AND'] as $and) {
                $filterString .= " AND " . $this->buildFilters($and, $params)['query'];
            }

        }


        $filterString .= ")";
        return [
            "query" => $filterString,
            "params" => $params
        ];
    }

    public static function invalidFiltersJsonApiBodyError($message = null) {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Incorrect filter query params',
                    JsonApi::TAG_ERROR_DETAIL => $message ? $message : 'Please specify filters as e.g.: ....objectType?filter[attribute]=value',
                    JsonApi::TAG_ERROR_CODE => 'RIUAC_001'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function getPersons(): array {
        return $this->persons;
    }

    /**
     * @param Person $person
     */
    public function addPerson(Person $person): void {
        $this->persons[] = $person;
    }

    /**
     * @return null
     */
    public function getInstitute() {
        return $this->institute;
    }

    /**
     * @param Institute $institute
     */
    public function setInstitute(Institute $institute): void {
        $this->institute = $institute;
    }

    public function getDocs() {
        return SwaggerDocsHelper::renderDocs(
            '/api/repoitemupload/v1',
            "/api/repoitemupload/v1/docs",
            '../docs/openapi_repoitemupload.json'
        );
    }
}