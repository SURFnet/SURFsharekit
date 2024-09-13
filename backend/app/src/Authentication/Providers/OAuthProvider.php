<?php

namespace SilverStripe\Authentication\Providers;

use DateTime;
use Firebase\JWT\JWT;
use SilverStripe\api\Upload\Data\TokenResponse;
use SilverStripe\Core\Environment;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;

abstract class OAuthProvider extends TokenProvider {

    protected array $supported_grant_types = [];

    public function __construct(string $grant_type) {
        if (!in_array($grant_type, $this->supported_grant_types)) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_003, "The provided grant_type is not currently supported");
        }
    }
    abstract protected function provideSecret(): string;
    protected function provideJWTPayload(): array {
        $dateTime = (new DateTime());
        return [
            "iss" => $this->getIss(),
            "exp" => (new DateTime())->modify("+ $this->token_life_time seconds")->getTimestamp(),
            "iat" => $dateTime->getTimestamp(),
            "nbf" => $dateTime->getTimestamp()
        ];
    }

    private function getIss() {
        $scheme = array_key_exists('REQUEST_SCHEME', $_SERVER) ? $_SERVER['REQUEST_SCHEME'] : 'http';

        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }

    public function provideToken(): TokenResponse {
        $payload = $this->provideJWTPayload();
        $alg = Environment::getEnv("UPLOAD_API_JWT_ALG");

        $secret = $this->provideSecret();
        $encodedJWT = JWT::encode($payload, $secret, $alg);

        $tokenResponse = new TokenResponse();
        $tokenResponse->access_token = $encodedJWT;
        $tokenResponse->expires_at = $payload["exp"];
        return $tokenResponse;
    }
}