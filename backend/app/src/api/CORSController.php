<?php

namespace SurfSharekit\Api;

use SilverStripe;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

/**
 * Class CORSController
 * @package SurfSharekit\Api
 * This class needs to be extended if you want to use CORS within your API Controller.
 * The extension is mandatory because using the 'enableCrossOriginRequests' as a static function or instance variable will first 'use/include/require' this class and then the header output is somehow already sent to the client.
 * Which will still cause the CORS error. See: https://www.php.net/manual/en/function.header.php
 */
class CORSController extends Controller {
    public function beforeHandleRequest(HTTPRequest $request) {
        parent::beforeHandleRequest($request);
        $this->setupCors();
    }

    /**
     * CORS: Cross-Origin Resource Sharing
     * When a non simple request is made (e.g when the content type is application/json) a preflight call is first made to determine if the request is safe.
     * This preflight request is sent before the original request.
     * This determines whether or not the origin is correct and if the methods and request headers are supported by the server.
     * If this is not the case a CORS (Cross-origin Resource Sharing) error is immediately thrown after the preflight, which blocks the intended request from executing.
     * See: https://stackoverflow.com/questions/10636611/how-does-access-control-allow-origin-header-work
     * or https://www.codecademy.com/articles/what-is-cors
     *
     * @param Controller $controller
     */
    function setupCors() {
        // Allow from any origin
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
            // you want to allow, and if so:
            header("Access-Control-Expose-Headers: location");
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }

        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                // may also be using PUT, PATCH, HEAD etc
                header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
            }
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }

            exit(0);
        }
    }
}