<?php

namespace SurfSharekit\Api;

use DateTime;
use Exception;
use Ramsey\Uuid\Uuid;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Member;
use SilverStripe\View\Requirements;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Constants;

/**
 * Class ApiMemberExtension
 * @package SurfSharekit\Api
 * Extension to the SilverStripe Member class to add Api Features
 */
class ApiMemberExtension extends DataExtension {
    const TOKEN_EXPIRATION = '22 hours';

    private static $db = [
        'ApiToken' => 'Varchar(255)',
        'ApiTokenAcc' => 'Varchar(255)', // used for acceptance environment
        'ApiTokenExpires' => 'Datetime'
    ];


    private static $indexes = [
        'ApiToken' => true,
        'ApiTokenAcc' => true,
        'Surname' => true
    ];

    var $institute = null;

    /**
     * @param $member
     * @return bool
     * @throws Exception
     * Method to check whether or not the token of the member has expired
     */
    public static function isTokenIsExpired($member) {
        //Api tokens for API users cannot expire
        if (ApiMemberExtension::hasApiUserRole($member)) {
            return false;
        }

        if ($expirationDate = $member->ApiTokenExpires) {
            $now = new DateTime('now');
            try {
                $apiTokenExpires = new DateTime($expirationDate);
                if ($now->getTimestamp() >= $apiTokenExpires->getTimestamp()) {
                    return true;
                }
                return false;
            } catch (Exception $e) {
                return true;
            }
        }
        return true;
    }

    public static function hasApiUserRole($member) {
        foreach ($member->Groups() as $group) {
            foreach ($group->Roles() as $role) {
                if ($role->Title === RoleConstant::APIUSER) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param Member $member
     * @return array
     * @throws Exception
     * Method to extend generate a new ApiToken and set the expirationDate for this Member
     */
    public static function refreshApiToken(Member $member) {
        $tokenInfo = ApiMemberExtension::generateAccessTokenInformation();
        $newApiToken = hash('sha512', $tokenInfo['token']);
        if (Environment::getEnv('APPLICATION_ENVIRONMENT') == 'live') {
            $member->ApiToken = $newApiToken;
        } else {
            $member->ApiTokenAcc = $newApiToken;
        }

        $member->ApiTokenExpires = $tokenInfo['expires'];
        $member->write();
        return $tokenInfo;
    }

    public static function getHashedTokenInformation(Member $member) {
        $expirationdate = new DateTime($member->ApiTokenExpires);
        $apiToken = Environment::getEnv('APPLICATION_ENVIRONMENT') == 'live' ? $member->ApiToken : $member->ApiTokenAcc;
        return [
            'token' => $apiToken, //is actually the has of the apitokens
            'expires' => $expirationdate->getTimestamp()
        ];
    }

    /**
     * @return array
     * @throws Exception
     * Method used to generate a new Api Access Token to login with, also returns an expirationDate
     */
    public static function generateAccessTokenInformation() {
        $expires = new DateTime('now');
        $expires->modify('+ ' . self::TOKEN_EXPIRATION);

        $salt1 = "79236fe9";
        $salt2 = "390a4ab759d8";
        $tokenHash = hash('sha512', $salt1 . Uuid::uuid4()->toString() . $salt2);
        return ['token' => $tokenHash, 'expires' => $expires->getTimestamp()];
    } //caching the institute of a Member, so we only need to get it once during a call

    /**
     * @return |null
     * @throws Exception
     * Method to retrieve and cache the institute of the Member
     */
    public function getInstitute() {
        if (!$this->institute) {
            //member is new and needs to be added to a institute's student group
            $groupOfMember = $this->getOwner()->Groups()->first();
            if (!$groupOfMember) {
                throw new Exception("Member is not a student of an Institute");
            }
            $this->institute = $groupOfMember->Institute;
        }
        return $this->institute;
    }

    /**
     * @return |null
     * @throws Exception
     * Method to retrieve all scoping insitutes of member
     */
    public function getInstituteIdentifiers() {
        //member is new and needs to be added to a institute's student group
        $groups = $this->getOwner()->Groups();
        $ids = [];
        foreach ($groups as $group) {
            $ids[] = $group->InstituteID;
        }
        return array_unique($ids);
    }

    public function updateCMSFields(FieldList $fields) {
        parent::updateCMSFields($fields);
        $apiTokenField = $fields->dataFieldByName('ApiToken');
        $apiTokenField->setDescription('Use this button to generate a new API Authentication Bearer Token<br><div class="generate-code-button btn action" data-fieldname="ApiToken" style="color: white;background-color: #489E46">Generate ApiToken</div>');
        $apiTokenField = $fields->dataFieldByName('ApiTokenAcc');
        $apiTokenField->setDescription('API token for Acceptance environment<br><div class="generate-code-button btn action" data-fieldname="ApiTokenAcc" style="color: white;background-color: #489E46">Generate ApiToken</div>');
        Requirements::javascript('app/src/javascript/access-token-generator.js');
    }
}