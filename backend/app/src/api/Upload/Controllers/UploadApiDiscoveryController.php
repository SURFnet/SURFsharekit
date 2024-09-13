<?php

namespace SurfSharekit\Api\Upload\Controllers;

use Exception;
use SilverStripe\api\ResponseHelper;
use SilverStripe\api\Upload\Data\Channels\Channel;
use SilverStripe\api\Upload\Data\MetaFields\GetMetaFieldResponse;
use SilverStripe\api\Upload\Data\MetaFields\MetaField;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Services\Channel\ChannelService;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Models\Helper\Authentication;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Services\Discover\DiscoverUploadService;

class UploadApiDiscoveryController extends UploadApiAuthController {

    private static $url_handlers = [
        'GET institutes' => 'getInstitutes',
        'GET metafields' => 'getMetafields',
        'GET channels' => 'getChannels',
    ];

    private static $allowed_actions = [
        'getInstitutes',
        'getMetafields',
        'getChannels',
    ];

    public function index() {
        throw new NotFoundException(ApiErrorConstant::GA_NF_001);
    }

    public function getInstitutes(HTTPRequest $request){

        $token = Authentication::getJWT($request);
        $discoverUploadService = new DiscoverUploadService();

        $data = [];
        $institutes = $discoverUploadService->getInstitutes($token->institute);
        foreach ($institutes as $childInstitute) {
            $data[] = new \SilverStripe\api\Upload\Data\Institute(
                $childInstitute->Uuid,
                $childInstitute->InstituteUuid,
                $childInstitute->Title,
                $childInstitute->Level
            );
        }

        $this->getResponse()->addHeader('Content-Type', 'application/json');

        $output = [
            'meta' => [
                'count' => count($data)
            ],
            'data' => [
                'institutes' => $data
            ]
        ];

         return json_encode($output);
    }

    public function getMetafields(HTTPRequest $request){
        $token = Authentication::getJWT($request);

        $discoverUploadService = new DiscoverUploadService();
        $metaFields = $discoverUploadService->getMetafields($token->institute, $token->allowedRepoTypes);

        return ResponseHelper::responseDataList($metaFields, function ($metaField) use ($discoverUploadService) {
            try {
                $metaFieldResponse = new MetaField();
                $metaFieldResponse->id = $metaField->Uuid;
                $metaFieldResponse->title = $metaField->Label_EN;
                $metaFieldResponse->type = $metaField->MetaFieldType()->Key;
                $metaFieldResponse->jsonType = $metaField->JsonType;
                $metaFieldResponse->jsonKey = $metaField->JsonKey;
                $metaFieldResponse->description = $metaField->Description_EN;
                $metaFieldResponse->exampleValue = $discoverUploadService->checkAndReturnValue($metaField->MetaFieldJsonExample()->Example);
                $metaFieldResponse->options = $discoverUploadService->getMetaFieldOptions($metaField);
                return $metaFieldResponse;
            } catch(Exception $e) {
                Logger::infoLog("Something went wrong while converting MetaField $metaField->Uuid to json");
            }

            return null;
        });
    }

    public function getChannels(HTTPRequest $request) {
        $token = Authentication::getJWT($request);
        $instituteUuid = $token->institute;
        $allowedRepoTypes = $token->allowedRepoTypes;

        $channelService = new ChannelService();
        $channelMetaFields = $channelService->getAllChannelsWithinInstituteSubtree($instituteUuid, $allowedRepoTypes);

        return ResponseHelper::responseDataList($channelMetaFields, function ($channelMetaField) {
            try {
                $channel = new Channel();
                $channel->id = $channelMetaField->Uuid;
                $channel->title = $channelMetaField->Label_EN;

                return $channel;
            } catch(Exception $e) {
                Logger::infoLog("Something went wrong while converting MetaField $channelMetaField->Uuid to json");
            }

            return null;
        });
    }
}