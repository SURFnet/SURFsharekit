<?php

namespace SurfSharekit\Api;

use DataObjectCSVFileEncoder;
use http\Exception;
use Ramsey\Uuid\Uuid;
use RepoItemJsonApiDescription;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use StatsDownloadJsonApiDescription;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\StatsDownload;

/**
 * Class InternalJsonApiController
 * @package SurfSharekit\Api
 * This class is the entry point to download repoItems in CSV format,
 * It use the same filter, sort notation etc as the internal JSONApi controller, but its output is CSV -> thus CSVJsonApiController
 */
class CSVJsonApiController extends JsonApiController {
    /**
     * @return mixed
     * returns the parts of the url added to base url to denominate this API
     */
    protected function getApiURLSuffix() {
        return '/api/v1/csv';
    }

    private static $allowed_actions = [
        'getJsonApiRequest'
    ];

    private static $allowed_reportTypes = [
        'export', 'statistics', 'downloads'
    ];

    /**
     * @return mixed
     * returns a map of DataObjects to their Respective @see DataObjectJsonApiDescription
     */
    protected function getClassToDescriptionMap() {
        return [
            RepoItem::class => new RepoItemJsonApiDescription(),
            StatsDownload::class => new StatsDownloadJsonApiDescription()
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
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
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
        return InstituteScoper::getAll($objectClass);
    }

    /**
     * @return mixed called when \A HTTP method GET is called, follows the JSON:API protocol to create a response based on SilverStripe DataObjects and their JsonApiDescription
     */
    public function getJsonApiRequest() {
        $request = $this->getRequest();
        $this->classToDescriptionMap = $this->getClassToDescriptionMap();
        //https://jsonapi.org/format/#content-negotiation-clients
        $stringOfIncludedRelationships = $this->request->requestVar("include") ?: "";
        $this->listOfIncludedRelationships = explode(',', $stringOfIncludedRelationships);

        //$dataObjectJsonApiDescriptor = new DataObjectJsonApiEncoder($this->classToDescriptionMap, $listOfIncludedRelationships);

        $requestedObjectClass = $request->param("Action");

        $objectClass = null;
        foreach ($this->classToDescriptionMap as $class => $description) {
            if ($description->type_plural == $requestedObjectClass) {
                $objectClass = $class;
                break;
            }
        }

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

        if ($requestVars && isset($requestVars['purge'])) {
            $purgeCache = true;
            set_time_limit(0); // increase time limit when purging
        } else {
            $purgeCache = false;
            set_time_limit(600);
        }

        if ($requestVars && isset($requestVars['filter'])) {
            $filtersPerAttribute = $requestVars['filter'];
            if (!is_array($filtersPerAttribute)) {
                return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError(), 400);
            }
            $this->filters = $filtersPerAttribute;
        }

        if ($requestVars && isset($requestVars['page'])) {
            if (!is_array($requestVars['page'])) {
                return $this->createJsonApiBodyResponseFrom(static::invalidPaginationJsonApiBodyError(), 400);
            }
            $paginationQuery = $requestVars['page'];

            if (isset($paginationQuery['number'])) {
                $this->pageNumber = $paginationQuery['number'];
            }
            if (isset($paginationQuery['size'])) {
                $this->pageSize = $paginationQuery['size'];
            }

            if (!$this->pageNumber || !$this->pageSize) {
                return $this->createJsonApiBodyResponseFrom(static::invalidPaginationJsonApiBodyError(), 400);
            }
        }

        if ($requestVars && isset($requestVars['reportType'])) {
            $reportType = $requestVars['reportType'];
        } else {
            $reportType = 'export';
        }
        if (!in_array($reportType, self::$allowed_reportTypes)) {
            return $this->createJsonApiBodyResponseFrom(static::invalidParameter(), 400);
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

            if (!$objectToDescribe->canReport(Security::getCurrentUser())) {
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

        $dataObjectJsonApiDescriptor = $this->getDataObjectJsonApiEncoder();
        try {
            // Get all items in scope
            /**
             * @var DataList $objectsToDescribe
             */
            $objectsToDescribe = $this->getDataList($objectClass);

            // Apply general filter
            $objectDescription = $dataObjectJsonApiDescriptor->getJsonApiDescriptionForClass($objectClass);
            try {
                $objectsToDescribe = $objectDescription->applyGeneralFilter($objectsToDescribe);
            } catch (Exception $e) {
                return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError($e->getMessage()), 403);
            }
            // Apply field filters
            foreach ($this->filters as $field => $value) {
                try {
                    $objectsToDescribe = $objectDescription->applyFilter($objectsToDescribe, $field, $value);
                } catch (Exception $e) {
                    return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError($e->getMessage()), 400);
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

            if ($reportType == 'statistics') {
                if ($objectClass != RepoItem::class) {
                    return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError("Cannot export $reportType $objectClass"), 400);
                }
                return DataObjectCSVFileEncoder::repoItemStatsToCSVFile($objectsToDescribe, $purgeCache);
            } else if ($reportType == 'export') {
                if ($objectClass != RepoItem::class) {
                    return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError("Cannot export $reportType $objectClass"), 400);
                }
                return DataObjectCSVFileEncoder::repoItemListToCSVFile($objectsToDescribe, $purgeCache);
            } else {
                if ($objectClass != StatsDownload::class) {
                    return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError("Cannot export $reportType $objectClass"), 400);
                }
                return DataObjectCSVFileEncoder::statsDownloadsToCSVFile($objectsToDescribe);
            }
        } catch (Exception $e) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError($e->getMessage()), 403);
        }
    }

    protected function deleteToObject($objectClass, $requestBody, $prexistingObject, $relationshipToModify = null) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function canViewObjectToDescribe($objectToDescribe) {
        return $objectToDescribe->canView(Security::getCurrentUser());
    }
}