<?php

namespace SurfSharekit\Api;

use DateTime;
use Exception;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Class AccessTokenApiController
 * @package SurfSharekit\Api
 * Used to create a url that can be usd for two minutes to download a file
 */
class AccessTokenApiController extends LoginProtectedApiController {

    private static $url_handlers = [
        '' => 'getAccessToken',
    ];

    private static $allowed_actions = [
        'getAccessToken'
    ];

    public function getAccessToken() {
        $member = Security::getCurrentUser();

        $this->getResponse()->addHeader("content-type", "application/json");
        $this->getResponse()->setStatusCode(200);
        $this->getResponse()->setBody(json_encode(['accessToken' => self::generateAccessTokenForMember($member)]));
        return $this->getResponse();
    }

    public static function generateAccessTokenForMember(Member $member) {
        $apiToken = Environment::getEnv('APPLICATION_ENVIRONMENT') == 'live' ? $member->ApiToken : $member->ApiTokenAcc;
        $time = new DateTime();
        $timeString = $time->format('d-m-Y H:i:s');
        $dataToEncode = $apiToken . ';' . $timeString;

        return bin2hex(openssl_encrypt($dataToEncode, Environment::getEnv("FILE_DOWNLOAD_CIPHER"), Environment::getEnv("FILE_DOWNLOAD_PRIVATE_KEY"), 0, Environment::getEnv("FILE_DOWNLOAD_IV")));
    }

    public static $ACCESS_TOKEN_ERROR = 'AccessToken is not valid';

    public static function consumeAccessToken($accessToken) {
        if (!$accessToken || strlen($accessToken) % 2 !== 0){
            throw new Exception(static::$ACCESS_TOKEN_ERROR);
        }
        $accessToken = hex2bin($accessToken);
        $decodedData = openssl_decrypt($accessToken, Environment::getEnv("FILE_DOWNLOAD_CIPHER"), Environment::getEnv("FILE_DOWNLOAD_PRIVATE_KEY"), 0, Environment::getEnv("FILE_DOWNLOAD_IV"));
        if (!$decodedData) {
            throw new Exception(static::$ACCESS_TOKEN_ERROR);
        }
        $decodedData = explode(';', $decodedData);
        if (count($decodedData) !== 2) {
            throw new Exception(static::$ACCESS_TOKEN_ERROR);
        }
        if (Environment::getEnv('APPLICATION_ENVIRONMENT') == 'live') {
            $memberBehindAccessToken = Member::get()->filter(['ApiToken' => $decodedData[0]])->first();
        } else {
            $memberBehindAccessToken = Member::get()->filter(['ApiTokenAcc' => $decodedData[0]])->first();
        }
        if (!$memberBehindAccessToken || !$memberBehindAccessToken->exists()) {
            throw new Exception(static::$ACCESS_TOKEN_ERROR);
        }
        $timeAccessTokenGenerated = strtotime($decodedData[1]);
        if (!$timeAccessTokenGenerated){
            throw new Exception(static::$ACCESS_TOKEN_ERROR);
        }
        if ((time() - $timeAccessTokenGenerated) > 60 * 15){ //access token can only be consumed for 15 minutes after creation
            throw new Exception(static::$ACCESS_TOKEN_ERROR);
        }
        return $memberBehindAccessToken;
    }
}