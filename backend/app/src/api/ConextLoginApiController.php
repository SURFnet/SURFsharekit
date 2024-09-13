<?php

namespace SurfSharekit\Api;

use DataObjectJsonApiEncoder;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Security;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\LogItem;
use SurfSharekit\Models\Person;
use const SurfSharekit\Models\AUTHENTICATION_LOG;

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
    const EDUID_CONEXTCODE = 'eduid.nl';

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
        // Disable SRAM/Conext login if the variable "DISABLE_LOGIN" is true
        if (Environment::getEnv('DISABLE_LOGIN') == "true") {
            LogItem::debugLog('Login temporarily disabled', __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
            return json_encode([self::ERROR_TAG => 'Login temporarily disabled']);
        } else if (!$code) {
            LogItem::debugLog('Bad request, missing code', __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
            return json_encode([self::ERROR_TAG => 'Bad request, missing code']);
        } else if (!$redirect_uri) {
            LogItem::debugLog('Bad request, missing redirect_uri', __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
            return json_encode([self::ERROR_TAG => 'Bad request, missing redirect_uri']);
        } else if ($request->getHeader('Content-Type') != 'application/x-www-form-urlencoded') {
            LogItem::debugLog('Incorrect Content-Type (use application/x-www-form-urlencoded)', __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
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
                LogItem::debugLog('Could not ensure ConextMember exists ' . $tokenInformation, __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                return json_encode([self::ERROR_TAG => $tokenInformation]);
            }
            LogItem::debugLog('Could not ensure ConextMember exists', __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
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
            LogItem::debugLog('Bad response ' . $conextUserInfoResponse, __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
        } catch (Exception $e) {
            LogItem::warnLog($e->getMessage(), __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
            return $e->getMessage();
        }

        if ($conextUserInfoResponse->getStatusCode() == 200) {
            $conextBodyStream = $conextUserInfoResponse->getBody();
            $conextMemberJSON = json_decode($conextBodyStream, true);
            LogItem::debugLog($conextMemberJSON, __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
            $name = isset($conextMemberJSON['name']) ? $conextMemberJSON['name'] : null;
            $surname = isset($conextMemberJSON['family_name']) ? $conextMemberJSON['family_name'] : null;
            $firstName = isset($conextMemberJSON['given_name']) ? $conextMemberJSON['given_name'] : null;
            $email = isset($conextMemberJSON['email']) ? $conextMemberJSON['email'] : null;
            $organization = isset($conextMemberJSON['schac_home_organization']) ? $conextMemberJSON['schac_home_organization'] : null;
            $teamIdentifiers = isset($conextMemberJSON['edumember_is_member_of']) ? $conextMemberJSON['edumember_is_member_of'] : null;
            $conextRoles = isset($conextMemberJSON['eduperson_affiliation']) ? $conextMemberJSON['eduperson_affiliation'] : null;
            $uniqueConextCodeIdentifier = isset($conextMemberJSON['sub']) ? $conextMemberJSON['sub'] : null;

            if (!$uniqueConextCodeIdentifier) {
                return null;
            }

            LogItem::debugLog([
                '-----------------------------------------------',
                "name:" . json_encode($name),
                "surname:" . json_encode($surname),
                "firstName:" . json_encode($firstName),
                "email:" . json_encode($email),
                "organization:" . json_encode($organization),
                "teamIdentifiers:" . json_encode($teamIdentifiers),
                "conextRoles:" . json_encode($conextRoles),
                "uniqueConextCodeIdentifier:" . json_encode($uniqueConextCodeIdentifier),
                '-----------------------------------------------',
            ], __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);

            /**
             * @var $institute Institute
             */
            $institute = Institute::get()->filter('ConextCode', $organization)->first();
            if ($organization != static::EDUID_CONEXTCODE && !($institute && $institute->exists())) {
                $err = 'Could not find institute ' . $organization;
                LogItem::debugLog($err, __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
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
            }

            if ($organization != static::EDUID_CONEXTCODE && $firstTimeLoggingIn) {
                $memberGroup = $institute->Groups()->filter(['Roles.Title' => RoleConstant::MEMBER])->first();
                if (!$memberGroup || !$memberGroup->exists()) {
                    LogItem::debugLog('Could not find institute ' . $organization, __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                    return $institute->Title . ' Does not have a group for the role of ' . RoleConstant::MEMBER;
                }

                LogItem::debugLog("Institute $institute->Title", __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                LogItem::debugLog("Adding to $memberGroup->Title", __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);

                $groupsToPutPersonIn[] = $memberGroup;

                if ($person->IsStaffOrEmployee) {
                    $staffGroup = $institute->Groups()->filter(['Roles.Title' => RoleConstant::STAFF])->first();
                    if (!$staffGroup || !$staffGroup->exists()) {
                        LogItem::debugLog('Could not find institute ' . $organization, __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                        return $institute->Title . ' Does not have a group for the role of ' . RoleConstant::STAFF;
                    }
                    LogItem::debugLog("Adding to $staffGroup->Title", __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                    $groupsToPutPersonIn[] = $staffGroup;
                }
            }

            if ($teamIdentifiers) {
                foreach ($teamIdentifiers as $teamIdentifier) {
                    $institute = Institute::get()->filter('ConextTeamIdentifier:PartialMatch', $teamIdentifier)->first();
                    if ($institute && $institute->exists()) {
                        $instituteStaffGroup = $institute->Groups()->filter(['Roles.Title' => RoleConstant::STAFF])->first();
                        if ($instituteStaffGroup && $instituteStaffGroup->exists() && !$person->Groups()->filter('ID', $instituteStaffGroup->ID)->exists()) {
                            $groupsToPutPersonIn[] = $instituteStaffGroup;
                            LogItem::debugLog("Adding to $instituteStaffGroup->Title", __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                        }
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
                if ($firstTimeLoggingIn && (!count($groupsToPutPersonIn) && !$person->Groups()->count())) {
                    LogItem::warnLog('New account without any groups', __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                    return 'Could not create new member';
                }
                $person->IsRemoved = false;
                $person->IsLoggingIn = true;
                $person->write();

                if (count($groupsToPutPersonIn)) {
                    foreach ($groupsToPutPersonIn as $group) {
                        $group->Members()->add($person);
                    }
                }
            } catch (Exception $exception) {
                LogItem::warnLog('Could not create new member: ' . $exception->getMessage(), __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                return 'Could not create new member';
            }

            if (!$person->Groups()->count()) {
                LogItem::warnLog('member not connected to any groups', __CLASS__, __FUNCTION__,AUTHENTICATION_LOG);
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
        LogItem::debugLog('Could not find member information', __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
        return 'Could not find member information';
    }
}