<?php

namespace SurfSharekit\Api;

use Aws\ComputeOptimizer\ComputeOptimizerClient;
use Aws\S3\S3Client;
use DataObjectJsonApiBodyEncoder;
use DataObjectJsonApiEncoder;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\Promise\CancellationException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use RepoItemFileJsonApiDescription;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\RepoItemFile;

/**
 * Class S3FileUploadController
 * @package SurfSharekit\Api
 * Used to create a url that can be usd for two minutes to download a file
 */
class S3FileUploadPartApiController extends CORSController {
    private static $url_handlers = [
        'POST uploadPart' => 'uploadPart'
    ];

    private static $allowed_actions = [
        'uploadPart'
    ];

    public function uploadPart(HTTPRequest $request) {
        HTTPCacheControlMiddleware::singleton()->disableCache();
        $this->send();
    }

    // Helper function
    function __($key, array $array, $default = null)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }


    /**
     * @inheritdoc
     */
    public function send()
    {
        $client = new Client();

        if( ! isset($curl_timeout)) {
            $curl_timeout = 30;
        }

        $headers = getallheaders();
        $method = $this->__('REQUEST_METHOD', $_SERVER);
        $url = $this->__('X-Proxy-Url', $headers);

        /** @var S3Client $s3Client */
        $s3Client = Injector::inst()->create('Aws\\S3\\S3Client');
        $s3Base = $s3Client->getEndpoint();
        $s3Host = $s3Base->getHost();

        if( ! $url) {
            http_response_code(400) and exit("X-Proxy-Url header missing");
        }

        $urlData = parse_url($url);
        $urlHost = $urlData['host'];

        if(stripos($urlHost, $s3Host) === false) {
            http_response_code(400) and exit("X-Proxy-Url not allowed");
        }

        if( ! parse_url($url, PHP_URL_SCHEME)) {
            http_response_code(403) and exit("Not an absolute URL: $url");
        }

        $ignore = ['Cookie', 'Host', 'X-Proxy-URL'];
        $headers = array_diff_key($headers, array_flip($ignore));
        $body = "";

        switch($method)
        {
            case 'GET':
                break;
            case 'PUT':
            case 'POST':
            case 'DELETE':
            default:
                // Capture the post body of the request to send along
                $body = file_get_contents('php://input');
                break;
        }

        try {
            //Create an HTTP request
            $request = new Request($method, $url, $headers,$body);
            //Make the HTTP request
            $response = $client->send($request);
        } catch (GuzzleException $e) {
            if ($e->hasResponse()) {
                $response=$e->getResponse();
            }
        } catch (Exception $e) {
            echo $e;
            echo "=================";
            if ($e->hasResponse()) {
                $response=$e->getResponse();
            }
        }

        Logger::debugLog(print_r($response, true));

        // Remove any existing headers
        header_remove();
        $this->setupCors();
        //Print all the headers except for "Transfer-Encoding" because chunked responses will end up failing.
        foreach ($response->getHeaders() as $key => $value) {
            if($key!="Transfer-Encoding"){
                header("$key: $value[0]");
            }
        }



//Print the response code
        http_response_code($response->getStatusCode());

        Logger::debugLog(print_r($response->getBody()->getContents(), true));
// And finally the body
        return $response->getBody()->getContents();
    }

}