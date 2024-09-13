<?php

namespace SurfSharekit\Api;

use SilverStripe\Security\Member;
use SurfSharekit\Api\ApiMemberExtension;
use SurfSharekit\Api\JsonApiController;
use SurfSharekit\Api\LoginProtectedApiController;
use Zooma\SilverStripe\Models\SwaggerDocsHelper;

class OaipmhSwaggerApiController extends LoginProtectedApiController
{
    protected $channel;

    private static $url_handlers = [
        'GET docs' => 'getDocs'
    ];

    private static $allowed_actions = [
        'getDocs'
    ];

    protected function getApiURLSuffix() {
        return '/api/oaipmh/v1';
    }

    protected function userHasValidLogin(Member $member) {
        if ($member->isDefaultAdmin()) {
            return true;
        } else if (ApiMemberExtension::hasApiUserRole($member)) {
            return true;
        }
        return false;
    }

    public function getDocs() {
        return SwaggerDocsHelper::renderDocs(
            '/api/oaipmh/channel/v1',
            "/api/oaipmh/v1/docs",
            '../docs/oaipmh_swagger.json'
        );
    }

}