<?php

namespace SurfSharekit\Models;

use League\Flysystem\Exception;
use Ramsey\Uuid\Uuid;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\MemberHelper;
use UuidExtension;

class Person extends Member {

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    public static $overwriteCanView = false;
    public $SkipEmail = false;

    private static $table_name = 'SurfSharekit_Person';

    private static $db = [
        "LinkedInUrl" => 'Varchar(255)',
        "TwitterUrl" => 'Varchar(255)',
        "ResearchGateUrl" => 'Varchar(255)',

        "FormOfAddress" => 'Varchar(255)',
        "AcademicTitle" => 'Varchar(255)',
        "Initials" => 'Varchar(255)',

        "SecondaryEmail" => 'Varchar(255)',
        "City" => 'Varchar(255)',

        "PersistentIdentifier" => 'Varchar(255)', //used for DAI
        "ORCID" => 'Varchar(255)',
        "ISNI" => 'Varchar(255)',
        "HogeschoolID" => 'Varchar(255)',
        "Phone" => 'Varchar(255)',
        "Position" => 'Varchar(255)',

        "HasLoggedIn" => 'Boolean(0)',
        "HasFinishedOnboarding" => 'Boolean(0)',
        "IsRemoved" => 'Boolean(0)'
    ];

    private static $has_one = [
        'PersonImage' => PersonImage::class
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $currentController = Controller::curr();
        if (!$currentController instanceof SecurityAdmin) {
            $fields->removeByName('ApiToken');
            $fields->removeByName('ApiTokenAcc');
            $fields->removeByName('ApiTokenExpires');
            $fields->removeByName('ConextCode');
            $fields->removeByName('ContextRoles');
            $fields->removeByName('HasLoggedIn');
            $fields->removeByName('PersistentIdentifier');
            $fields->removeByName('ORCID');
            $fields->removeByName('ISNI');
            $fields->removeByName('HogeschoolID');
            $fields->removeByName('Phone');
            $fields->removeByName('City');
            $fields->removeByName('PersonID');
            $fields->removeByName('Password');
            $fields->removeByName('Permissions');
            $fields->removeByName('Locale');
            $fields->removeByName('FailedLoginCount');

            $fields->changeFieldOrder(['Uuid', 'FirstName', 'SurnamePrefix', 'Surname', 'LinkedInUrl', 'TwitterUrl', 'ResearchGateUrl']);
        }
        $skipEmailField = CheckboxField::create('SkipEmail', 'SkipEmail');
        $fields->insertAfter('Email', $skipEmailField);
        return $fields;
    }

    function getTitle() {
        return $this->getFullName();
    }

    function getPermissionObjectName() {
        return 'Member';
    }

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function getIsEmailEditable() {
        return $this->ConextCode === null || $this->ConextCode === '';
    }

    public function onBeforeWrite() {
        if (!$this->isInDB()) { //Require email when creating a new person
            if (!static::isValidEmail($this->Email) && !$this->SkipEmail) {
                throw new Exception("$this->Email is not a valid email");
            }
        }
        if (!$this->isInDB()) {
            $this->PersonID = Security::getCurrentUser() ? Security::getCurrentUser()->ID : null;
        }
        if (!$this->IsLoggingIn && $this->isChanged('IsRemoved') && !$this->canDelete(Security::getCurrentUser())) {
            throw new Exception("No permission to delete this person");
        }
        parent::onBeforeWrite();
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
        SearchObject::updateForPerson($this);

        // If this is a new person -> PersonSummary::updateFor is executed in setBaseInstitute AFTER the person
        // has been added to the default member group
        if ($this->isInDB()) {
            PersonSummary::updateFor($this);
        }

        // Remove all cache if current person is changed
        if ($this->isChanged()) {
            if ($this === Security::getCurrentUser()) {
                ScopeCache::removeAllCachedPermissions();
                ScopeCache::removeAllCachedViewables();
                ScopeCache::removeAllCachedDataLists();
            } else {
                ScopeCache::removeCachedViewable(Person::class);
                ScopeCache::removeCachedDataList(Person::class);
            }
        }

//        if ($this->isChanged('ID')) { //implied onAfterCreate
//            if ($this->PersonID != 0) { //created by someone
//                $this->sendCreationEmail();
//            }
//        }

        // MB , update disabled beacuse old names must be kept as value
        // $this->updateRelevantRepoItems();
    }

