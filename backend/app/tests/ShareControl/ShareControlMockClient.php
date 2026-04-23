<?php

namespace ShareControl;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use SilverStripe\Core\Config\Config;

class ShareControlMockClient extends Client {
    private static $response = null;

    public function request(string $method, $uri = '', array $options = []): ResponseInterface {
        if (static::$response) {
            return new Response(200, [], static::$response);
        } else {
            return new Response(404);
        }
    }

    public static function setResponseToFile(?string $file) {
        if (!$file) {
            static::$response = null;
            return;
        }

        if (str_starts_with("/", $file)) {
            $file = substr($file, 1);
        }
        if (file_exists($file)) {
            static::$response = file_get_contents($file);
        } else if (file_exists(__DIR__ . "/" . $file)) {
            static::$response = file_get_contents(__DIR__ . "/" . $file);
        } else {
            static::$response = null;
        }
    }
}