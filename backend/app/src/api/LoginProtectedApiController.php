<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Logger;

/**
 * Class LoginProtectedApiController
 * @package SurfSharekit\Api
 * This method is an abstract class used to ensure the current HTTP action has a corresponding member via the Authorization Bearer Token Http Header
 * This token can be missing, invalid or expired
 * If none of the above applies, the corresponding member is logged in
 */
abstract class LoginProtectedApiController extends CORSController {
    const ALLOW_APITOKEN_VERIFICATION_WITHOUT_HASH = true;
    const IGNORE_TOKEN_EXPIRATION = false;

    private $statusRedirectsTo;

    public function __construct() {
        parent::__construct();
    }

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
        $requestedUrl = $_SERVER['REQUEST_URI'];
        $possiblePaths = [
            "repoitemupload",
            "jsonapi",
            "oaipmh"
        ];

        foreach ($possiblePaths as $possiblePath){
            if (
                $requestedUrl === "/api/$possiblePath/v1/docs" ||
                $requestedUrl === "/api/$possiblePath/v1/docs?json=1" ||
                $requestedUrl === "/api/$possiblePath/repoItems/v1/docs" ||
                $requestedUrl === "/api/$possiblePath/repoItems/v1/docs?json=1" ||
                $requestedUrl === "/api/$possiblePath/persons/v1/docs" ||
                $requestedUrl === "/api/$possiblePath/persons/v1/docs?json=1"
            ){
                return;
            }
        }

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

    function setStatusRedirectsTo(int $status, string $location, array $queryParams = [], $isFile = false) {
        $this->statusRedirectsTo[$status] = [
            'isFile' => $isFile,
            'location' => $location,
            'queryParams' => $queryParams
        ];
    }

    function getStatusRedirectsTo(int $status) {
        return $this->statusRedirectsTo[$status] ?? null;
    }

    protected function afterHandleRequest() {
        $response = $this->getResponse();
        $code = $response->getStatusCode();
        if ($code === 403 || $code === 401) {
        if (null !== $redirectTo = $this->getStatusRedirectsTo($code)) {
                $this->response->setStatusCode(302);

                $requestUrl = Environment::getEnv('SS_BASE_URL') . '/' . $this->request->getURL(true);

                $queryParamsArray = [
                    "redirectPrivate" => 1
                ];

                $queryParamsArray = array_merge($queryParamsArray, $redirectTo['queryParams']);

                // redirect always last
                $queryParamsArray['redirect'] = $requestUrl;

                $queryParams = [];
                foreach ($queryParamsArray as $key => $value) {
                    $queryParams[] = "$key=$value";
                }

                $redirectUrl = $redirectTo['location'] . '?' . implode('&', $queryParams);
                if ($redirectTo["isFile"]) {
                    $redirectUrl = $redirectUrl . "&fileId=" . $this->request->param("ID");
                }

                $response->addHeader('Location', $redirectUrl);
            }
        }
        parent::afterHandleRequest();
    }
}