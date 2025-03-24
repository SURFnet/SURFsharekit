<?php

namespace SilverStripe\api\external\jsonapi;

use SilverStripe\Security\Member;
use SurfSharekit\Api\ApiMemberExtension;
use SurfSharekit\Api\LoginProtectedApiController;
use Zooma\SilverStripe\Models\SwaggerDocsHelper;

class ExternalInstituteJsonApiController extends LoginProtectedApiController {
    protected $channel;

    private static $url_handlers = [
        'GET docs' => 'getDocs'
    ];

    private static $allowed_actions = [
        'getDocs'
    ];

    protected function getApiURLSuffix() {
        return '/api/jsonapi/institutes/v1';
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
            '/api/jsonapi/channel/v1',
            "/api/jsonapi/institutes/v1/docs",
            '../docs/external_json_institutes_swagger.json'
        );
    }
}