<?php

namespace SurfSharekit\Models\Helper;

use SilverStripe;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;

/**
 * JSHelper gathers the JS that is needed on the page
 */
class JSHelper {

    /* @var SilverStripe\Control\HTTPRequest $request */
    private $request;

    private $WebsiteConfig = false;

    public function __construct() {
    }

    public function setRequest($request) {
        $this->request = $request;
    }

    /**
     * @desc: Collect all js into the Community_Website object
     * @return string
     */
    public function getJS() {

        $JSString = "var Website = {};";

        if ($this->WebsiteConfig) {
            $JSString .= "Website.Config = " . $this->WebsiteConfig . ";";
        }

        return $JSString;
    }

    /**
     * @desc: Prepare website config like url & host etc..
     */
    public function addWebsiteConfig() {

        $UrlQuery = Director::baseURL();

        if (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']) {
            // https
            $requestScheme = 'https';
        } else {
            $requestScheme = 'http';
        }

        $HttpHost = $requestScheme . '://' . $_SERVER['HTTP_HOST'] . '/';
        if (strpos($HttpHost, '192') !== false || strpos($HttpHost, 'localhost') !== false || strpos($HttpHost, '127.0.0.1') !== false) {
            $HttpHost = $HttpHost . Director::BaseURL();
        }
        $FullUrl = $requestScheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $Url = $requestScheme . '://' . $_SERVER['HTTP_HOST'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $Params = json_encode($this->request->getVars());

        $this->WebsiteConfig = "{
                                    'UrlQuery': '" . $UrlQuery . "',
                                    'UrlAjax': '" . $UrlQuery . "req/',
                                    'HttpHost': '" . $HttpHost . "',
                                    'Url': '" . $Url . "',
                                    'FullUrl': '" . $FullUrl . "',
                                    'Env': '" . Environment::getenv('SS_ENVIRONMENT_TYPE') . "', 
                                    'Params': $Params
                                }";


    }

}