<?php

namespace SurfSharekit\Piwik\Api;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use SilverStripe\Core\Environment;

class PiwikAPI {

    private string $clientId;
    private string $clientSecret;
    private string $url;
    private string $siteId;

    private ?PiwikAccessToken $accessToken = null;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $url,
        string $siteId
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->url = $url;
        $this->siteId = $siteId;
    }

    public function query() {
        return (new PiwikQuery($this, "query"));
    }

    public function events() {
        return (new PiwikQuery($this, "events"));
    }

    public function getAccessToken(): PiwikAccessToken {
        if ($accessToken = $this->accessToken) {
            if (!$accessToken->getExpiresAt() - 20 < time()) {
                return $this->accessToken;
            }
        }

        $this->authenticate();

        return $this->getAccessToken();
    }

    private function setAccessToken(PiwikAccessToken $accessToken): void {
        $this->accessToken = $accessToken;
    }

    private function authenticate() {
        $res = (new Client())->post($this->getUrl() . "/auth/token", [
            RequestOptions::FORM_PARAMS => [
                "grant_type" => "client_credentials",
                "client_id" => Environment::getEnv("PIWIK_API_CLIENT_ID"),
                "client_secret" => Environment::getEnv("PIWIK_API_CLIENT_SECRET")
            ]
        ]);

        $this->setAccessToken(PiwikAccessToken::fromJson(json_decode($res->getBody(), true)));
    }

    private function getClientId(): string {
        return $this->clientId;
    }

    private function getClientSecret(): string {
        return $this->clientSecret;
    }

    public function getUrl(): string {
        return $this->url;
    }

    public function getSiteId(): string {
        return $this->siteId;
    }
}