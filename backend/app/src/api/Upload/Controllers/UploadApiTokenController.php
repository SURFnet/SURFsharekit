<?php

namespace SurfSharekit\Api\Upload\Controllers;

use SilverStripe\api\ResponseHelper;
use SilverStripe\api\Upload\Authentication\UploadApiClientAuthenticator;
use SilverStripe\api\Upload\Authentication\UploadApiOAuthProvider;
use SilverStripe\Control\HTTPRequest;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;

class UploadApiTokenController extends UploadApiController {

    private static $url_handlers = [
        'POST /' => 'getToken',
    ];

    private static $allowed_actions = [
        'getToken',
    ];

    private static $supported_grant_types = [
        "client_credentials"
    ];

    public function getToken(HTTPRequest $request) {
        $requestParameters = $request->postVars();

        $clientId = $requestParameters["client_id"] ?? null;
        if (!$clientId) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_001);
        }

        $clientSecret = $requestParameters["client_secret"] ?? null;
        if (!$clientSecret) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_002);
        }

        $grantType = $requestParameters["grant_type"] ?? null;
        if (!$grantType) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_003);
        }

        $instituteUuid = $requestParameters["institute"] ?? null;
        if (!$instituteUuid) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_004);
        }

        $clientAuthenticator = new UploadApiClientAuthenticator();
        $clientAuthenticationResult = $clientAuthenticator->authenticate($clientId, $clientSecret, $requestParameters);

        $client = $clientAuthenticationResult->getClient();
        $tokenProvider = new UploadApiOAuthProvider($client, $instituteUuid, $grantType);
        $tokenResponse = $tokenProvider->provideToken();

        return ResponseHelper::responseSuccess(json_encode($tokenResponse));
    }
}