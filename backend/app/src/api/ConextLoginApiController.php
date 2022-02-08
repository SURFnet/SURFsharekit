<?php

namespace SurfSharekit\Api;

use DataObjectJsonApiEncoder;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;

/**
 * Class ConextLoginApiController
 * @package SurfSharekit\Api
 * A class used as the entryPoint to login with a context OpenID callback code and redirect_uri
 */
class ConextLoginApiController extends CORSController {

    /**
     * @config
     * @var string $conextURL
     * The base url of conext to connect to for member information and code validation
     */
    private $conextURL = null;

    const ERROR_TAG = 'error';

    private static $url_handlers = [
        '' => 'login',
    ];
    private static $allowed_actions = [
        'login'
    ];

    /**
     * @return false|\Psr\Http\Message\StreamInterface|string
     * The only endpoint of this Controller, used to login a user with an OpenID callback code and callback uri
     * This function check whether or not the code is correct and retrieves member information from conext if it is.
     * This memberinformation contains an email-address, and institute code which is used to search for an existing member
     * If the member does not exist, it is automatically created and placed in a group corresponding to the Conext member\s role and member's institute
     * A hashcode is generated and used as ApiToken which can be verified against a re-hashed database-stored version to not have to login again.
     */
    public function login() {
        $this->conextURL = Environment::getEnv('CONEXT_URL');
        $client_id = Environment::getEnv('CONEXT_CLIENT_ID');
        $client_secret = Environment::getEnv('CONEXT_CLIENT_SECRET');

        $request = $this->getRequest();
        $code = $request->requestVar('code');
        $redirect_uri = $request->requestVar('redirect_uri');

        $this->getResponse()->setStatusCode(401);
        if (!$code) {
            Logger::debugLog('Bad request, missing code', __CLASS__, __FUNCTION__);
            return json_encode([self::ERROR_TAG => 'Bad request, missing code']);
        } else if (!$redirect_uri) {
            Logger::debugLog('Bad request, missing redirect_uri', __CLASS__, __FUNCTION__);
            return json_encode([self::ERROR_TAG => 'Bad request, missing redirect_uri']);
        } else if ($request->getHeader('Content-Type') != 'application/x-www-form-urlencoded') {
            Logger::debugLog('Incorrect Content-Type (use application/x-www-form-urlencoded)', __CLASS__, __FUNCTION__);
            return json_encode([self::ERROR_TAG => 'Incorrect Content-Type (use application/x-www-form-urlencoded)']);
        }
        $this->getResponse()->setStatusCode(200);

        $client = new Client(['base_uri' => $this->conextURL]);
        try {
            $conextResponse = $client->post('token',
                [
                    'query' => [
                        'grant_type' => 'authorization_code',
                        'client_id' => $client_id,
                        'client_secret' => $client_secret,
                        'redirect_uri' => $redirect_uri,
                        'code' => $code,
                    ],
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ]
                ]
            );
        } catch (BadResponseException $exception) {
            $conextResponse = $exception->getResponse();
        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);
            return $e->getMessage();
        }
        $this->getResponse()->addHeader('Content-Type', 'application/json');

        if ($conextResponse->getStatusCode() == 200) {
            $conextBody = $conextResponse->getBody();
            $contextJSON = json_decode($conextBody, true);
            $conextAccesToken = $contextJSON['access_token'];
            $tokenInformation = $this->ensurePersonExistsAndGetApiInfo($conextAccesToken);
            if (is_array($tokenInformation)) {
                $this->getResponse()->setStatusCode(200);
                return json_encode([
                    'token' => $tokenInformation['token'],
                    'token_expires' => $tokenInformation['expires'] - time(),
                    'name' => $tokenInformation['name'],
                    'id' => $tokenInformation['id']
                ]);
            }

            $this->getResponse()->setStatusCode(401);
            if (is_string($tokenInformation)) {
                Logger::debugLog('Could not ensure ConextMember exists ' . $tokenInformation, __CLASS__, __FUNCTION__);
                return json_encode([self::ERROR_TAG => $tokenInformation]);
            }
            Logger::debugLog('Could not ensure ConextMember exists', __CLASS__, __FUNCTION__);
            return json_encode([self::ERROR_TAG => 'Could not ensure ConextMember exists']);
        }
        $this->getResponse()->setStatusCode($conextResponse->getStatusCode());
        return $conextResponse->getBody();
    }

    /**
     * @param $conextAccesToken
     * @return array|string|null
     * Method called after verifying the OpenID CallbackCode and Redirect URI and having received a Conext JWT AccessToken
     * This method retrieves the Conext member information and updates/creates a corresponding SilverStripe member and ApiToken for said Member
     * The Member is automatically placed in an Institute's Group if one can be found for the Conext 'schac_home_organization' and 'eduperson_affiliation'
     */
    private function ensurePersonExistsAndGetApiInfo($conextAccesToken) {
        $this->conextURL = Environment::getEnv('CONEXT_URL');
        $client = new Client(['base_uri' => $this->conextURL]);
        try {
            $conextUserInfoResponse = $client->get('userinfo',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $conextAccesToken
                    ]
                ]

            );
        } catch (BadResponseException $exception) {
            $conextUserInfoResponse = $exception->getResponse();
            Logger::debugLog('Bad response ' . $conextUserInfoResponse, __CLASS__, __FUNCTION__);
        } catch (Exception $e) {
            Logger::warnLog($e->getMessage(), __CLASS__, __FUNCTION__);
            return $e->getMessage();
        }

        if ($conextUserInfoResponse->getStatusCode() == 200) {
            $conextBodyStream = $conextUserInfoResponse->getBody();
            $conextMemberJSON = json_decode($conextBodyStream, true);
            Logger::debugLog($conextMemberJSON);
            $name = isset($conextMemberJSON['name']) ? $conextMemberJSON['name'] : null;
            $surname = isset($conextMemberJSON['family_name']) ? $conextMemberJSON['family_name'] : null;
            $firstName = isset($conextMemberJSON['given_name']) ? $conextMemberJSON['given_name'] : null;
            $email = isset($conextMemberJSON['email']) ? $conextMemberJSON['email'] : null;
            $organization = isset($conextMemberJSON['schac_home_organization']) ? $conextMemberJSON['schac_home_organization'] : null;
            $consortiumTeamIdentifier = isset($conextMemberJSON['edumember_is_member_of']) ? $conextMemberJSON['edumember_is_member_of'] : null;
            $conextRoles = isset($conextMemberJSON['eduperson_affiliation']) ? $conextMemberJSON['eduperson_affiliation'] : null;
            $uniqueConextCodeIdentifier = isset($conextMemberJSON['sub']) ? $conextMemberJSON['sub'] : null;
            if (!$uniqueConextCodeIdentifier) {
                return null;
            }

            $institute = Institute::get()->filter('ConextCode', $organization)->first();
            if (!($institute && $institute->exists())) {
                $err = 'Could not find institute ' . $organization;
                Logger::debugLog($err, __CLASS__, __FUNCTION__);
                return $err;
            }

            $person = Person::get()->filter('ConextCode', $uniqueConextCodeIdentifier)->first();

            if (!$person || !$person->Exists()) {
                $person = Person::get()->filter('Email', $email)->first();
            }

            if (!$person || !$person->Exists()) {
                if (!$name || !$email || !$organization || !$uniqueConextCodeIdentifier) {
                    return null;
                }
                $person = new Person();
                $person->Email = $email;
                $person->ConextCode = $uniqueConextCodeIdentifier;
                $person->Surname = $surname;
                $person->FirstName = $firstName;
            }


            if (!$person->ConextRoles && $conextRoles) {
                $person->ConextRoles = implode(',', $conextRoles);

                if (in_array('student', $conextRoles)) {
                    $person->Position = 'student';
                }
            }

            $groupsToPutPersonIn = [];
            $firstTimeLoggingIn = false;
            if (!$person->HasLoggedIn) {
                $firstTimeLoggingIn = true;
                $person->HasLoggedIn = true;

                $memberGroup = $institute->Groups()->filter(['Roles.Title' => Constants::TITLE_OF_MEMBER_ROLE])->first();
                if (!$memberGroup || !$memberGroup->exists()) {
                    Logger::debugLog('Could not find institute ' . $organization, __CLASS__, __FUNCTION__);
                    return $institute->Title . ' Does not have a group for the role of ' . Constants::TITLE_OF_MEMBER_ROLE;
                }

                $groupsToPutPersonIn[] = $memberGroup;

                if ($person->IsStaffOrEmployee) {
                    $staffGroup = $institute->Groups()->filter(['Roles.Title' => Constants::TITLE_OF_STAFF_ROLE])->first();
                    if (!$staffGroup || !$staffGroup->exists()) {
                        Logger::debugLog('Could not find institute ' . $organization, __CLASS__, __FUNCTION__);
                        return $institute->Title . ' Does not have a group for the role of ' . Constants::TITLE_OF_STAFF_ROLE;
                    }
                    $groupsToPutPersonIn[] = $staffGroup;
                }
            }

            if ($consortiumTeamIdentifier) {
                $consortium = Institute::get()->filter('Level', 'consortium')->filter('ConextTeamIdentifier', $consortiumTeamIdentifier)->first();
                if ($consortium && $consortium->exists()) {
                    $consortiumStaffGroup = $consortium->Groups()->filter(['Roles.Title' => Constants::TITLE_OF_STAFF_ROLE])->first();
                    if ($consortiumStaffGroup && $consortiumStaffGroup->exists() && !$person->Groups()->filter('ID', $consortiumStaffGroup->ID)->exists()) {
                        $groupsToPutPersonIn[] = $consortiumStaffGroup;
                    }
                }
            }

            if ($firstTimeLoggingIn) {
                foreach ($groupsToPutPersonIn as $groupToPutPersonIn) {
                    foreach ($groupToPutPersonIn->AutoAddedConsortiums() as $consortiumToAddPersonInto) {
                        $role = $groupToPutPersonIn->Roles()->first();

                        if ($role && $role->exists()) {
                            $memberGroupOfConsortium = $consortiumToAddPersonInto->Groups()->filter(['Roles.Title' => $role->Title])->first();
                            if ($memberGroupOfConsortium && $memberGroupOfConsortium->exists()) {
                                $groupsToPutPersonIn[] = $memberGroupOfConsortium;
                            }
                        }
                    }
                }
            }

            if (ApiMemberExtension::isTokenIsExpired($person)) {
                $generatedTokenInformation = ApiMemberExtension::refreshApiToken($person);
            } else {
                $generatedTokenInformation = ApiMemberExtension::getHashedTokenInformation($person);
            }
            try {
                $person->IsRemoved = false;
                $person->IsLoggingIn = true;
                $person->write();

                if (count($groupsToPutPersonIn)) {
                    foreach ($groupsToPutPersonIn as $group) {
                        $person->Groups()->Add($group);
                    }
                }
            } catch (Exception $exception) {
                Logger::warnLog('Could not create new member: ' . $exception->getMessage(), __CLASS__, __FUNCTION__);
                return 'Could not create new member';
            }

            Security::setCurrentUser($person);
            return [
                'name' => $person->getName(),
                'token' => $generatedTokenInformation['token'],
                'expires' => $generatedTokenInformation['expires'],
                'id' => DataObjectJsonApiEncoder::getJSONAPIID($person),
            ];
        }
        Logger::debugLog('Could not find member information', __CLASS__, __FUNCTION__);
        return 'Could not find member information';
    }
}