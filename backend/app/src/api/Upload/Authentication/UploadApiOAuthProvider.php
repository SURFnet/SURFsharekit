<?php

namespace SilverStripe\api\Upload\Authentication;

use DateTime;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LogicException;
use SilverStripe\api\Exceptions\InternalServerErrorException;
use SilverStripe\Authentication\Providers\OAuthProvider;
use SilverStripe\Core\Environment;
use stdClass;
use SurfSharekit\Api\Exceptions\ForbiddenException;
use SurfSharekit\Api\Exceptions\UnauthorizedException;
use SurfSharekit\Models\UploadApiClient;
use SurfSharekit\Models\UploadApiClientConfig;
use UnexpectedValueException;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;

class UploadApiOAuthProvider extends OAuthProvider {

    private UploadApiClient $uploadApiClient;
    private string $instituteIdentifier;

    protected array $supported_grant_types = [
        "client_credentials"
    ];

    public function __construct(UploadApiClient $uploadApiClient, string $instituteIdentifier, string $grant_type) {
        parent::__construct($grant_type);
        $this->uploadApiClient = $uploadApiClient;
        $this->instituteIdentifier = $instituteIdentifier;
    }

    protected function provideJWTPayload(): array {
        $payload = parent::provideJWTPayload();

        /** @var UploadApiClientConfig $config */
        $config = $this->uploadApiClient->UploadApiClientConfigs()->filter(["InstituteUuid" => $this->instituteIdentifier])->first();
        if (!$config) {
            throw new ForbiddenException(ApiErrorConstant::UA_FB_001);
        }

        $payload["sub"] = $this->uploadApiClient->Uuid;
        $payload["institute"] = $this->instituteIdentifier;
        $payload["allowedRepoTypes"] = $config->getStringList();

        return $payload;
    }

    protected function provideSecret(): string {
        $privateKey = file_get_contents(Environment::getEnv("UPLOAD_API_JWT_PRIVATE_KEY_PATH"));

        if (!$privateKey) {
            throw new InternalServerErrorException(ApiErrorConstant::GA_ISE_002, "Due to an unexpected error the server was unable to generate a token");
        }

        return $privateKey;
    }

    public static function validateToken(string $token): stdClass {
        $publicKey = file_get_contents(Environment::getEnv("UPLOAD_API_JWT_PUBLIC_KEY_PATH"));
        $alg = Environment::getEnv("UPLOAD_API_JWT_ALG");

        if (!$publicKey) {
            throw new InternalServerErrorException(ApiErrorConstant::GA_ISE_002);
        }

        try {
            $jwt = JWT::decode($token, new Key($publicKey, $alg));
            $uploadApiClient = UploadApiClient::get()->filter([
                "Uuid" => $jwt->sub,
                "IsDisabled" => false
            ])->first();

            if (!$uploadApiClient) {
                throw new UnauthorizedException(ApiErrorConstant::GA_UA_002);
            }

            $expirationDate = $uploadApiClient->ExpirationDate;
            if ($expirationDate && DateTime::createFromFormat("Y-m-d H:i:s", $expirationDate)->getTimestamp() <= (new DateTime())->getTimestamp()) {
                throw new UnauthorizedException(ApiErrorConstant::GA_UA_002);
            }

            /** @var UploadApiClientConfig $config */
            $config = $uploadApiClient->UploadApiClientConfigs()->filter(["InstituteUuid" => $jwt->institute])->first();
            if (!$config) {
                throw new ForbiddenException(ApiErrorConstant::UA_FB_001);
            }

            return $jwt;
        } catch (LogicException $e) {
            throw new InternalServerErrorException(ApiErrorConstant::GA_ISE_002);
        } catch (UnexpectedValueException $e) {
            throw new UnauthorizedException(ApiErrorConstant::GA_UA_002);
        }
    }
}