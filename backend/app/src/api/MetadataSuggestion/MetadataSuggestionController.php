<?php

namespace SurfSharekit\Api;

use SilverStripe\api\BaseController;
use SilverStripe\api\internal\Data\MetadataSuggestionRequest;
use SilverStripe\api\ResponseHelper;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Services\MetadataSuggestion\MetadataSuggestionService;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;

class MetadataSuggestionController extends BaseController {

    private static $url_handlers = [
        'POST /' => 'handlePOST',
    ];

    private static $allowed_actions = [
        'handlePOST',
    ];

    public static function handlePOST(HTTPRequest $request): HttpResponse {
        $metadataSuggestionRequest = MetadataSuggestionRequest::fromJson($request->getBody());

        if (!$metadataSuggestionRequest) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_001);
        }

        $metadataSuggestionService = metadataSuggestionService::create();
        $suggestions = $metadataSuggestionService->getSuggestions(
            $metadataSuggestionRequest->metaFieldUuid,
            $metadataSuggestionRequest->repoItemRepoItemFileUuid,
            $metadataSuggestionRequest->metaFieldOptionUuid
        );

        $responseBody = [
            "suggestions" => $suggestions
        ];

        return ResponseHelper::responseSuccess($responseBody);
    }
}