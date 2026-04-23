<?php

namespace SurfSharekit\Api;

use Exception;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\ForbiddenException;
use SurfSharekit\Api\Exceptions\UnauthorizedException;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\LogItem;
use SurfSharekit\Models\Person;
use SurfSharekit\ShareControl\ShareControlApiCommunicator;
use Throwable;

/**
 * Class LmsJsonApiController
 * @package SurfSharekit\Api
 * This class is the entry point for the LMS API endpoints
 */
class LmsJsonApiController extends JsonApiController {
    var $pageSize = 20;
    var $maxPageSize = 20;
    var $minPageSize = 1;
    /**
     * @return mixed
     * returns the parts of the url added to base url to denominate this API
     */
    protected function getApiURLSuffix() {
        return '/api/v1/lms';
    }

    private static $url_handlers = [
        'GET items' => 'getJsonApiRequest',
        'POST flag/$UUID' => 'flagItem'
    ];

    private static $allowed_actions = [
        'getJsonApiRequest',
        'flagItem'
    ];

    protected function getClassToDescriptionMap() {
        return [];
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

    protected function getDataObject($objectToDescribe) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getDataList($objectClass) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getObjectsBehindRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    /**
     * Get items from ShareControl API
     * @return HTTPResponse
     */
    public function getJsonApiRequest() {
        $request = $this->getRequest();

        // Get the institute from the request and validate its existence
        if ($instituteUuid = $request->getVar('institute')) {
            $institute = Institute::get()->find("Uuid", $instituteUuid);
            if (!$institute || !$institute->exists()) {
                throw new BadRequestException(ApiErrorConstant::GA_NF_003);
            }
        } else {
            throw new BadRequestException(ApiErrorConstant::UA_BR_004);
        }

        /** @var Person $owner */
        $owner = Security::getCurrentUser();
        if (!$owner) {
            throw new UnauthorizedException(ApiErrorConstant::GA_UA_003);
        }

        // Check if member has permission to request items from this institute
        if (!$institute->canRequestLmsItem($owner)){
            throw new ForbiddenException(ApiErrorConstant::GA_FB_001);
        }
        
        // Handle pagination
        if ($request->getVar('page')) {
            $page = $request->getVar('page');
            if (isset($page['size']) && $page['size']) {
                $this->pageSize = min(max(intval($page['size']), $this->minPageSize), $this->maxPageSize);
            }
            if (isset($page['number']) && $page['number']) {
                $this->pageNumber = max(1, intval($page['number']));
            }
        }

        // Handle filters
        if ($request->getVar('filter')) {
            $this->filters = $request->getVar('filter');
        }

        try {
            $communicator = Injector::inst()->get(ShareControlApiCommunicator::class);
            $searchTerm = $this->filters['search'] ?? '';
            $iBron = $institute->IBronEnabled ? $institute->IBronName : false;

            if ($iBron && $searchTerm) {
                $items = $communicator::searchItems($iBron, $searchTerm, $this->pageNumber, $this->pageSize);
            } else {
                $items = [];
            }
            
            $response = [
                JsonApi::TAG_DATA => [],
                JsonApi::TAG_META => [
                    JsonApi::TAG_TOTAL_COUNT => count($items)
                ],
                JsonApi::TAG_LINKS => [
                    JsonApi::TAG_LINKS_SELF => 'items',
                    JsonApi::TAG_LINKS_FIRST => 'items?page[number]=1&page[size]=' . $this->pageSize,
                    JsonApi::TAG_LINKS_LAST => 'items?page[number]=' . ceil(count($items) / $this->pageSize) . '&page[size]=' . $this->pageSize
                ]
            ];

            foreach ($items as $item) {
                $authors = [];
                foreach ($item->authors as $author) {
                    $authors[] = [
                        'fullName' => $author->fullName,
                        'institute' => "Institute"
                    ];
                }
                $response[JsonApi::TAG_DATA][] = [
                    JsonApi::TAG_TYPE => 'item',
                    JsonApi::TAG_ID => $item->uuid,
                    JsonApi::TAG_ATTRIBUTES => [
                        'title' => $item->title,
                        'subTitle' => $item->subTitle,
                        'firstHarvested' => $item->firstHarvested,
                        'keywords' => $item->keywords,
                        'authors' => $authors
                    ]
                ];
            }

            return $this->createJsonApiBodyResponseFrom($response, 200);
        } catch (Throwable $e) {
            LogItem::errorLog("ShareControl search failed for iBron '$iBron' with search '$searchTerm': {$e->getMessage()}", __CLASS__, __FUNCTION__);
            return $this->createJsonApiBodyResponseFrom(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Flag an item in ShareControl API
     * @param HTTPRequest $request
     * @return HTTPResponse
     * @throws UnauthorizedException
     */
    public function flagItem(HTTPRequest $request) {
        $requestBody = json_decode($request->getBody(), true);
        $uuid = $request->param('UUID');

        // Get the institute from the request and validate its existence
        if ($instituteUuid = $requestBody['data']['institute'] ?? null) {
            $institute = Institute::get()->find("Uuid", $instituteUuid);
            if (!$institute || !$institute->exists()) {
                throw new BadRequestException(ApiErrorConstant::GA_NF_003);
            }
        } else {
            throw new BadRequestException(ApiErrorConstant::UA_BR_004);
        }

        /** @var Person $owner */
        $owner = Security::getCurrentUser();
        if (!$owner) {
            throw new UnauthorizedException(ApiErrorConstant::GA_UA_003);
        }

        // Check if member has permission to request items from this institute
        if (!$institute->canRequestLmsItem($owner)){
            throw new ForbiddenException(ApiErrorConstant::GA_FB_001);
        }

        try {
            $communicator = Injector::inst()->create(ShareControlApiCommunicator::class);
            $iBron = $institute->IBronEnabled ? $institute->IBronName : false;
            if ($iBron) {
                $success = $communicator::flagItem($iBron, $uuid, $owner->Uuid);
            } else {
                LogItem::warnLog("Tried to flag item $uuid for institute $institute->Uuid without iBron enabled", __CLASS__, __FUNCTION__);
                return $this->createJsonApiBodyResponseFrom(['error' => 'The provided institute has no ibron access'], 400);
            }
            if ($success) {
                return $this->createJsonApiBodyResponseFrom(['message' => 'Item flagged successfully'], 200);
            } else {
                LogItem::errorLog("Failed to flag item $uuid in iBron '$iBron' for owner $owner->Uuid", __CLASS__, __FUNCTION__);
                return $this->createJsonApiBodyResponseFrom(['error' => 'Failed to flag item'], 502);
            }
        } catch (Exception $e) {
            LogItem::errorLog("ShareControl flag failed for item $uuid: {$e->getMessage()}", __CLASS__, __FUNCTION__);
            return $this->createJsonApiBodyResponseFrom(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @param $objectToDescribe
     * @return bool
     */
    protected function canViewObjectToDescribe($objectToDescribe) {
        return true;
    }
} 