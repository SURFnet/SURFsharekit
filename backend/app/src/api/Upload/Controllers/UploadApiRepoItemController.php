<?php

namespace SurfSharekit\Api\Upload\Controllers;

use SilverStripe\api\ResponseHelper;
use SilverStripe\api\Upload\Data\ChangeRepoItemChannelsRequest;
use SilverStripe\api\Upload\Data\ChangeRepoItemStatusRequest;
use SilverStripe\api\Upload\Data\CreateRepoItemRequest;
use SilverStripe\api\Upload\Data\CreateRepoItemResponse;
use SilverStripe\api\Upload\Data\GetRepoItemResponse;
use SilverStripe\api\Upload\Data\RepoItemStatusResponse;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use SilverStripe\Services\Channel\ChannelService;
use SilverStripe\Services\RepoItem\RepoItemService;
use SilverStripe\Services\RepoItem\RepoItemTaskService;
use SurfSharekit\Api\Exceptions\ApiError;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\ForbiddenException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Api\Exceptions\NotImplementedException;
use SurfSharekit\Models\Helper\Authentication;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaFieldValue;
use SurfSharekit\Models\TaskCreator;

class UploadApiRepoItemController extends UploadApiAuthController {

    private static $url_handlers = [
        'GET $Uuid!/status'  => 'getRepoItemStatus',
        'POST $Uuid!/status'  => 'changeRepoItemStatus',
        'POST $Uuid!/channels' => 'changeRepoItemChannels',
        'POST $Uuid!/fill-request' => 'handleRepoItemFillRequest',
        'PATCH $Uuid!' => 'patchRepoItem',
        'GET $Uuid!' => 'getRepoItem',
        'DELETE $Uuid!' => 'deleteRepoItem',
        'POST /' => 'createRepoItem',
    ];

    private static $allowed_actions = [
        "getRepoItem",
        "createRepoItem",
        "patchRepoItem",
        "deleteRepoItem",
        "getRepoItemStatus",
        "changeRepoItemStatus",
        "changeRepoItemChannels",
        "handleRepoItemFillRequest",
    ];