    /**
     * @param $instituteUUID
     * This method is called when creating a new person using the API
     */
    function setBaseInstitute($instituteUUID) {
        if (!UUID::isValid($instituteUUID)) {
            throw new Exception('Institute is not a valid institute ID');
        }
        $institute = UuidExtension::getByUuid(Institute::class, $instituteUUID);
        if (!$institute || !$institute->exists()) {
            throw new Exception("Institute $instituteUUID is not an existing Institute");
        }
        if ($institute->InstituteID) {
            throw new Exception("Institute is not a root institute");
        }

        $defaultMemberGroupOfInstitute = $institute->Groups()->filter(['Roles.Title' => Constants::TITLE_OF_MEMBER_ROLE])->first();
        if (!$defaultMemberGroupOfInstitute || !$defaultMemberGroupOfInstitute->exists()) {
            throw new Exception("Institute doesn't have a default member group");
        }
        Logger::debugLog("Add " . $this->Uuid . " to default group : $instituteUUID : " . $defaultMemberGroupOfInstitute->getTitle() . "\n");
        if (!$this->isInDB()) {
            $this->write();
            $this->Groups()->Add($defaultMemberGroupOfInstitute);
            PersonSummary::updateFor($this);
        }
    }

    function setSkipEmail($value) {
        $this->SkipEmail = $value;
    }

    /**
     * @param $instituteUUID
     * This method is called when creating a new person using the API
     */
    function setBaseDiscipline($instituteUUID) {
        if (!$this->ConextRoles) {
            throw new Exception("No conext roles set for this person");
        }
        if (!$this->IsStudent) {
            throw new Exception("Person is not a student");
        }
        if (!UUID::isValid($instituteUUID)) {
            throw new Exception('Discipline is not a valid institute ID');
        }
        $discipline = UuidExtension::getByUuid(Institute::class, $instituteUUID);
        if (!$discipline || !$discipline->exists()) {
            throw new Exception("Institute $instituteUUID is not an existing Institute");
        }
        if ($discipline->Level != 'discipline') {
            throw new Exception("Institute is not a discipline");
        }
        $studentGroupOfDiscipline = $discipline->Groups()->filter(['Roles.Title' => Constants::TITLE_OF_STUDENT_ROLE])->first();
        if (!$studentGroupOfDiscipline || !$studentGroupOfDiscipline->exists()) {
            throw new Exception("Discipline doesn't have a student group");
        }
        if ($this->HasFinishedOnboarding || ($this->HasFinishedOnboarding && !$this->isChanged('HasFinishedOnboarding'))) {
            throw new Exception("Discipline can only be set during onboarding");
        }

        $this->write();
        $this->Groups()->Add($studentGroupOfDiscipline); //Add to discipline student group
    }

    function getLoggedInUserPermissions() {
        $loggedInMember = Security::getCurrentUser();
        return [
            'canView' => $this->canView($loggedInMember),
            'canEdit' => $this->canEdit($loggedInMember),
            'canDelete' => $this->canDelete($loggedInMember),
        ];
    }

    function getLoggedInUserCanViewPermission() {
        $loggedInMember = Security::getCurrentUser();
        return [
            'canView' => $this->canView($loggedInMember)
        ];
    }

    public function canView($member = null) {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isDefaultAdmin()) {
            return true;
        }

        if (static::$overwriteCanView) {
            return true;
        }

        if ($member->isWorksAdmin()) {
            return true;
        }

