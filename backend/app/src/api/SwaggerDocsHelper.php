<?php

namespace Zooma\SilverStripe\Models;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

class SwaggerDocsHelper
{
    public static function renderDocs(string $serverUrl, string $jsonUrl, string $jsonFilePath) {
        $controller = Controller::curr();

        if ($controller && $controller->getRequest()->requestVar('json') === "1") {
            $response = new HTTPResponse();

            $response->addHeader('Content-Type', 'Application/json');

            $json = file_get_contents($jsonFilePath);
            $decodedJson = json_decode($json, true);

            $decodedJson['servers'] = [[
                "url" =>  $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $serverUrl,
                "description" => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']
            ]];

            return $response->setBody(json_encode($decodedJson, JSON_PRETTY_PRINT));
        }

        return ArrayData::create([
            'JsonUrl' => "$jsonUrl?json=1"
        ])->renderWith('Swagger/Swagger');
    }
}