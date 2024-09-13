<?php

namespace SurfSharekit\Api\Upload\Controllers;


use Zooma\SilverStripe\Models\SwaggerDocsHelper;

class UploadApiDocsController extends UploadApiController {

    private static $url_handlers = [
        'GET /'  => 'getDocs'
    ];

    private static $allowed_actions = [
        "getDocs"
    ];

    public function getDocs() {
        return SwaggerDocsHelper::renderDocs(
            '/api/upload/v1',
            "/api/upload/v1/docs",
            '../docs/upload_api_swagger.json'
        );
    }
}