        return parent::canView($member);
    }

    private static function isValidEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    public function getInstituteTitles() {
        $institutes = [];
        foreach ($this->Groups() as $group) {
            $institutes[] = $group->Institute();
        }
        $titles = [];
        foreach ($institutes as $institute) {
            foreach ($institutes as $otherInstitute) {
                if (InstituteScoper::getScopeLevel($institute->ID, $otherInstitute->ID) == InstituteScoper::HIGHER_LEVEL) {
                    break;
                }
                if (!in_array($institute->Title, $titles)) {
                    $titles[] = $institute->Title;
                }
            }
        }
        return $titles;
    }

    public function getGroupCount() {
        return $this->Groups()->count();
    }

    public function getFamilyName() {
        $namesArray = [];
        $lastName = $this->getLastName();
        if (!empty($lastName)) {
            $namesArray[] = $lastName;
        }
        $surnamePrefix = $this->SurnamePrefix;
        if (!empty($surnamePrefix)) {
            $namesArray[] = $surnamePrefix;
        }
        if (count($namesArray)) {
            return implode(',', $namesArray);
        }
        return null;
    }

    public function getIsStudent() {
        return stripos($this->ConextRoles, 'student') !== false;
    }

    public function getIsStaffOrEmployee() {
        return stripos($this->ConextRoles, 'staff') !== false ||
            stripos($this->ConextRoles, 'employee') !== false ||
            !$this->getIsStudent();
    }

    public function getFullName() {
        return MemberHelper::getPersonFullName($this);
    }

    public function getGroupTitles() {
        $titles = [];
        foreach ($this->Groups() as $group) {
            $titles[] = $group->Title;
        }
        return $titles;
    }

    public function getName() {
        $nameValues = [];
        if (!empty($firstName = trim($this->FirstName))) {
            $nameValues[] = $firstName;
        }
        if (!empty($surnamePrefix = trim($this->SurnamePrefix))) {
            $nameValues[] = $surnamePrefix;
        }
        if (!empty($surname = trim($this->Surname))) {
            $nameValues[] = $surname;
        }

        return implode(' ', $nameValues);
    }

    protected function onBeforeDelete() {
        $this->validatePredelete();
        parent::onBeforeDelete();
    }

    public function onAfterDelete() {
        parent::onAfterDelete();
        SearchObject::get()->filter('PersonID', $this->ID)->removeAll();
        PersonSummary::get()->filter('PersonID', $this->ID)->removeAll();
    }

    private function validatePredelete() {
        if (RepoItem::get()->filter('OwnerID', $this->ID)->filter('IsRemoved', 0)->exists()) {
            throw new ValidationException("Person has Repository Items, remove items or associate items with different person before deleting");
        }
        foreach (RepoItemMetaFieldValue::get()->filter('PersonID', $this->ID)->filter('IsRemoved', 0) as $mentionedInValue) {
            $mentionedInAnswer = $mentionedInValue->RepoItemMetaField();
            if (!$mentionedInAnswer || !$mentionedInAnswer->exists()) {
                continue;
            }
            $mentionedInRepoItem = $mentionedInAnswer->RepoItem();
            if (!$mentionedInRepoItem || !$mentionedInRepoItem->exists() || $mentionedInRepoItem->IsRemoved) {
                continue;
            }
            if ($mentionedInRepoItem->RepoType != 'RepoItemPerson') {
                if ($mentionedInRepoItem->IsRemoved) {
                    continue;
                }
                $infoSummary = $mentionedInRepoItem->Uuid . '(' . $mentionedInRepoItem->Title . ' by ' . $mentionedInRepoItem->Owner()->Title . ')';
                throw new ValidationException("Person is mentioned Repository Items (non-author), remove answers referring to person before deleting. $infoSummary");
            }
            $parentRepoItem = $mentionedInRepoItem->getActiveParent();
            if (!$parentRepoItem || !$parentRepoItem->exists() || $parentRepoItem->IsRemoved) {
                continue;
            }
            $infoSummary = $parentRepoItem->Uuid . '(' . $parentRepoItem->Title . ' by ' . $parentRepoItem->Owner()->Title . ')';
            throw new ValidationException("Person is mentioned in Repository Items, remove answers referring to person before deleting. $infoSummary");
        }
        if (DefaultMetaFieldOptionPart::get()->filter('PersonID', $this->ID)->exists()) {
            throw new ValidationException("Person is mentioned as a default option, remove this reference before deleting");
        }
        if ($this->Groups()->exists()) {
            throw new ValidationException("Person is part of a group, remove this link before deleting");
        }
    }
}
