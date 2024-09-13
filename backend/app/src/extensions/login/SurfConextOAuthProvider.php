<?php

namespace SurfSharekit\Extensions;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use SilverStripe\Core\Environment;
use SurfSharekit\extensions\login\SurfConextOAuthUser;

class SurfConextOAuthProvider extends AbstractProvider
{


    public function getBaseAuthorizationUrl() {
        return Environment::getEnv("CMS_CONEXT_URL") . '/authorize';
    }

    public function getBaseAccessTokenUrl(array $params) {
        return Environment::getEnv("CMS_CONEXT_URL") . "/token";
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token) {
        return Environment::getEnv("CMS_CONEXT_URL") . "/userinfo";
    }

    protected function getAuthorizationHeaders($token = null) {
        return [
            'Authorization' => 'Bearer ' . $token->getToken()
        ];
    }

    public function getDefaultScopes() {
        return ['email', 'openid'];
    }

    protected function checkResponse(ResponseInterface $response, $data) {
        if (empty($data['error'])) {
            return;
        }

        $code = 0;
        $error = $data['error'];

        if (is_array($error)) {
            $code = $error['code'];
            $error = $error['message'];
        }

        throw new IdentityProviderException($error, $code, $data);
    }

    protected function createResourceOwner(array $response, AccessToken $token) {
        return new SurfConextOAuthUser($response);
    }
}