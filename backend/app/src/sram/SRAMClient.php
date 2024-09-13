<?php

namespace SurfSharekit\SRAM;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use http\Env;
use SilverStripe\Core\Environment;

class SRAMClient
{
    private $httpClient;

    public function __construct() {
        $this->httpClient = new Client();
    }

    public static function getGroups(): array {
        $response = (new self())->get("/scim/v2/Groups");

        return json_decode($response->getBody(), true);
    }

    public static function getUser(string $userId): array {
        $response = (new self())->get("/scim/v2/Users/$userId");

        return json_decode($response->getBody(), true);
    }

    public function get($endpoint, $options = []): Response {
        return $this->httpClient->get(Environment::getEnv("SRAM_API_URL") . $endpoint, $this->prepareOptions($options));
    }

    private function prepareOptions($options): array {
        $options = $options ?? [];
        $options['headers'] = $this->prepareHeaders($options);

        return $options;
    }

    private function prepareHeaders($option): array {
        $headers = [
            'Authorization' => "Bearer " . Environment::getEnv("SRAM_API_TOKEN")
        ];

        return array_merge($headers, ($option['headers'] ?? []));
    }

}