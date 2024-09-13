<?php

namespace SurfSharekit\Api;

use DataObjectJsonApiBodyEncoder;
use DataObjectJsonApiEncoder;
use Exception;
use Ramsey\Uuid\Uuid;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use SurfSharekit\Models\RepoItemFile;
use UuidExtension;

/**
 * Class JsonApiController
 * @package SurfSharekit\Api
 * This class is the entry point for a json api to GET,POST and PATCH DataObjects inside the logged in member's scope
 */
abstract class JsonApiController extends LoginProtectedApiController {
    /**
     * @var array A list of all accessible DataObjects and their JsonApi definition
     */
    var $classToDescriptionMap;

    /**
     * @var array that can be set during a get request to limit what fields are send to the client
     */
    var $sparseFields = null;

    /**
     * @var array that can be set during a get request to define what the sorts should be
     */
    var $sorts = [];

    /**
     * @var array that can contain attribute filters the client requested when getting a datalist
     */
    var $filters = [];
    /**
     * @var array that can contain attribute filters like the fitlers above but these filters are used after the given filters above
     */
    var $additionalFilters = [];
    /**
     * @var int value to be used to set the pagination information without doing a ->count()
     */
    var $totalCount = null;
    /**
     * @var int that can be set during a get request to limit how many results are sent to the client
     */
    var $pageSize = null;
    /**
     * @var int that can be set during a get request to offset the results that are sent to the client
     */
    var $pageNumber = null;

    /**
     * @var int $purge
     * Variable used to force cache purge
     */
    var $purge = 0;

    /**
     * @var array exploded include=comments,author query paramether
     */
    var $listOfIncludedRelationships = [];

    private static $url_handlers = [
        'GET $Action/$ID/$Relations/$RelationName' => 'getJsonApiRequest',
        'POST $Action/$ID/$Relations/$RelationName' => 'postJsonApiRequest',
        'PATCH $Action/$ID/$Relations/$RelationName' => 'patchJsonApiRequest',
        'DELETE $Action/$ID/$Relations/$RelationName' => 'deleteJsonApiRequest',
    ];

    private static $allowed_actions = [
        'getJsonApiRequest',
        'postJsonApiRequest',
        'patchJsonApiRequest',
        'deleteJsonApiRequest'
    ];

    protected function handleAction($request, $action) {
        //https://jsonapi.org/format/#content-negotiation-clients
        $stringOfIncludedRelationships = $this->request->requestVar("include") ?: "";
        $this->listOfIncludedRelationships = explode(',', $stringOfIncludedRelationships);

        $requestVars = $request->getVars();
        if ($requestVars && isset($requestVars['fields'])) {
            $sparseFieldsPerType = $requestVars['fields'];
            if (!is_array($sparseFieldsPerType)) {
                return $this->createJsonApiBodyResponseFrom(static::invalidSparseFieldsJsonApiBodyError(), 400);
            }
            $this->sparseFields = $sparseFieldsPerType;
        }

        if ($requestVars && isset($requestVars['sort'])) {
            foreach (explode(',', $requestVars['sort']) as $sortString) {
                if (strpos($sortString, '-') === 0) {
                    $this->sorts[substr($sortString, 1, strlen($sortString) - 1)] = 'DESC';
                } else {
                    $this->sorts[$sortString] = 'ASC';
                }
            }
        }

        if ($requestVars && isset($requestVars['filter'])) {
            $filtersPerAttribute = $requestVars['filter'];
            if (!is_array($filtersPerAttribute)) {
                return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError(), 400);
            }
            $this->filters = $filtersPerAttribute;
        }

        if ($requestVars && isset($requestVars['additionalFilter'])) {
            $additionalFiltersPerAttribute = $requestVars['additionalFilter'];
            if (!is_array($additionalFiltersPerAttribute)) {
                return $this->createJsonApiBodyResponseFrom(static::invalidAdditionalFiltersJsonApiBodyError(), 400);
            }
            $this->additionalFilters = $additionalFiltersPerAttribute;
        }

        if ($requestVars && isset($requestVars['purge'])) {
            $purgeCache = intval($requestVars['purge']);
            $this->purge = $purgeCache;
        }

