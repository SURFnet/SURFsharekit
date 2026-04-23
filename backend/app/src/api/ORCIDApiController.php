<?php

namespace SurfSharekit\Api;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Person;
use SurfSharekit\Orcid\Service\OrcidService;

class ORCIDApiController extends LoginProtectedApiController {
    private static $url_handlers = [
        '' => 'register',
    ];
    private static $allowed_actions = [
        'register', 'callback'
    ];

    private ?OrcidService $orcidService = null;

    public function register(HTTPRequest $request) {
        $user = Security::getCurrentUser();
        if (!$user) return $this->httpError(401, 'Unauthorized');
        $request->getSession()->set('ORCIDLinkUserID', $user->ID);
        $request->getSession()->save($request);
        Logger::infoLog("User added to session $user->ID");
        $authUrl = $this->getOrcidService()->getAuthorizationUrl();
        return $this->redirect($authUrl);
    }

    public function callback(HTTPRequest $request) {
        try {
            Logger::infoLog("Session info : " . print_r($request->getSession()->getAll(), true));
            $userId = $request->getSession()->get('ORCIDLinkUserID');
            if (!$userId) {
                Logger::warnLog("User not found in session.");
                throw new Exception("User not found in session.");
            }
            $user = Person::get()->byID($userId);
            if (!$user) {
                Logger::warnLog("User not found in database.");
                throw new Exception("User not found in database.");
            }
            $request->getSession()->clear('ORCIDLinkUserID');

            $code = $request->getVar('code');
            $state = $request->getVar('state');

            $orcidService = $this->getOrcidService();
            $token = $orcidService->handleCallback($code, $state);
            $profile = $orcidService->getProfile($token);

            $user->ORCID = $profile->orcidId;
            $user->ORCIDRegisterDate = DBDatetime::now();
            $user->write();

            return $this->redirect(Controller::join_links([Environment::getEnv('FRONTEND_BASE_URL'), "profile?orcid_verified=1"]));
        } catch (Exception $e) {
            Logger::warnLog("ORCID callback failed: " . $e->getMessage());
            return $this->redirect(Controller::join_links([Environment::getEnv('FRONTEND_BASE_URL'), "profile?orcid_verified=0"]));
        }
    }

    protected function getOrcidService(): OrcidService {
        if (!$this->orcidService) {
            $this->orcidService = new OrcidService(
                Environment::getEnv('ORCID_CLIENT_ID'),
                Environment::getEnv('ORCID_CLIENT_SECRET'),
                Controller::join_links([Environment::getEnv('SS_BASE_URL'), "api/v1/register/orcid/callback"]),
                Environment::getEnv('APPLICATION_ENVIRONMENT') == 'live' ? 'production' : 'sandbox'
            );
        }
        return $this->orcidService;
    }
}