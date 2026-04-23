<?php

namespace SurfSharekit\Models\Helper;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use stdClass;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\UnauthorizedException;

class Authentication {
    private static $bearerToken;

    public static function getJWT(?HTTPRequest $request = null): stdClass {
        $jwt = static::$bearerToken ?? null;
        if (!$jwt) {
            throw new UnauthorizedException(ApiErrorConstant::GA_UA_002);
        }

        return $jwt;
    }

    public static function setJWT(HTTPRequest $request, stdClass $token): void {
        static::$bearerToken = $token;
    }
}