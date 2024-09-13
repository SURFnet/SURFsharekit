<?php

namespace SurfSharekit\api;

use DataObjectJsonApiEncoder;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Jumbojett\OpenIDConnectClient;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\LogItem;
use SurfSharekit\Models\Person;
use SurfSharekit\SRAM\SRAMClient;
use const SurfSharekit\Models\AUTHENTICATION_LOG;

/**
 * Class SramLoginApiController
 * @package SurfSharekit\Api
 * A class used as the entryPoint to login with a context OpenID callback code and redirect_uri
 */
class SramLoginApiController extends CORSController {

    /**
     * @config
     * @var string $conextURL
     * The base url of conext to connect to for member information and code validation
     */
    private $conextURL = null;

    const ERROR_TAG = 'error';
    const EDUID_SRAMCODE = 'eduid.nl';

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
        $this->conextURL = Environment::getEnv('SRAM_URL');
        $client_id = Environment::getEnv('SRAM_CLIENT_ID');
        $client_secret = Environment::getEnv('SRAM_CLIENT_SECRET');

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
        } else if ($request->getHeader('Content-Type') !== 'application/x-www-form-urlencoded') {
            LogItem::debugLog('Incorrect Content-Type (use application/x-www-form-urlencoded)', __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
            return json_encode([self::ERROR_TAG => 'Incorrect Content-Type (use application/x-www-form-urlencoded)']);
        }
        $this->getResponse()->setStatusCode(200);

        $oidc = new OpenIDConnectClient($this->conextURL, $client_id, $client_secret);
        $oidc->providerConfigParam(['token_endpoint' => $this->conextURL . '/OIDC/token']);
        $oidc->setProviderURL($this->conextURL);
        $oidc->setRedirectURL($redirect_uri);
        $oidc->addScope('voperson_external_affiliation');
        $_REQUEST['state'] = false; // TODO: Check what this is

        try {
            $res = $oidc->authenticate();
        } catch (Exception $exception) {
            $this->getResponse()->setStatusCode(400);
            return json_encode(['error' => 'Authentication invalid']);
        }

        if ($res) {
            $tokenInformation = $this->ensurePersonExistsAndGetApiInfo($oidc);
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
        $this->getResponse()->setStatusCode(400);
        return json_encode(['error' => 'Authentication invalid']);
    }

    /**
     * @param $conextAccesToken
     * @return array|string|null
     * Method called after verifying the OpenID CallbackCode and Redirect URI and having received a Conext JWT AccessToken
     * This method retrieves the Conext member information and updates/creates a corresponding SilverStripe member and ApiToken for said Member
     * The Member is automatically placed in an Institute's Group if one can be found for the Conext 'schac_home_organization' and 'eduperson_affiliation'
     */
    private function ensurePersonExistsAndGetApiInfo(OpenIDConnectClient $oidc) {
        try {
            $userInfo = $oidc->requestUserInfo();
        } catch (Exception $e) {
            LogItem::warnLog($e->getMessage(), __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
            return $e->getMessage();
        }

        if (!empty($userInfo)) {
            LogItem::debugLog(var_export($userInfo, true), __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
            $name = $userInfo->name;
            $surname = $userInfo->family_name;
            $firstName = $userInfo->given_name;
            $email = $userInfo->email;
            $organization = !empty($userInfo->schac_home_organization) ? $userInfo->schac_home_organization[0] : null;
            $externalAffiliation = !empty($userInfo->voperson_external_affiliation) ? $userInfo->voperson_external_affiliation[0] : null;
            $teamIdentifiers = $userInfo->eduperson_entitlement;
            $scopedAffiliation = $userInfo->eduperson_scoped_affiliation;
            $sub = $userInfo->sub;

            if (!$sub) {
                return null;
            }

            LogItem::debugLog([
                '-----------------------------------------------',
                "name:" . json_encode($name),
                "surname:" . json_encode($surname),
                "firstName:" . json_encode($firstName),
                "email:" . json_encode($email),
                "organization:" . json_encode($organization),
                "externalAffiliation:" . json_encode($externalAffiliation),
                "teamIdentifiers:" . json_encode($teamIdentifiers),
                "scopedAffiliation:" . json_encode($scopedAffiliation),
                "sub:" . json_encode($sub),
                '-----------------------------------------------',
            ], __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);


            $organizations = self::getOrganisationsFromEntitlements($userInfo->eduperson_entitlement ?? []);
            $conextCode = self::getConextCodeFromExternalAffilliation($externalAffiliation);
            $organizationsWithCooperations = Arr::where($organizations, function ($organization) {
                return count($organization['cooperations']) > 0;
            });

            /** @var $institute Institute */
            $institute = Institute::get()->filter('ConextCode', $conextCode)->first();

            $person = Person::get()->filter('SramCode', $sub)->first();

            if (!$person || !$person->Exists()) {
                $person = Person::get()->filter('Email', $email)->first();
            }

            // if institute does not exist or license is not active
            if ($conextCode == static::EDUID_SRAMCODE || $institute == null || $institute->exists() == false || $institute->LicenseActive == false) {
                // check if user has consortia that have existing institute
                if (Institute::get()->filter(['SRAMCode' => array_keys($organizations), 'LicenseActive' => true])->count() == 0) {

                    // check if person exists, if not we throw error else we just continue logging in
                    if (!$person || !$person->exists()) {
                        // also no institute found by organization name of consortia that have an active license
                        $organizationsString = implode(', ', array_keys($organizations));
                        $err = "Institute $organization not found or not active and none of the given cooperation organizations ($organizationsString) are found or active ";
                        LogItem::debugLog($err, __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                        return $err;
                    }
                }
            }

            if (!$person || !$person->Exists()) {
                if (!$name || !$email || !$conextCode || !$sub) {
                    return null;
                }
                $person = new Person();
                $person->Email = $email;
                $person->SramCode = $sub;
                $person->Surname = $surname;
                $person->FirstName = $firstName;
            }

            $person->HasLoggedIn = true;
            $function = self::getFunctionFromExternalAffiliation($externalAffiliation);
            if (!$person->ConextRoles && $externalAffiliation) {
                $person->ConextRoles = $function;

                if ($function == 'student') {
                    $person->Position = 'student';
                }
            }

            if ($conextCode != static::EDUID_SRAMCODE) {
                // sync organization institute groups
                $this->syncWithInstitute($institute, $person);
            }

            // create consortium from SRAM CO's if not exists and add person to given groups
            try {
                $this->syncWithConsortia($organizationsWithCooperations, $person);
            } catch (Exception $exception) {
                LogItem::warnLog($exception->getMessage(), __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                return $exception->getMessage();
            }

            if (ApiMemberExtension::isTokenIsExpired($person)) {
                $generatedTokenInformation = ApiMemberExtension::refreshApiToken($person);
            } else {
                $generatedTokenInformation = ApiMemberExtension::getHashedTokenInformation($person);
            }
            try {
                if (!$person->Groups()->count()) {
                    LogItem::warnLog('New account without any groups', __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
                    return 'Could not create new member';
                }
                $person->IsRemoved = false;
                $person->IsLoggingIn = true;
                $person->write();
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

    private function syncWithInstitute(Institute $institute, Person $person) {
        if ($institute->LicenseActive) {
            $roles = [
                RoleConstant::MEMBER
            ];

            if ($person->IsStaffOrEmployee) {
                $roles[] = RoleConstant::STAFF;
            }

            $institute->syncPersonGroups($person, $roles, [
                RoleConstant::MEMBER,
                RoleConstant::STAFF
            ]); // only check MEMBER and STAFF
        } else {
            // remove all groups results in remove from institute
            $institute->syncPersonGroups($person, []);
        }
    }

    private function syncWithConsortia(array $organizations, $person) {
        $processedConsortia = [];

        if (!empty($organizations)) {
            foreach ($organizations as $organization => $organizationProps) {
                foreach ($organizationProps['cooperations'] as $cooperation) {
                    $urn = $organizationProps['name'] . ":" . $cooperation['name'];
                    $consortiumInstitute = Institute::get()->filter(['SRAMCode' => $urn, 'Level' => 'consortium'])->first();
                    if (!$consortiumInstitute || !$consortiumInstitute->exists() ) {

                        if (Institute::get()->filter(['SRAMCode' => $organization, 'LicenseActive' => true])->count() == 0) {
                            continue;
                        }

                        if (null !== $title = $this->getConsortiumTitle($urn)) {
                            $consortiumInstitute = Institute::create([
                                'Title' => $title,
                                'SRAMCode' => $urn,
                                'Level' => 'consortium'
                            ]);

                            $consortiumInstitute->write();
                        } else {
                            throw new Exception("Could not create consortia, getConsortiumTitle returns null");
                        }
                    }

                    if ($consortiumInstitute == null) {
                        continue;
                    }

                    $processedConsortia[$consortiumInstitute->ID] = [
                        RoleConstant::MEMBER,
                        RoleConstant::STAFF
                    ];

                    $siteAdminGroup = array_filter($cooperation['groups'], fn($group) => Str::startsWith($group['name'], 'ssk-') && Str::endsWith($group['name'], '-siteadmin'));
                    if (count($siteAdminGroup)) {
                        $processedConsortia[$consortiumInstitute->ID][] = RoleConstant::SITEADMIN;
                    }

                    $supporterGroup = array_filter($cooperation['groups'], fn($group) => Str::startsWith($group['name'], 'ssk-') && Str::endsWith($group['name'], '-supporter'));
                    if (count($supporterGroup)) {
                        $processedConsortia[$consortiumInstitute->ID][] = RoleConstant::SUPPORTER;
                    }
                }
            }
        }

        $currentConsortia = Institute::get()
            ->filter('Level', 'consortium')
            ->filterAny([
                'Persons.ID' => $person->ID,
                'ID' => count(array_keys($processedConsortia)) > 0 ? array_keys($processedConsortia) : [-1]
            ]);

        foreach ($currentConsortia as $institute) {
            /** @var Institute $institute */
            if (array_key_exists($institute->ID, $processedConsortia)) {
                $institute->syncPersonGroups($person, $processedConsortia[$institute->ID], [
                    RoleConstant::SUPPORTER,
                    RoleConstant::MEMBER,
                    RoleConstant::STAFF,
                    RoleConstant::SITEADMIN
                ]);
            } else {
                // remove all groups results in remove from institute
                $institute->syncPersonGroups($person, []);
            }
        }
    }

    private static function getOrganisationsFromEntitlements(array $entitlements) {
        $organizations = [];

        foreach ($entitlements as $entitlement) {
            $splitEntitlement = explode(':group:', $entitlement);
            if (count($splitEntitlement) == 2) {
                $identifiers = explode(':', $splitEntitlement[1]); // organization - cooperation - group
                $organization = strtok($splitEntitlement[1], ':');

                // save organization
                if (!isset($organizations[$organization])) {
                    $organizations[$organization] = [
                        "namespace" => $splitEntitlement[0], // namespace
                        "name" => $identifiers[0],
                        "cooperations" => []
                    ];
                }

                // save cooperation
                if (isset($identifiers[1])) {
                    if (!isset($organizations[$organization]['cooperations'][$identifiers[1]])) {
                        $organizations[$organization]['cooperations'][$identifiers[1]] = [
                            "name" => $identifiers[1],
                            "groups" => []
                        ];
                    }
                }

                // save groups
                if (isset($identifiers[2])) {
                    if (!isset($organizations[$organization]['cooperations'][$identifiers[1]]['groups'][$identifiers[2]])) {
                        $organizations[$organization]['cooperations'][$identifiers[1]]['groups'][$identifiers[2]] = [
                            "name" => $identifiers[2]
                        ];
                    }
                }
            }
        }

        return $organizations;
    }

    private function getConsortiumTitle(string $urn): ?string {

        try {

            $body = SRAMClient::getGroups();
            if (isset($body['Resources']) && is_array($body['Resources'])) {
                $foundResource = Arr::first($body['Resources'], function ($resource) use ($urn) {
                    return $resource['urn:mace:surf.nl:sram:scim:extension:Group']['urn'] === $urn;
                });

                if ($foundResource !== null) {
                    return $foundResource['displayName'];
                }
            }
        } catch (Exception $exception) {
            LogItem::warnLog('Couldnt get consortia title: ' . $exception->getMessage(), __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
            return null;
        }

        LogItem::warnLog('Couldnt get consortia title: consortia not found', __CLASS__, __FUNCTION__, AUTHENTICATION_LOG);
        return null;
    }


    private static function getConextCodeFromExternalAffilliation($externalAffiliation) {
        $splitByAtSign = explode('@', $externalAffiliation);
        if(count($splitByAtSign) > 1){
            return $splitByAtSign[count($splitByAtSign)-1];
        }
        return $externalAffiliation;
    }
    private static function getFunctionFromExternalAffiliation($externalAffiliation) {
        $splitByAtSign = explode('@', $externalAffiliation);
        if(count($splitByAtSign) > 1){
            return $splitByAtSign[0];
        }
        return $externalAffiliation;
    }
}