        if ($requestVars && isset($requestVars['page'])) {
            if (!is_array($requestVars['page'])) {
                return $this->createJsonApiBodyResponseFrom(static::invalidPaginationJsonApiBodyError(), 400);
            }
            $paginationQuery = $requestVars['page'];

            if (isset($paginationQuery['number'])) {
                $number = intval($paginationQuery['number']);
                if ($number) {
                    $this->pageNumber = $number;
                }
            }
            if (isset($paginationQuery['size'])) {
                $size = intval($paginationQuery['size']);
                if ($size) {
                    $this->pageSize = intval($size);
                }
            }

            if (!$this->pageNumber || !$this->pageSize) {
                return $this->createJsonApiBodyResponseFrom(static::invalidPaginationJsonApiBodyError(), 400);
            }
        }
        return parent::handleAction($request, $action);
    }

    public function postJsonApiRequest() {
        return $this->editJsonApiRequest('postToObject');
    }

    public function patchJsonApiRequest() {
        return $this->editJsonApiRequest('patchToObject');
    }

    public function deleteJsonApiRequest() {
        try {
            return $this->editJsonApiRequest('deleteToObject');
        } catch (Exception $e) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError($e->getMessage()), 403);
        }
    }

    /**
     * @return mixed called when A HTTP method PATCH is called, with current implementation, only objects can be patched, relationships not
     */
    public function editJsonApiRequest($callback) {
        $request = $this->getRequest();
        $this->classToDescriptionMap = $this->getClassToDescriptionMap();
        //https://jsonapi.org/format/#content-negotiation-clients
        if ($request->getHeader('Content-Type') != JsonApi::CONTENT_TYPE) {
            return $this->createJsonApiBodyResponseFrom(static::unsupportedTypeError(), 415);
        }

        $requestedObjectClass = $request->param('Action');
        $objectClass = null;
        foreach ($this->classToDescriptionMap as $class => $description) {
            if ($description->type_plural == $requestedObjectClass) {
                $objectClass = $class;
                break;
            }
        }
        if (!$objectClass) {
            //Cannot post object with type = $objectClass
            return $this->createJsonApiBodyResponseFrom(static::noneExistingObjectTypeJsonApiBodyArray(), 400);
        }

        //Posting an object of type = $objectClassx
        $objectUUID = $request->param("ID");
        $preexistingObject = null;
        if ($objectUUID) {
            if (!Uuid::isValid($objectUUID)) {
                return $this->createJsonApiBodyResponseFrom(static::invalidObjectIDJsonApiBodyArray(), 400);
            }
            $preexistingObject = self::getObjectOfTypeById($objectClass, $objectUUID);
        }

        if (($request->httpMethod() === 'PATCH' || $request->httpMethod() === 'DELETE') && !$preexistingObject) {
            //Can't find object to
            return $this->createJsonApiBodyResponseFrom(static::objectNotFoundJsonApiBodyError(), 404);
        }

        if ($preexistingObject) {
            if (in_array($request->httpMethod(), ['PATCH', 'POST']) && !$preexistingObject->canEdit(Security::getCurrentUser())) {
                return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError(), 403);
            }
        }

        $requestBody = null;
        //Can't PATCH OR POST without a request body
        if (in_array($request->httpMethod(), ['PATCH', 'POST']) && !($requestBody = json_decode($request->getBody(), true))) {
            return $this->createJsonApiBodyResponseFrom(static::missingRequestBodyError(), 400);
        }

        //patching relationship not supported
        if ($relationshipToPatch = ($request->param('RelationName') ?: $request->param('Relations'))) {
            if (!$this->classToDescriptionMap[$objectClass]->hasRelationship($relationshipToPatch)) {
                return $this->createJsonApiBodyResponseFrom(static::relationshipNotFoundJsonApiBodyError(), 404);
            }

            //Cannot part of a relationship without sending what to delete in the request body
            if ($request->httpMethod() == 'DELETE' && !($requestBody = json_decode($request->getBody(), true))) {
                return $this->createJsonApiBodyResponseFrom(static::missingRequestBodyError(), 400);
            }
        }

        if ($preexistingObject && !$relationshipToPatch && $request->httpMethod() == 'POST') {
            return $this->createJsonApiBodyResponseFrom(static::cannotModifyExistingJsonApiBodyError(), 404);
        }

        if ($request->httpMethod() === 'DELETE' && $relationshipToPatch && !$preexistingObject->canEdit(Security::getCurrentUser())) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError("You do not have permission to edit this object"), 403);
        } else if ($request->httpMethod() === 'DELETE' && !$relationshipToPatch && !$preexistingObject->canDelete(Security::getCurrentUser())) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError("You do not have permission to delete this object"), 403);
        }

        return $this->$callback($objectClass, $requestBody, $preexistingObject, $relationshipToPatch, $request->httpMethod() == 'PATCH');
    }

    /**
     * @return mixed called when \A HTTP method GET is called, follows the JSON:API protocol to create a response based on SilverStripe DataObjects and their JsonApiDescription
     */
    public function getJsonApiRequest() {
        HTTPCacheControlMiddleware::singleton()->disableCache();
        $request = $this->getRequest();
        $this->classToDescriptionMap = $this->getClassToDescriptionMap();

        $requestedObjectClass = $request->param("Action");

        $objectClass = null;
        foreach ($this->classToDescriptionMap as $class => $description) {
            if ($description->type_plural == $requestedObjectClass) {
                $objectClass = $class;
                break;
            }
        }
        if (!$objectClass) {
            //Cannot retrieve dataobjects with type = $objectClass
            return $this->createJsonApiBodyResponseFrom(static::noneExistingObjectTypeJsonApiBodyArray(), 400);
        }

        //Retrieving info with object(s) of type = $objectClass
        if ($objectId = $request->param("ID")) {
            //Retrieving a single object
            if ($objectId && !Uuid::isValid($objectId)) {
                return $this->createJsonApiBodyResponseFrom(static::invalidObjectIDJsonApiBodyArray(), 400);
            }
            $objectToDescribe = self::getObjectOfTypeById($objectClass, $objectId);

            if (!$objectToDescribe) {
                return $this->createJsonApiBodyResponseFrom(static::objectNotFoundJsonApiBodyError(), 404);
            }

            if($objectToDescribe->PendingForDestruction) {
                return $this->createJsonApiBodyResponseFrom(static::isLockedJsonApiBodyError(null), 423);
            }

            if (!$this->canViewObjectToDescribe($objectToDescribe)) {
                if ($objectToDescribe->IsRemoved) {
                    return $this->createJsonApiBodyResponseFrom(static::isLockedJsonApiBodyError(null), 423);
                }
                return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError(null), 403);
            }

            $requestedRelationName = $request->param('RelationName') ?: $request->param("Relations");

            //Retrieving description of the relation of $dataobject, not the actual object of the relation //Example: ..../relationships/comments
            if ($request->param("Relations") == 'relationships') {
                if (!$this->classToDescriptionMap[$objectClass]->hasRelationship($requestedRelationName)) {
                    return $this->createJsonApiBodyResponseFrom(static::relationshipNotFoundJsonApiBodyError(), 404);
                }
                return $this->getRelationOfDataObject($objectToDescribe, $requestedRelationName);
            }

            if ($request->param("RelationName")) { //Cannot access .../comments/comments, only .../comments
                return $this->createJsonApiBodyResponseFrom(static::invalidPathJsonApiBodyError(), 400);
            }
            if (!$requestedRelationName) { //Getting a single object
                return $this->getDataObject($objectToDescribe);
            }

            if (!$this->classToDescriptionMap[$objectClass]->hasRelationship($requestedRelationName)) {
                return $this->createJsonApiBodyResponseFrom(static::relationshipNotFoundJsonApiBodyError(), 404);
            }

            //Retrieving the actual objects the relation points to
            if ($this->pageSize || $this->pageNumber) {
                return $this->createJsonApiBodyResponseFrom(static::relationshipPaginationNotSupportedJsonApiBodyError(), 403);
            }

            return $this->getObjectsBehindRelationOfDataObject($objectToDescribe, $requestedRelationName);
        }

        //Not retrieving single object, thus retrieving multiple objects

        try {
            // Get all items in scope
            $objectsToDescribe = $this->getDataList($objectClass);
            $dataObjectJsonApiDescriptor = $this->getDataObjectJsonApiEncoder();

            try {
                // Apply general filter
                $objectDescription = $dataObjectJsonApiDescriptor->getJsonApiDescriptionForClass($objectClass);
                try {
                    $objectsToDescribe = $objectDescription->applyGeneralFilter($objectsToDescribe);
                } catch (Exception $e) {
                    return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError($e->getMessage()), 403);
                }
            } catch (Exception $e2) {
                return $this->createJsonApiBodyResponseFrom(static::invalidSparseFieldsJsonApiBodyError($e2->getMessage()), 404);
            }


            // Apply field filters
            foreach ($this->filters as $field => $value) {
                try {
                    $objectsToDescribe = $objectDescription->applyFilter($objectsToDescribe, $field, $value);
                } catch (Exception $e) {
                    return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError($e->getMessage()), 400);
                }
            }
            
            $possibleFilters = $objectDescription->getPossibleFilters($objectsToDescribe);

            // Apply field additional filters
            foreach ($this->additionalFilters as $field => $value) {
                try {
                    $objectsToDescribe = $objectDescription->applyFilter($objectsToDescribe, $field, $value);
                } catch (Exception $e) {
                    return $this->createJsonApiBodyResponseFrom(static::invalidAdditionalFiltersJsonApiBodyError($e->getMessage()), 400);
                }
            }

            // Apply sort
            foreach ($this->sorts as $sortField => $ascOrDesc) {
                try {
                    $objectsToDescribe = $objectDescription->applySort($objectsToDescribe, $sortField, $ascOrDesc);
                } catch (Exception $e) {
                    return $this->createJsonApiBodyResponseFrom(static::invalidSortJsonApiBodyError($e->getMessage()), 400);
                }
            }

            DB::query("SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED");
            $this->totalCount = $objectsToDescribe->count();
            DB::query("COMMIT");
            if ($this->pageNumber && $this->pageSize) {
                $objectsToDescribe = $objectsToDescribe->limit($this->pageSize, ($this->pageNumber - 1) * $this->pageSize);
            }

            $dataObjectJsonApiDescriptor->setTotalCount($this->totalCount);
            $dataObjectJsonApiDescriptor->setPurge($this->purge);

            $encodedObjects = DataObjectJsonApiBodyEncoder::dataListToMultipleObjectsJsonApiBodyArray($objectsToDescribe, $dataObjectJsonApiDescriptor, $possibleFilters, (BASE_URL . $this->getApiURLSuffix()));
            return static::createJsonApiBodyResponseFrom($encodedObjects, 200);
        } catch (Exception $e) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError($e->getMessage()), 403);
        }
    }

    /**
     * @param $response
     * @param int $statusCode
     * @return mixed
     * Utility method to generate JsonApi response from $response
     * if $response is an array, set httpstatuscode to 'heaviest' HTTP-Code of all codes in the jsonapi error list if applicaable
     * else if $response is a DataObject, set HttpStatusCode to 200 and describe it
     */
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

    /**
     * @param $objectClass
     * @param $objectUuid
     * @return false|\SilverStripe\ORM\DataObject|null
     * Utility function to get a certain DataObject based on its uuid instead of id
     */
    public static function getObjectOfTypeById($objectClass, $objectId) {
        $object = UuidExtension::getByUuid($objectClass, $objectId);
        if ($object && $object->exists()) {
            return $object;
        }
        return null;
    }

    abstract protected function canViewObjectToDescribe($objectToDescribe);

    public static function cannotModifyExistingJsonApiBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Invalid method, modifying existing object',
                    JsonApi::TAG_ERROR_DETAIL => 'Cannot POST to an already existing object, if you\'d like to edit the object, use PATCH',
                    JsonApi::TAG_ERROR_CODE => 'JAC_001'
                ]
            ]
        ];
    }

    public static function relationshipNotFoundJsonApiBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Invalid relationship name',
                    JsonApi::TAG_ERROR_DETAIL => 'The object request does not have a relationship of the requested name',
                    JsonApi::TAG_ERROR_CODE => 'JAC_002'
                ]
            ]
        ];
    }

    public static function unsupportedTypeError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Unsupported Content-Type',
                    JsonApi::TAG_ERROR_DETAIL => "Please specify Content-Type in your header as '" . JsonApi::CONTENT_TYPE . "''",
                    JsonApi::TAG_ERROR_CODE => 'JAC_003',
                    'links' => [
                        'href' => 'https://jsonapi.org/',
                        'meta' => [
                            'description' => 'A link describing the of the JSON;API protocol this API uses to communicate'
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function noneExistingObjectTypeJsonApiBodyArray() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Invalid object type',
                    JsonApi::TAG_ERROR_DETAIL => 'Requested an invalid object type in url',
                    JsonApi::TAG_ERROR_CODE => 'JAC_004'
                ]
            ]
        ];
    }

    public static function objectNotFoundJsonApiBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Invalid object id',
                    JsonApi::TAG_ERROR_DETAIL => 'The object with the url-specified id could not be found',
                    JsonApi::TAG_ERROR_CODE => 'JAC_005'
                ]
            ]
        ];
    }

    public static function invalidObjectIDJsonApiBodyArray() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Invalid object id',
                    JsonApi::TAG_ERROR_DETAIL => 'Id should be a valid UUID',
                    JsonApi::TAG_ERROR_CODE => 'JAC_016'
                ]
            ]
        ];
    }

    public static function modifyingRelationshipNotSupportedJsonApiBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Relationship editing not supported using relationary path',
                    JsonApi::TAG_ERROR_DETAIL => 'Please PATCH the base object you\'re trying to edit instead of editing the relation',
                    JsonApi::TAG_ERROR_CODE => 'JAC_006'
                ]
            ]
        ];
    }

    public static function filtersNotSupportedJsonApiBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Filters not supported',
                    JsonApi::TAG_ERROR_CODE => 'JAC_010'
                ]
            ]
        ];
    }

    public static function sortNotSupportedJsonApiBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Sorting not supported',
                    JsonApi::TAG_ERROR_CODE => 'JAC_017'
                ]
            ]
        ];
    }

    public static function relationshipPaginationNotSupportedJsonApiBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Relationship pagination not supported',
                    JsonApi::TAG_ERROR_CODE => 'JAC_007'
                ]
            ]
        ];
    }

    public static function missingRequestBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Missing Request body',
                    JsonApi::TAG_ERROR_DETAIL => 'Missing a correctly formatted JSON HTTP Request body with information on the object to POST, PATCH or relationship DELETE',
                    JsonApi::TAG_ERROR_CODE => 'JAC_008'
                ]
            ]
        ];
    }

    public static function invalidPathJsonApiBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Requested invalid path',
                    JsonApi::TAG_ERROR_DETAIL => "If you'd like to access the relationship information of an object use 'object/relationship/relationshipName'",
                    JsonApi::TAG_ERROR_CODE => 'JAC_009'
                ]
            ]
        ];
    }

    public static function invalidSparseFieldsJsonApiBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Incorrect fields query params',
                    JsonApi::TAG_ERROR_DETAIL => 'Please specify sparse fields as e.g.: ....objectType?fields[objectType]=title,name',
                    JsonApi::TAG_ERROR_CODE => 'JAC_010'
                ]
            ]
        ];
    }

    public static function invalidPaginationJsonApiBodyError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Incorrect pagination query params',
                    JsonApi::TAG_ERROR_DETAIL => 'Please specify pagination with both page size and page number as e.g.: ....objectType?page[size]=100&page[number]=2',
                    JsonApi::TAG_ERROR_CODE => 'JAC_011'
                ]
            ]
        ];
    }

    public static function unsupportedAction($message = null) {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Requested invalid path',
                    JsonApi::TAG_ERROR_DETAIL => $message ?? "This action is not supported",
                    JsonApi::TAG_ERROR_CODE => 'JAC_012'
                ]
            ]
        ];
    }

    public static function noPermissionJsonApiBodyError($message = null) {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Forbidden',
                    JsonApi::TAG_ERROR_DETAIL => $message ?: 'You do not have the permissions for this action',
                    JsonApi::TAG_ERROR_CODE => 'JAC_013'
                ]
            ]
        ];
    }

    public static function invalidFiltersJsonApiBodyError($message = null) {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Incorrect filter query params',
                    JsonApi::TAG_ERROR_DETAIL => $message ? $message : 'Please specify filters as e.g.: ....objectType?filter[attribute]=value',
                    JsonApi::TAG_ERROR_CODE => 'JAC_014'
                ]
            ]
        ];
    }

    public static function invalidAdditionalFiltersJsonApiBodyError($message = null) {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Incorrect additional filter query params',
                    JsonApi::TAG_ERROR_DETAIL => $message ? $message : 'Please specify filters as e.g.: ....objectType?filter[attribute]=value',
                    JsonApi::TAG_ERROR_CODE => 'JAC_014'
                ]
            ]
        ];
    }

    public static function invalidSortJsonApiBodyError($message = null) {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Incorrect sort query params',
                    JsonApi::TAG_ERROR_DETAIL => $message ? $message : 'Please specify sorting as e.g.: ....sort=title,-authorName',
                    JsonApi::TAG_ERROR_CODE => 'JAC_015'
                ]
            ]
        ];
    }

    public static function invalidParameter($message = null) {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Invalid parameter',
                    JsonApi::TAG_ERROR_DETAIL => $message ? $message : 'Invalid parameter used',
                    JsonApi::TAG_ERROR_CODE => 'JAC_020'
                ]
            ]
        ];
    }

    private static function isLockedJsonApiBodyError($message) {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Locked',
                    JsonApi::TAG_ERROR_DETAIL => $message ? $message : 'Object has been made inaccessible',
                    JsonApi::TAG_ERROR_CODE => 'JAC_021'
                ]
            ]
        ];
    }

    /**
     * @return mixed
     * returns the parts of the url added to base url to denominate this API
     */
    abstract protected function getApiURLSuffix();

    /**
     * @return mixed
     * returns a map of DataObjects to their Respective @see DataObjectJsonApiDescription
     */
    abstract protected function getClassToDescriptionMap();

    /**
     * @param $objectClass
     * @param $requestBody
     * @param DataObject $prexistingObject
     * @param $relationshipToPatch
     * @return mixed
     * Called when all error checks have been done
     */
    abstract protected function patchToObject($objectClass, $requestBody, $prexistingObject, $relationshipToPatch = null);

    /**
     * @param int $objectClass
     * @param $requestBody
     * @param $prexistingObject
     * @param $relationshipToPost
     * @return mixed
     * called after all error checks have been done and the request is legitimate
     */
    abstract protected function postToObject($objectClass, $requestBody, $prexistingObject = null, $relationshipToPost = null);

    /**
     * @param int $objectClass
     * @param $requestBody
     * @param $prexistingObject
     * @param $relationshipToModify
     * @return mixed
     * called after all error checks have been done and the request is legitimate
     */
    abstract protected function deleteToObject($objectClass, $requestBody, $prexistingObject, $relationshipToModify = null);

    /**
     * @param $objectToDescribe
     * @return mixed
     * called when the user requested a single dataobject
     */
    abstract protected function getDataObject($objectToDescribe);

    /**
     * @param $objectClass
     * @return mixed
     * Called when the use request all objects of a certain type
     */
    abstract protected function getDataList($objectClass);

    /**
     * @param $objectToDescribe
     * @param $requestedRelationName
     * @return mixed
     * called after all error check have been done and the user request the relationship of a dataobject
     */
    abstract protected function getRelationOfDataObject($objectToDescribe, $requestedRelationName);

    /**
     * @param DataObject|null $objectToDescribe
     * @param string $requestedRelationName
     * @return mixed
     * called when the client requested a description of the relation of a dataobject
     */
    abstract protected function getObjectsBehindRelationOfDataObject($objectToDescribe, $requestedRelationName);

    /**
     * @return DataObjectJsonApiEncoder
     * returns an jsonApiEncoder based on the request done
     */
    protected function getDataObjectJsonApiEncoder() {
        $dataObjectJsonApiDescriptor = new DataObjectJsonApiEncoder($this->classToDescriptionMap, $this->listOfIncludedRelationships);
        if ($this->sparseFields) {
            $dataObjectJsonApiDescriptor->setSparseFields($this->sparseFields);
        }

        if ($this->pageSize) {
            $dataObjectJsonApiDescriptor->setPagination($this->pageSize, $this->pageNumber);
        }
        return $dataObjectJsonApiDescriptor;
    }
}