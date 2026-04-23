<?php

namespace SurfSharekit\Models;

use Exception;
use Ramsey\Uuid\Uuid;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Services\Blueprint\BlueprintService;
use SilverStripe\Versioned\Versioned;
use SurfSharekit\Action\BlueprintCopyButton;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\DateHelper;
use SurfSharekit\Models\Helper\EmailHelper;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\MemberHelper;
use UuidExtension;

/**
 * @property String Uuid
 * @property String Initials
 * @property String ORCID
 * @property String InviteSentDate
 * @property String $HasLoggedIn
 * @property String ORCIDRegisterDate
 * @property String HasFinishedOnboarding
 * @property String OnboardingDate
 */
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
        "SocialMediaUrl" => 'Varchar(255)',
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
        "DisableEmailChange" => 'Boolean(0)',

        "GeneratedThroughBlueprint" => 'Boolean(0)',
        "GeneratedBy" => "Varchar(255)",

        "InviteSentDate" => "Datetime",
        "ORCIDRegisterDate" => "Datetime",
        "OnboardingDate" => "Datetime"
    ];

    private static $has_one = [
        'PersonImage' => PersonImage::class,
        'PersonConfig' => PersonConfig::class
    ];

    private static $has_many = [
        'Claims' => Claim::class,
        'Tasks' => Task::class
    ];

    private static $many_many = [
        'RootInstitutes' => Institute::class
    ];

    private const ACTION_ADD = 'ADD';

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->makeFieldReadonly(['GeneratedThroughBlueprint', 'GeneratedBy']);
        if ($this->GeneratedThroughBlueprint == false) {
            $fields->removeByName('GeneratedBy');
        }

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
            $fields->removeByName('PersonConfigID');
            $fields->removeByName('Password');
            $fields->removeByName('Permissions');
            $fields->removeByName('Locale');
            $fields->removeByName('FailedLoginCount');

            $fields->findTab('Root.Main')->Fields()->changeFieldOrder(['Uuid', 'FirstName', 'SurnamePrefix', 'Surname', 'LinkedInUrl', 'TwitterUrl', 'SocialMediaUrl', 'ResearchGateUrl']);
        }

        $blueprintService = BlueprintService::create();
        $personJson = $blueprintService->createBlueprintPreviewForDataobject($this);
        $fields->addFieldsToTab('Root.Blueprint', BlueprintCopyButton::create($personJson));

        $skipEmailField = CheckboxField::create('SkipEmail', 'SkipEmail');
        $fields->insertAfter('Email', $skipEmailField);
        $fields->insertAfter('SkipEmail', $fields->dataFieldByName('DisableEmailChange'));
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

    public function trySendInvitationMail() {
        Logger::debugLog("trySendInvitationMail for " . $this->Email . "\n");;
        if (!$this->canSendInvitationMail()) return false;

        // Try to find another user with the same mail and check its sent flag
        /** @var Person $person */
        $mail = $this->Email;
        foreach (Person::get()->filter('Email', $mail) as $person) {
            if (!$person->canSendInvitationMail()) {
                return false;
            }
        }

        EmailHelper::sendEmail([$this->Email], 'Email\\ActivateProfileInvitation', 'Profiel activeren SURFsharekit', []);

        $this->InviteSentDate = DBDatetime::now()->Rfc2822();
        $this->write();

        return true;
    }

    public function canSendInvitationMail() {
        Logger::debugLog("canSendInvitationMail for " . $this->Email . "\n");;
        // Check if the account is inactive
        if ($this->HasLoggedIn) return false;

        // Check if the person mail belongs to an institute
        $emailParts = explode('@', $this->Email);
        $emailDomain = count($emailParts) === 2 ? $emailParts[1] : '';
        if (!Institute::get()->find('ConextCode', $emailDomain)) {
            return false;
        }

        // Check if the previous mail is at least 2 years ago or never sent
        if (!$this->InviteSentDate) return true;

        /** @var DBDatetime $date */
        $date = $this->dbObject('InviteSentDate');
        $timestamp = $date->getTimestamp();
        $curTimestamp = DBDatetime::now()->getTimestamp();

        return ($curTimestamp - $timestamp > 365 * 24 * 60 * 60); // 365 days
    }

    public function onBeforeWrite() {
        if (!$this->isInDB()) { //Require email when creating a new person
            if (!static::isValidEmail($this->Email) && !$this->SkipEmail && !$this->DisableEmailChange) {
                throw new Exception("$this->Email is not a valid email");
            }

            if ($this->DisableEmailChange) {
                $this->Email = $this->original['Email'] ?? null;
            }
        }
        if (!$this->isInDB()) {
            $this->PersonID = Security::getCurrentUser() ? Security::getCurrentUser()->ID : null;
        }

        if (!$this->isInDB()) {
            $newPersonConfig = new PersonConfig();
            $newPersonConfig->write();
            $this->PersonConfigID = $newPersonConfig->ID;
        }

        if (!$this->IsLoggingIn && $this->isChanged('IsRemoved') && !$this->canDelete(Security::getCurrentUser())) {
            throw new Exception("No permission to delete this person");
        }

        // If the mail of an account gets changed, it's valid to send one again
        if (!$this->HasLoggedIn && isset($this->getChangedFields(true, DataObject::CHANGE_VALUE)['Email'])) {
            $this->InviteSentDate = null;
        }

        // Set onboarding date if onboarded
        if ($this->isChanged("HasFinishedOnboarding") && $this->HasFinishedOnboarding) {
            $this->OnboardingDate = DBDatetime::now();
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

            if ($this->DisableEmailChange && static::isValidEmail($this->Email)) {
                $this->Email = null;
                $this->write();
            }
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

        // Only do this on creation
        if ($this->isChanged('ID')) {
            foreach ($this->Groups() as $group) {
                $group->Members()->add($this);
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

        $defaultMemberGroupOfInstitute = $institute->Groups()->filter(['Roles.Title' => RoleConstant::MEMBER])->first();
        if (!$defaultMemberGroupOfInstitute || !$defaultMemberGroupOfInstitute->exists()) {
            throw new Exception("Institute doesn't have a default member group");
        }
        Logger::debugLog("Add " . $this->Uuid . " to default group : $instituteUUID : " . $defaultMemberGroupOfInstitute->getTitle() . "\n");
        if (!$this->isInDB()) {
            $this->write();
            $defaultMemberGroupOfInstitute->Members()->add($this);
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
    function setBaseDiscipline($instituteUUIDs) {
        if (!$this->ConextRoles) {
            throw new Exception("No conext roles set for this person");
        }

        foreach ($instituteUUIDs as $instituteUUID) {
            if (!UUID::isValid($instituteUUID)) {
                throw new Exception('Discipline is not a valid institute ID');
            }
            $discipline = UuidExtension::getByUuid(Institute::class, $instituteUUID);
            if (!$discipline || !$discipline->exists()) {
                throw new Exception("Institute $instituteUUID is not an existing Institute");
            }

            $role = $this->getIsStaffOrEmployee() ? RoleConstant::STAFF : RoleConstant::STUDENT;
            $groupOfDiscipline = $discipline->Groups()->filter(['Roles.Title' => $role])->first();
            if (!$groupOfDiscipline || !$groupOfDiscipline->exists()) {
                throw new Exception($discipline->Title . " doesn't have a " . $role . " group");
            }
            if ($this->HasFinishedOnboarding || ($this->HasFinishedOnboarding && !$this->isChanged('HasFinishedOnboarding'))) {
                throw new Exception("Discipline can only be set during onboarding");
            }

            $this->write();
            $groupOfDiscipline->Members()->add($this); //Add to discipline student group
        }
    }

    function getLoggedInUserPermissions() {
        $loggedInMember = Security::getCurrentUser();
        return [
            'canView' => $this->canView($loggedInMember),
            'canEdit' => $this->canEdit($loggedInMember),
            'canDelete' => $this->canDelete($loggedInMember),
            'canMerge' => $this->canMerge($loggedInMember),
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
        if ($member->isMainAdmin()) {
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

    public function canMerge($member = null) {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }
        return Permission::check("PERSON_MERGE_SAMELEVEL");
    }

    private static function isValidEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    public function getInstituteTitles() {
        $titles = [];
        foreach ($this->Groups() as $group) {
            $title = $group->Institute()->Title;
            if (!in_array($title, $titles)) {
                $titles[] = $title;
            }
        }
        return $titles;
    }

    public function getRootInstitutesSummary() {
        $rootInstitutes = [];
        foreach ($this->RootInstitutes() as $institute) {
            $rootInstitutes[] = [
                'id' => $institute->Uuid,
                'title' => $institute->Title
            ];
        }
        return $rootInstitutes;
    }

    public function getInstituteIDs() {
        $ids = [];
        foreach ($this->Groups() as $group) {
            $id = $group->Institute()->ID;
            if (!in_array($id, $ids)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    public function getGroupCount() {
        return $this->Groups()->count();
    }

    public function getRepoCount() {
        try {
            $repoItemSummaries = RepoItemSummary::get();

            // Apply filters using RepoItemSummaryJsonApiDescription
            $jsonApiDescription = new \RepoItemSummaryJsonApiDescription();
            $repoItemSummaries = $jsonApiDescription->applyGeneralFilter($repoItemSummaries);

            // Apply isRemoved filter
            $repoItemSummaries = $jsonApiDescription->applyFilter($repoItemSummaries, 'isRemoved', ['EQ' => 'false']);

            // Apply repoType filter
            $repoItemSummaries = $jsonApiDescription->applyFilter($repoItemSummaries, 'repoType', ['NEQ' => 'Project']);

            // Apply search filter with UUID
            $repoItemSummaries = $jsonApiDescription->applyFilter($repoItemSummaries, 'search', ['EQ' => $this->Uuid]);

            return $repoItemSummaries->count();
        } catch (\Exception $e) {
            \SurfSharekit\Models\Helper\Logger::errorLog("Error in getRepoCount: " . $e->getMessage());
            return 0;
        }

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

    public function getIsStudent(): bool {
        return stripos($this->ConextRoles ?? "", 'student') !== false;
    }

    public function getIsOnlyStudent(): bool {
        return $this->ConextRoles === 'student';
    }

    public function getIsStaffOrEmployee() {
        return stripos($this->ConextRoles ?? "", 'staff') !== false ||
            stripos($this->ConextRoles ?? "", 'employee') !== false ||
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

    public function getGroupLabelsNL() {
        $labelsNL = [];
        foreach ($this->Groups() as $group) {
            if ($group->RoleCode != 'Default Member') {
                $labelsNL[] = $group->Label_NL;
            }
        }
        return $labelsNL;
    }

    public function getGroupLabelsEN() {
        $labelsEN = [];
        foreach ($this->Groups() as $group) {
            if ($group->RoleCode != 'Default Member') {
                $labelsEN[] = $group->Label_EN;
            }
        }
        return $labelsEN;
    }

    public function getGroupTitlesWithoutMembers() {
        $titles = [];
        foreach ($this->Groups() as $group) {
            if ($group->RoleCode != 'Default Member') {
                $titles[] = $group->Title;
            }
        }
        return $titles;
    }

    public function getName() {
        $nameValues = [];
        if (!empty($firstName = trim($this->FirstName ?? ""))) {
            $nameValues[] = $firstName;
        }
        if (!empty($surnamePrefix = trim($this->SurnamePrefix ?? ""))) {
            $nameValues[] = $surnamePrefix;
        }
        if (!empty($surname = trim($this->Surname ?? ""))) {
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

    public static function getPositionOptions(): array {
        return [
            "role-lecturer",
            "teacher",
            "researcher",
            "student",
            "staff-employee",
            "associate-lecturer",
            "member-lectureship",
             "phd",
            "other",
        ];
    }

    function getLastEditorSummary() {
        // Only check for canView permission as the others are irrelevant in this case
        $lastEditor = $this->ModifiedBy();
        return [
            'id' => $lastEditor->Uuid,
            'name' => $lastEditor->Name,
            'permissions' => $lastEditor->LoggedInUserCanViewPermission,
            'lastEditedLocal' => $this->getLastEditedLocal()
        ];
    }

    function getCreatorSummary() {
        // Only check for canView permission as the others are irrelevant in this case
        $creator = $this->CreatedBy();
        return [
            'id' => $creator->Uuid,
            'name' => $creator->Name,
            'permissions' => $creator->LoggedInUserCanViewPermission,
            'createdLocal' => $this->getCreatedLocal()
        ];
    }

    function getCreatedLocal() {
        return DateHelper::localDatetimeFromUTC($this->Created);
    }

    function getLastEditedLocal() {
        return DateHelper::localDatetimeFromUTC($this->LastEdited);
    }

    // Special case: after 30 days each student should re-onboard
    public function getCanSkipOnboarding() {
        if ($this->getIsOnlyStudent()) {
            if (!$this->OnboardingDate) {
                return false;
            }
            $onboardingDate = DBDatetime::create()->setValue($this->OnboardingDate);
            $thirtyDaysAgo = DBDatetime::now()->modify('-30 days');
            $canSkip = $onboardingDate->getTimestamp() > $thirtyDaysAgo->getTimestamp();
            if (!$canSkip && $this->HasFinishedOnboarding) {
                $this->HasFinishedOnboarding = false;
                $this->write();
            }
            return $canSkip && $this->HasFinishedOnboarding;
        } else {
            return $this->HasFinishedOnboarding;
        }
    }
}