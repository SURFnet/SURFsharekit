<?php

namespace SurfSharekit\Api;

use DataObjectCSVFileEncoder;
use Exception;
use Ramsey\Uuid\Uuid;
use RepoItemJsonApiDescription;
use SilverStripe\api\internal\descriptions\ExportItemJsonApiDescription;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SurfSharekit\Models\ExportItem;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\StatsDownload;
use SurfSharekit\Piwik\PiwikCSVFileEncoder;

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
        'getJsonApiRequest',
        'postJsonApiRequest'
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
            ExportItem::class => new ExportItemJsonApiDescription()
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
        return $this->createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
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
        if ($objectClass === ExportItem::class) {
            return ExportItem::get()->filter([
                'Person.ID' => Security::getCurrentUser()->ID
            ]);
        }

        return InstituteScoper::getAll($objectClass);
    }

    public function postJsonApiRequest() {
        $exportItem = ExportItem::create([
            "Args" => json_encode($this->getRequest()->getVars()),
            "PersonID" => Security::getCurrentUser()->ID
        ]);

        $exportItem->write();

        return $this->createJsonApiBodyResponseFrom([], 202);
    }

    /**
     * @return mixed called when \A HTTP method GET is called, follows the JSON:API protocol to create a response based on SilverStripe DataObjects and their JsonApiDescription
     */
    public function generateExport() {
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

        // COPIED FROM handleAction!

        //https://jsonapi.org/format/#content-negotiation-clients
        $stringOfIncludedRelationships = $this->request->requestVar("include") ?: "";
        $this->listOfIncludedRelationships = explode(',', $stringOfIncludedRelationships);

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

        // END COPY

        if ($requestVars && isset($requestVars['purge'])) {
            $purgeCache = true;
            set_time_limit(0); // increase time limit when purging
        } else {
            $purgeCache = false;
            set_time_limit(600);
        }

        if ($requestVars && isset($requestVars['reportType'])) {
            $reportType = $requestVars['reportType'];
        } else {
            $reportType = 'export';
        }

        if ($reportType === 'downloads') {
            return PiwikCSVFileEncoder::getDownloadsCSV($requestVars['ExportItem'], $requestVars);
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
                throw new Exception($e ?: 'Please specify filters as e.g.: ....objectType?filter[attribute]=value');
            }
            // Apply field filters
            foreach ($this->filters as $field => $value) {
                try {
                    $objectsToDescribe = $objectDescription->applyFilter($objectsToDescribe, $field, $value);
                } catch (Exception $e) {
                    throw new Exception($e ?: 'Please specify filters as e.g.: ....objectType?filter[attribute]=value');
                }
            }

            // Apply sort
            foreach ($this->sorts as $sortField => $ascOrDesc) {
                try {
                    $objectsToDescribe = $objectDescription->applySort($objectsToDescribe, $sortField, $ascOrDesc);
                } catch (Exception $e) {
                    throw new Exception($e ?: 'Please specify sorting as e.g.: ....sort=title,-authorName');
                }
            }

            if ($reportType == 'statistics') {
                if ($objectClass != RepoItem::class) {
                    throw new Exception("Cannot export $reportType $objectClass");
                }
                return DataObjectCSVFileEncoder::repoItemStatsToCSVFile($requestVars['ExportItem'], $objectsToDescribe, $purgeCache);
            } else if ($reportType == 'export') {
                if ($objectClass != RepoItem::class) {
                    throw new Exception("Cannot export $reportType $objectClass");
                }
                return DataObjectCSVFileEncoder::repoItemListToCSVFile($requestVars['ExportItem'], $objectsToDescribe, $purgeCache);
            } else {
                if ($objectClass != StatsDownload::class) {
                    throw new Exception("Cannot export $reportType $objectClass");
                }
                return DataObjectCSVFileEncoder::statsDownloadsToCSVFile($requestVars['ExportItem'], $objectsToDescribe);
            }
        } catch (Exception $e) {
            throw new Exception($e ?: 'You do not have the permissions for this action');
        }
    }

    private function getExports() {
        $exportItems = ExportItem::get()->filter([
            "PersonID" => Security::getCurrentUser()->ID
        ])->sort('Created DESC');

        $responseBody = new ArrayList();

        foreach ($exportItems as $exportItem) {
            $responseBody->push([
                "id" => $exportItem->Uuid,
                "status" => $exportItem->Status,
                "created" => $exportItem->Created,
                "finished" => $exportItem->FinishedAt,
                "file" => [
                    "url" => $exportItem->File->getAbsoluteURL()
                ]
            ]);
        }

        return $responseBody;
    }

    protected function canViewObjectToDescribe($objectToDescribe) {
        return $objectToDescribe->canView(Security::getCurrentUser());
    }

    protected function deleteToObject($objectClass, $requestBody, $prexistingObject, $relationshipToModify = null) {
        /** @var ExportItem $object */
        if (null === $object = $objectClass::get()->find('Uuid', $this->getRequest()->param('ID'))) {
            $this->getResponse()->setStatusCode(404);
            return;
        }

        if (!$object->canDelete()) {
            $this->getResponse()->setStatusCode(204);
            return;
        }

        $object->delete();

        $this->getResponse()->setStatusCode(204);
    }
}