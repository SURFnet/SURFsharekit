<?php

namespace SilverStripe\api\Upload\Authentication;

use DateTime;
use SilverStripe\Authentication\AuthenticationResult;
use SilverStripe\Authentication\Authenticators\ClientAuthenticator;
use SilverStripe\Authentication\ClientAuthenticationResult;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\ForbiddenException;
use SurfSharekit\Api\Exceptions\UnauthorizedException;
use SurfSharekit\Models\UploadApiClient;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Models\UploadApiClientConfig;

class UploadApiClientAuthenticator extends ClientAuthenticator {

    private ?UploadApiClient $uploadApiClient = null;

    protected function performAuthentication(string $clientId, string $clientSecret, array $postVars, AuthenticationResult $result): ClientAuthenticationResult {
        $this->uploadApiClient = UploadApiClient::get()->filter([
            "ClientID" => $clientId,
            "IsDisabled" => false
        ])->first();

        if (!$this->uploadApiClient) {
            throw new UnauthorizedException(ApiErrorConstant::GA_UA_003);
        }

        $expirationDate = $this->uploadApiClient->ExpirationDate;
        if ($expirationDate && DateTime::createFromFormat("Y-m-d H:i:s", $expirationDate)->getTimestamp() <= (new DateTime())->getTimestamp()) {
            throw new UnauthorizedException(ApiErrorConstant::GA_UA_003);
        }

        $passwordVerified = password_verify($clientSecret, $this->uploadApiClient->ClientSecret);
        if (!$passwordVerified) {
            throw new UnauthorizedException(ApiErrorConstant::GA_UA_003);
        }

        $result->setClient($this->uploadApiClient);

        return $result;
    }

    protected function afterAuthentication(array $postVars, AuthenticationResult &$result) {
        parent::afterAuthentication($postVars, $result);

        /** @var UploadApiClientConfig $config */
        $config = $this->uploadApiClient->UploadApiClientConfigs()->filter(["InstituteUuid" => $postVars["institute"]])->first();
        if (!$config) {
            throw new ForbiddenException(ApiErrorConstant::UA_FB_001);
        }
    }
}