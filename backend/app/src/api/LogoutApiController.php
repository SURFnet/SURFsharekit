<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Api\LoginProtectedApiController;
use SurfSharekit\Models\Person;

class LogoutApiController extends LoginProtectedApiController {
    public function index(HTTPRequest $request) {
        if ($request->isPOST()) {
            $this->doLogout();
        } else {
            $this->getResponse()->addHeader("Content-Type", "application/json");
            $this->getResponse()->setStatusCode(405);
            return json_encode([
                JsonApi::TAG_ERRORS => [
                    [
                        JsonApi::TAG_ERROR_TITLE => 'Method not allowed',
                        JsonApi::TAG_ERROR_DETAIL => 'This endpoint requires a POST'
                    ]
                ]
            ]);
        }
    }

    private function doLogout() {
        /** @var ApiMemberExtension|Member $user */
        $user = Security::getCurrentUser();
        $user->ApiToken = null;
        $user->ApiTokenAcc = null;
        $user->ApiTokenExpires = null;
        $user->write();
    }
}