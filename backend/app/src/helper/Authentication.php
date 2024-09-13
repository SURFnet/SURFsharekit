<?php

namespace SurfSharekit\Models\Helper;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use stdClass;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\UnauthorizedException;

class Authentication {

    public static function getJWT(?HTTPRequest $request = null): stdClass {
        if (!$request) {
            /** @var Controller $controller */
            if ($controller = Controller::has_curr()) {
                $request = $controller->getRequest();
            } else {
                throw new UnauthorizedException(ApiErrorConstant::GA_UA_002);
            }
        }

        $jwt = $request->BearerToken ?? null;
        if (!$jwt) {
            throw new UnauthorizedException(ApiErrorConstant::GA_UA_002);
        }

        return $jwt;
    }

    public static function setJWT(HTTPRequest $request, stdClass $token): void {
        $request->BearerToken = $token;
    }
}