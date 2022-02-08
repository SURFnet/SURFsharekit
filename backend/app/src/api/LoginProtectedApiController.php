<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Class LoginProtectedApiController
 * @package SurfSharekit\Api
 * This method is an abstract class used to ensure the current HTTP action has a corresponding member via the Authorization Bearer Token Http Header
 * This token can be missing, invalid or expired
 * If none of the above applies, the corresponding member is logged in
 */
abstract class LoginProtectedApiController extends CORSController {
    const ALLOW_APITOKEN_VERIFICATION_WITHOUT_HASH = true;
    const IGNORE_TOKEN_EXPIRATION = true;

    /**
     * @param HTTPRequest $request
     * Method called before executing the request/routing the action to ensure a member is logged in
     */
    public function beforeHandleRequest(HTTPRequest $request) {
        parent::beforeHandleRequest($request);
        if (Security::getCurrentUser()) {
            return;
        }
        $this->setUserFromRequest($request);
    }

    protected function getMissingAuthorizationHeaderMessage() {
        return 'Missing Authorization Bearer token in header';
    }

    protected function getIncorrectBearerTokenMessage() {
        return 'Incorrectly formatted Bearer token in header';
    }

    protected function getIncorrectApiTokenMessage() {
        return 'Incorrect token';
    }

    protected function getExpiredApiTokenMessage() {
        return 'Api token expired';
    }

    protected function getInvalidLoginMessage() {
        return 'All institutes have been removed for your upper scope';
    }

    protected function userHasValidLogin(Member $member) {
        if ($member->isDefaultAdmin()) {
            return true;
        }

        foreach ($member->Groups() as $group) {
            if (($institute = $group->Institute()) && $institute->exists()) {
                return $institute->IsRemoved ? false : true;
            }
        }
        return false;
    }

    protected function setUserFromRequest(HTTPRequest $request) {
        $authorizationToken = $request->getHeader('Authorization');
        if (!$authorizationToken) {
            $this->getResponse()->setStatusCode(401);
            $this->getResponse()->setBody($this->getMissingAuthorizationHeaderMessage());
            return;
        }

        $bearerParts = explode(' ', $authorizationToken);
        if (sizeof($bearerParts) != 2) {
            $this->getResponse()->setStatusCode(401);
            $this->getResponse()->setBody($this->getIncorrectBearerTokenMessage());
            return;
        }

        $token = $bearerParts[1];
        /***
         * @var Member $member
         */
        if(Environment::getEnv('APPLICATION_ENVIRONMENT') == 'live'){
            $apiTokenField = 'ApiToken';
        }else{
            $apiTokenField = 'ApiTokenAcc';
        }

        $member = Member::get()->filter($apiTokenField, hash('sha512', $token))->first(); //bottleneck 1 (between 150 and 200 ms) https://docs.silverstripe.org/en/4/developer_guides/performance/caching/
        if (!$member && static::ALLOW_APITOKEN_VERIFICATION_WITHOUT_HASH) {
            $member = Member::get()->filter($apiTokenField, $token)->first();
        }

        if (!($member && $member->Exists())) {
            $this->getResponse()->setStatusCode(401);
            $this->getResponse()->setBody($this->getIncorrectApiTokenMessage());
            return;
        }

        if (!static::IGNORE_TOKEN_EXPIRATION && ApiMemberExtension::isTokenIsExpired($member)) {
            $this->getResponse()->setStatusCode(401);
            $this->getResponse()->setBody($this->getExpiredApiTokenMessage());
            return;
        }

        $hasValidLogin = $this->userHasValidLogin($member);

        if (!$hasValidLogin) {
            $this->getResponse()->setStatusCode(401);
            $this->getResponse()->setBody($this->getInvalidLoginMessage());
            return;
        }

        Security::setCurrentUser($member);
    }

    function isRedirectToLoginEnabled() {
        return false;
    }

    protected function afterHandleRequest() {
        $response = $this->getResponse();
        if ($this->isRedirectToLoginEnabled()) {
            $code = $response->getStatusCode();
            if ($code === 403 || $code === 401) {
                $this->response->setStatusCode(302);
            }
            $loginUrl = Environment::getEnv('FRONTEND_BASE_URL');
            $requestUrl = Environment::getEnv('SS_BASE_URL') . '/' . $this->request->getURL(true);
            $response->addHeader('Location', $loginUrl . '/login' . '?redirectPrivate=1&redirect=' . $requestUrl);
        }
        parent::afterHandleRequest();
    }
}