    public function createRepoItem(HTTPRequest $request) {
        $createRepoItemRequest = CreateRepoItemRequest::fromJson($request->getBody());
        if (!$createRepoItemRequest) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_001);
        }

        $token = Authentication::getJWT($request);
        $rootInstituteUuid = $token->institute;
        $allowedRepoTypes = $token->allowedRepoTypes;

        $institute = Institute::getAllChildInstitutes($rootInstituteUuid, true)->filter(["Uuid" => $createRepoItemRequest->institute])->first();
        if (!$institute) {
            throw new ForbiddenException(ApiErrorConstant::UA_FB_002);
        }

        if (!in_array($createRepoItemRequest->repoItemType, $allowedRepoTypes)) {
            throw new ForbiddenException(ApiErrorConstant::GA_FB_001, "You do not have permission to create RepoItems with type $createRepoItemRequest->repoItemType");
        }

        $repoItemService = RepoItemService::create();
        DB::get_conn()->transactionStart();
        $repoItem = $repoItemService->createRepoItem($createRepoItemRequest->owner, $createRepoItemRequest->institute, $createRepoItemRequest->repoItemType);
        $repoItemService->addMetaData($repoItem, $createRepoItemRequest->metadata, $rootInstituteUuid);
        DB::get_conn()->transactionEnd();

        $responseBody = (new CreateRepoItemResponse($repoItem->Uuid))->toJson();

        return ResponseHelper::responseSuccess($responseBody);
    }

    public function deleteRepoItem(HTTPRequest $request) {
        $token = Authentication::getJWT($request);
        $rootInstituteUuid = $token->institute;

        $repoItemUuid = $request->param("Uuid");
        $repoItemService = RepoItemService::create();
        $repoItem = $repoItemService->findByUuid($repoItemUuid);
        if (!$repoItem) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002);
        }

        $institute = Institute::getAllChildInstitutes($rootInstituteUuid, true)->filter(["Uuid" => $repoItem->InstituteUuid])->first();
        if (!$institute) {
            throw new ForbiddenException(ApiErrorConstant::UA_FB_002);
        }

        $repoItem->disablePublishedCheckOnDelete = true; // disable so we can check in this controller and return a more fitting error
        if (!$repoItem->canDelete()) {
            throw new ForbiddenException(ApiErrorConstant::GA_FB_001);
        }

        if ($repoItem->IsRemoved){
            throw new BadRequestException(ApiErrorConstant::UA_BR_008, "Could not delete RepoItem $repoItem->Uuid as it has already been deleted");
        }

        if ($repoItem->Status === "Published") {
            throw new BadRequestException(ApiErrorConstant::UA_BR_008, "It's not allowed to delete RepoItems with the 'Published' status. Please patch to 'Draft' before deleting this RepoItem");
        }

        //Process to delete repoItem
        $repoItem->Status = 'Draft';
        $repoItem->IsRemoved = true;
        $repoItem->write();

        TaskCreator::getInstance()->createRecoverTasks($repoItem);
    }

    public function getRepoItem(HTTPRequest $request) {

        $token = Authentication::getJWT($request);
        $repoItemService = RepoItemService::create();

        if (null !== $uuid = $request->param('Uuid')) {
            $repoItem = RepoItem::get()->filter([
                'UUID' => $uuid
            ])->first();

            if ($repoItem && $repoItem->exists() && $repoItem instanceof RepoItem) {
                $responses = $repoItemService->getMetaData($repoItem, $token->institute);

                $response = new GetRepoItemResponse($repoItem->Uuid, $repoItem->Title, $repoItem->RepoType, $responses);
                return ResponseHelper::responseSuccess($response->toJson());
            }

            throw new BadRequestException(ApiErrorConstant::UA_NF_002);
        }
        throw new BadRequestException(ApiErrorConstant::GA_BR_002);
    }

    public function patchRepoItem(HTTPRequest $request) {
        // TODO: implement
        throw new NotImplementedException(ApiErrorConstant::GA_NI_001);
    }

    public function getRepoItemStatus(HTTPRequest $request) {
        $repoItemService = RepoItemService::create();

        $token = Authentication::getJWT($request);
        $rootInstituteUuid = $token->institute;

        $repoItemUuid = $request->param("Uuid");
        $repoItem = $repoItemService->findByUuid($repoItemUuid);
        if (!$repoItem) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002);
        }

        $institute = Institute::getAllChildInstitutes($rootInstituteUuid, true)->filter(["Uuid" => $repoItem->InstituteUuid])->first();
        if (!$institute) {
            throw new ForbiddenException(ApiErrorConstant::UA_FB_002);
        }

        $response = new RepoItemStatusResponse($repoItem->Status);
        return ResponseHelper::responseSuccess($response->toJson());
    }

    public function changeRepoItemStatus(HTTPRequest $request) {
        $repoItemService = RepoItemService::create();

        $changeRepoItemStatusRequest = ChangeRepoItemStatusRequest::fromJson($request->getBody());
        if (!$changeRepoItemStatusRequest) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_001);
        }

        $token = Authentication::getJWT($request);
        $rootInstituteUuid = $token->institute;

        $repoItemUuid = $request->param("Uuid");

        $repoItem = $repoItemService->findByUuid($repoItemUuid);
        if (!$repoItem) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002);
        }

        $institute = Institute::getAllChildInstitutes($rootInstituteUuid, true)->filter(["Uuid" => $repoItem->InstituteUuid])->first();
        if (!$institute) {
            throw new ForbiddenException(ApiErrorConstant::UA_FB_002);
        }

        $allowedStatuses = ["Published", "Submitted", "Draft"];
        if (!in_array($changeRepoItemStatusRequest->status, $allowedStatuses)) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_008, "Changing the status to '$changeRepoItemStatusRequest->status' is not allowed. Please provide one of the following statuses: (" . implode(', ', $allowedStatuses) . ")");
        }

        $repoItemService->changeRepoItemStatus($repoItem, $changeRepoItemStatusRequest->status);
        return $this->getResponse()->setStatusCode(200);
    }

    public function changeRepoItemChannels(HTTPRequest $request) {
        $repoItemService = RepoItemService::create();

        $changeRepoItemChannelsRequest = ChangeRepoItemChannelsRequest::fromJson($request->getBody());
        if (!$changeRepoItemChannelsRequest) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_001);
        }

        $repoItemUuid = $request->param("Uuid");
        $repoItem = $repoItemService->findByUuid($repoItemUuid);
        if (!$repoItem) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002);
        }

        // Validate scope
        $token = Authentication::getJWT($request);
        $rootInstituteUuid = $token->institute;
        $institute = Institute::getAllChildInstitutes($rootInstituteUuid, true)->filter(["Uuid" => $repoItem->InstituteUuid])->first();
        if (!$institute) {
            throw new ForbiddenException(ApiErrorConstant::UA_FB_002);
        }

        // Get all the channels that currently exist in the template of the provided repoitem
        $channelService = ChannelService::create();
        /** @var DataList<MetaField> $channelMetaFieldsAllowedForRepoItem */
        $channelsToEnable = $channelService->getChannelsForRepoItem($repoItem, $changeRepoItemChannelsRequest->channels);

        DB::get_conn()->transactionStart();
        // Set the RepoItemMetaFieldValue to IsRemoved = 1 for each channel that is currently enabled but was not included in the request body
        /** @var DataList<RepoItemMetaFieldValue> $currentlyEnabledChannels */
        $currentlyEnabledChannels = $channelService->getAllEnabledChannelsForRepoItem($repoItem);
        foreach ($currentlyEnabledChannels as $repoItemMetaFieldValue) {
            $channelService->disableChannelForRepoItem($repoItem, $repoItemMetaFieldValue);
        }

        // Set the RepoItemMetaFieldValue to IsRemoved = 1 for each channel that is currently enabled but was not included in the request body
        foreach ($channelsToEnable as $channelMetaField) {
            $channelService->enableChannelForRepoItem($repoItem, $channelMetaField);
        }
        DB::get_conn()->transactionEnd();

        return $this->getResponse()->setStatusCode(200);
    }

    public function handleRepoItemFillRequest(HTTPRequest $request) {
        $token = Authentication::getJWT($request);
        $rootInstituteUuid = $token->institute;

        $repoItemUuid = $request->param("Uuid");
        $repoItemService = RepoItemService::create();
        $repoItem = $repoItemService->findByUuid($repoItemUuid);
        if (!$repoItem) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002);
        }

        $institute = Institute::getAllChildInstitutes($rootInstituteUuid, true)->filter(["Uuid" => $repoItem->InstituteUuid])->first();
        if (!$institute) {
            throw new ForbiddenException(ApiErrorConstant::UA_FB_002);
        }

        if ($repoItem->Status != "Draft") {
            throw new BadRequestException(ApiErrorConstant::UA_BR_008, "Could not create fill requests for repoitem $repoItem->Uuid with status $repoItem->Status, please patch it to 'Draft' first");
        }

        $repoItemTaskService = RepoItemTaskService::create();
        $repoItemTaskService->createFillTasksForRepoItem($repoItem);

        return $this->getResponse()->setStatusCode(200);
    }
}