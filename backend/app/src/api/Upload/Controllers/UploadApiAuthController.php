<?php

namespace SurfSharekit\Api\Upload\Controllers;

use SilverStripe\api\Upload\Authentication\UploadApiOAuthProvider;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Security;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\UnauthorizedException;
use SurfSharekit\Models\Helper\Authentication;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Models\UploadApiClient;

class UploadApiAuthController extends UploadApiController {

    public function beforeHandleRequest(HTTPRequest $request) {
        parent::beforeHandleRequest($request);

        $authorizationHeader = $request->getHeader("Authorization");
        if (!$authorizationHeader) {
            throw new UnauthorizedException(ApiErrorConstant::GA_UA_001);
        }

        $bearerToken = explode(" ", $authorizationHeader)[1] ?? null;
        $jwt = UploadApiOAuthProvider::validateToken($bearerToken);
        Authentication::setJWT($request, $jwt);

        if ($apiClient = UploadApiClient::get()->find('Uuid', Authentication::getJWT($request)->sub)) {
            if ($apiClient->UploadApiUser !== null) {
                Security::setCurrentUser($apiClient->UploadApiUser);
            }
        }
    }
}