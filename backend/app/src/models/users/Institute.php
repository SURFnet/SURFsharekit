<?php

namespace SurfSharekit\Models;

use Exception;
use RelationaryPermissionProviderTrait;
use SilverStripe\extensions\Gridfield\Column\GridfieldSecretaryColumn;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\GridFieldArchiveAction;
use SilverStripe\Versioned\Versioned;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\helper\InstituteGroupManager;
use SurfSharekit\Models\Helper\PermissionRoleHelper;

/***
 * Class Institute
 * @package SurfSharekit\Models
 * @method Institute Institute()
 * @method InstituteImage InstituteImage()
 * @method HasManyList Institutes()
 * DataObject Representing an organisational entity (e.g. University, University for Applied Sciences, Faculty, Classetc.)
 */
class Institute extends DataObject implements PermissionProvider {
    use RelationaryPermissionProviderTrait;

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    const LOWER_LEVEL = 'LOWERLEVEL';
    const SAME_LEVEL = 'SAMELEVEL';

    private static $table_name = 'SurfSharekit_Institute';

    private static $db = [
        'Title' => 'Varchar(255)',
        'LicenseActive' => "Boolean(0)",
        'ConextCode' => 'Varchar(255)',
        'SRAMCode' => 'Varchar(255)',
        'ROR' => 'Varchar(255)',
        'ConextTeamIdentifier' => 'Varchar(255)',
        'Abbreviation' => 'Varchar(255)',
        'Level' => 'Enum(array("organisation","department","lectorate","discipline","consortium"), null)',
        'Type' => 'Enum(array("CentreOfExpertise"), null)',
        'IsRemoved' => 'Boolean(0)',
        'IsHidden' => 'Boolean(0)',
        'UpdateInstituteLabels' => 'Boolean(0)',
        'Description' => 'Text'
    ];

    private static $has_one = [
        'Institute' => Institute::class, //parent
        'InstituteImage' => InstituteImage::class
    ];

    private static $owns = [
        'InstituteImage'
    ];

    private static $has_many = [
        'Institutes' => Institute::class,   //children
        'Templates' => Template::class,
        'Groups' => Group::class,
        'RepoItems' => Repoitem::class
    ];

    private static $searchable_fields = [
        'Institute.Title',
        'Title',
        'Level',
        'ConextCode'
    ];

    private static $summary_fields = [
        'Institute.Title' => 'Parent institute',
        'Title' => 'Title',
        'Level' => 'Level',
        'ConextCode' => 'ConextCode'
    ];

    private static $field_labels = [
        'Institute.Title' => 'Parent institute'
    ];

    private static $many_many = [
        'Channels' => Channel::class,
        'ConsortiumChildren' => Institute::class,
        'AutoAddedGroups' => Group::class, //people in these groups will be automatically added when onboarding
    ];

    private static $many_many_extraFields = [
        'ConsortiumChildren' => [
            'Secretary' => 'Int',
        ],
    ];

    private static $belongs_many_many = [
        'ConsortiumParents' => Institute::class,
        'Persons' => Person::class
    ];

    private static $indexes = [
        'Level' => true,
        'ConextCode' => true,
        'SRAMCode' => true
    ];

    public static $overwriteCanView = false;

    function requireDefaultRecords() {
        parent::requireDefaultRecords();
        PermissionRoleHelper::addDefaultPermissionRoles();
        $repoTypes = Template::getRepoTypes();
        $missingTemplatesInstitutes = Institute::get()->filter(['Templates.Count():LessThan' => count($repoTypes)]);
        foreach ($missingTemplatesInstitutes as $institute) {
            /** @var Institute $institute */
            $institute->ensureTemplatesExist();
        }
        $rootInstitutes = Institute::get()->filter(['InstituteID' => 0]);
        foreach ($rootInstitutes as $rootInstitute) {
            InstituteGroupManager::createDefaultGroups($rootInstitute);
        }
    }


    function getCMSFields() {
        $fields = parent::getCMSFields();

        if ($this->Level != 'consortium') {
            $fields->removeByName('AutoAddedGroups');
        }

        $fields->insertBefore('Title', CheckboxField::create('UpdateInstituteLabels', 'Update labels'));
        $fields->makeFieldReadonly('UpdateInstituteLabels');

        /** @var Institute $parentInstitute */
        $parentInstitute = $this->Institute();
        if ($parentInstitute && $parentInstitute->exists()) {
            /** @var DropdownField $parentInstituteField */
            $parentInstituteField = new DropdownField('InstituteID', 'Parent institute');
            $parentInstituteField->setSource(InstituteScoper::getDataListScopedTo(Institute::class, [$parentInstitute->getRootInstitute()->ID])->map('ID', 'Title'));
            $parentInstituteField->setEmptyString('Select a parent institute');
            $parentInstituteField->setHasEmptyDefault(false);
            $parentInstituteField->setDescription('Changing the institute may cause unexpected results in the existing templates');
            $fields->removeByName('InstituteID');
            $fields->insertBefore('Title', $parentInstituteField);

        } else {
            $parentInstituteName = '- this is a root institute -';
            $parentInstituteDisplayField = ReadonlyField::create('DisplayInstitute', 'Parent institute', $parentInstituteName);
            $parentInstituteHiddenField = HiddenField::create('InstituteID', 'Institute', $this->InstituteID);
            $fields->replaceField('InstituteID', $parentInstituteHiddenField);
            $fields->insertBefore('Title', $parentInstituteDisplayField);
        }

        $uuidField = ReadonlyField::create('DisplayIdentifier', 'Identifier', $this->Uuid);
        $fields->insertBefore('Title', $uuidField);


        $children = $this->ConsortiumChildren()->map('ID', 'Title')->toArray();

        if ($this->Level === 'consortium') {
            $fields->addFieldToTab('Root.Main', DropdownField::create(
                'SecretaryID',
                'Penvoerder',
                $children
            )
            ->setEmptyString('-- Selecteer een penvoerder --')
            ->setValue($this->getSecretaryID()));

        } else {
            $fields->removeByName('ConsortiumChildren[Secretary]');
        }


        /** @var DropdownField $levelField */
        $levelField = $fields->dataFieldByName('Level');
        $levelField->setHasEmptyDefault(true);
        $levelField->setEmptyString('-- select an institute level --');

        /** @var DropdownField $typeField */
        $typeField = $fields->dataFieldByName('Type');
        $typeField->setHasEmptyDefault(true);
        $typeField->setEmptyString('-- select a type --');

        if ($this->isInDB()) {
            /** @var GridField $institutesGridField */
            $institutesGridField = $fields->dataFieldByName('Institutes');
            $institutesGridFieldConfig = $institutesGridField->getConfig();
            $institutesGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldDeleteAction()]);

            /** @var GridField $groupsGridField */
            $groupsGridField = $fields->dataFieldByName('Groups');
            $groupsGridFieldConfig = $groupsGridField->getConfig();
            $groupsGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldDeleteAction()]);

            /** @var GridField $templatesGridField */
            $templatesGridField = $fields->dataFieldByName('Templates');
            $templatesGridFieldConfig = $templatesGridField->getConfig();
            $templatesGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldDeleteAction()]);

            /** @var GridField $repoitemsGridField */
            $repoitemsGridField = $fields->dataFieldByName('RepoItems');
            $repoitemsGridFieldConfig = $repoitemsGridField->getConfig();
            $repoitemsGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldDeleteAction()]);

            /** @var GridField $consortiumChildrenGridField */
            $consortiumChildrenGridField = $fields->dataFieldByName('ConsortiumChildren');
            $consortiumChildrenGridFieldConfig = $consortiumChildrenGridField->getConfig();
            $consortiumChildrenGridFieldConfig->removeComponentsByType([GridFieldArchiveAction::class]);

             /** @var GridFieldDataColumns $header */
            $header = $consortiumChildrenGridFieldConfig->getComponentByType(GridFieldDataColumns::class);

            $displayFields = [
                "Title" => "Title",
                "Level" => "Level",
                "ConextCode" => "ConextCode",
            ];

            if ($this->Level === 'consortium') {
                $displayFields["ID"] = "";

                $header->setFieldFormatting([
                    'ID' => function ($value, $item) {
                        $secretaryID = $this->ConsortiumChildren()->find('ID', $item->ID)->Secretary;
                        return $secretaryID === 1
                            ? "<span style='color:#00008b;font-size:1em;font-weight:600;'>Penvoerder</span>"
                            : "<span></span>";
                    }
                ]);
            }

            $header->setDisplayFields($displayFields);
        }

        $fields->dataFieldByName('ConextTeamIdentifier')->setDescription("Separate multiple identifiers with a single space");

        // Get the current user
        $currentUser = Security::getCurrentUser();
        if ($this->InstituteID == 0 && $this->Level == 'organisation') {
            if ($this->ROR) {
                if (!Permission::checkMember($currentUser, 'ADMIN')) {
                    $fields->dataFieldByName('ROR')->setReadonly(true);
                }
            }
        } else {
            $fields->removeByName('ROR');
        }

        return $fields;
    }

    public function getSecretaryID() {
       return $this->ConsortiumChildren()->filter("Secretary", 1)->first()->ID ?? 0;
    }

    public function providePermissions() {
        $allActionsOnExistingObject = ['VIEW', 'DELETE', 'EDIT'];

        $normalPermissions = $this->provideRelationaryPermissions(Institute::SAME_LEVEL, 'their own institute', $allActionsOnExistingObject);
        $scopedPermissions = $this->provideRelationaryPermissions(Institute::LOWER_LEVEL, 'institutes below their own level', array_merge(['CREATE'], $allActionsOnExistingObject));

        return array_merge($normalPermissions, $scopedPermissions);
    }

    public function canCreate($member = null, $context = []) {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }

        if (Permission::checkMember($member, 'ADMIN')) {
            return true;
        }

        if ($member->isWorksAdmin()) {
            return true;
        }
        $parent = $this->Institute();
        if (!$parent || !$parent->exists()) {
            return false;
        }

        return $parent->canCreateSubInstitute(Security::getCurrentUser());
    }

    public function canView($member = null, $context = []) {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }

        if (parent::canView($member)) {
            return true;
        }

        if (static::$overwriteCanView && !$this->InstituteID) {
            return true;
        }

        if ($member->isWorksAdmin()) {
            return true;
        }

        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            foreach ($this->ConsortiumParents() as $consortiumParent) {
                if (InstituteScoper::getScopeLevel($consortiumParent->ID, $group->InstituteID) === InstituteScoper::SAME_LEVEL) {
                    return true;
                }
            }
            if ($this->checkRelationPermission(Institute::SAME_LEVEL, 'VIEW', $member, [Group::class => $group])
                || $this->checkRelationPermission(Institute::LOWER_LEVEL, 'VIEW', $member, [Group::class => $group])) {
                return true;
            }
        }

        return false;
    }

    public function canDelete($member = null, $context = []) {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }

        if (parent::canDelete($member)) {
            return true;
        }

        if ($member->isWorksAdmin()) {
            return true;
        }

        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            if ($this->checkRelationPermission(Institute::SAME_LEVEL, 'DELETE', $member, [Group::class => $group])
                || $this->checkRelationPermission(Institute::LOWER_LEVEL, 'DELETE', $member, [Group::class => $group])) {
                return true;
            }
        }

        return false;
    }

    public function canEdit($member = null, $context = []) {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }

        if (parent::canEdit($member)) {
            return true;
        }

        if ($member->isWorksAdmin()) {
            return true;
        }

        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            if ($this->checkRelationPermission(Institute::SAME_LEVEL, 'EDIT', $member, [Group::class => $group])
                || $this->checkRelationPermission(Institute::LOWER_LEVEL, 'EDIT', $member, [Group::class => $group])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $member
     * @return bool if the object is part of a scope below that of $member
     */
    public function isLowerLevel($member, $context) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            if ($this->InstituteID === $group->InstituteID) { //is possible this object is being created, normal scoping is preemptive then which has become invalid
                return true;
            } else if (InstituteScoper::getScopeLevel($group->InstituteID, $this->InstituteID) == InstituteScoper::LOWER_LEVEL) {
                return true;
            } else if ($this->isInDB()) {
                return InstituteScoper::getScopeLevel($group->InstituteID, $this->ID) == InstituteScoper::LOWER_LEVEL;
            } else if (($parentInstitute = $this->Institute()) && $parentInstitute->exists()) {
                return $parentInstitute->isLowerlevel($member, $context);
            }
        }
        return false;
    }

    /**
     * @param $member
     * @return bool if the object is part of the same scope of that of $member
     */
    public function isSameLevel($member, $context) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            return InstituteScoper::getScopeLevel($group->InstituteID, $this->ID) == InstituteScoper::SAME_LEVEL;
        }
        return false;
    }

    public function isLeaf()
    {
        return $this->Institutes()->count() == 0;
    }

    /** @return null|array */
    public function getInstituteChain($maxParents = 10) {
        $maxParents = $maxParents - 1;
        if($maxParents == 0) return null; // maxParents reached
        /** @var Institute $parentInstitute */
        $parentInstitute = $this->Institute();
        if($parentInstitute && $parentInstitute->exists()){
            $parentInstituteChain = $parentInstitute->getInstituteChain($maxParents);
            $addedInstitutes = array_merge([$this->Uuid=>$this], $parentInstituteChain['addedInstitutes']);
        }else{
            $parentInstituteChain = null;
            $addedInstitutes = [$this->Uuid=>$this];
        }

        return ['institute' => $this, 'parentInstituteChain' => $parentInstituteChain, 'addedInstitutes' => $addedInstitutes];
    }

    protected function onBeforeWrite() {
        if ($this->InstituteID == 0 && !in_array($this->Level, ['organisation', 'consortium'])) {
            throw new ValidationException('Cannot have a root institute without level organisation or consortium, ID = ' . $this->ID);
        }

        if($this->owner->isChanged('Label_NL') || $this->owner->isChanged('Label_EN')) {
            // only execute if $this is certain roles

            // If Label_NL is being edited, then set the Title as the same value of Label_NL
            $this->Title = $this->Label_NL;
        }

        // Only execute this code if the title of an existing institute changes
        if ($this->isChanged('Title') && $this->isInDB()){
            $this->UpdateInstituteLabels = true;

            $groups = Group::get()->filter(['InstituteID' => $this->ID]);

            foreach ($groups as $group){

                // Take the substring up to the position of 'van' or 'of'
                $resultTitle = trim(substr($group->Title, 0, strpos($group->Title, 'van ') + 3));
                $resultLabelNL = trim(substr($group->Label_NL, 0, strpos($group->Label_NL, 'van ') + 3));
                $resultLabelEN = trim(substr($group->Label_EN, 0, strpos($group->Label_EN, 'of ') + 2));

                // Replace all words in the existing title except the first two with the new title of the Institute
                $group->Title = $resultTitle . ' ' . $this->Title;
                $group->Label_NL = $resultLabelNL . ' ' . $this->Title;
                $group->Label_EN = $resultLabelEN . ' ' . $this->Title;
                $group->write();
            }

            $templates = Template::get()->filter(['InstituteID' => $this->ID]);
            // Update titles of associated templates
            foreach ($templates as $template) {
                // Use a regular expression to replace content within parentheses
                $template->Title = preg_replace('/\([^)]+\)/', '(' . $this->Title . ')', $template->Title);
                $template->Label_NL = preg_replace('/\([^)]+\)/', '(' . $this->Title . ')', $template->Title);
                $template->Label_EN = preg_replace('/\([^)]+\)/', '(' . $this->Title . ')', $template->Title);
                $template->write();
            }
        }

        parent::onBeforeWrite();
    }

    /**
     * @throws \SilverStripe\ORM\ValidationException
     * Automatically generates a group for employees and students if they do not yet exist
     */
    protected function onAfterWrite() {
        parent::onAfterWrite();

        // Remove all cache after institute changes
        if ($this->isChanged()) {
            ScopeCache::removeAllCachedViewables();
            ScopeCache::removeAllCachedPermissions();
            ScopeCache::removeAllCachedDataLists();
        }

        /** @var InstituteImage $image */
        if (($image = $this->InstituteImage()) && $image->exists() && !$image->isPublished()) {
            $image->publishSingle();
        }

        InstituteGroupManager::createDefaultGroups($this);

        $this->ensureTemplatesExist();
        if ($this->isChanged('ID')) { //implied onAfterCreate
            foreach ($this->Templates() as $template) {
                /** @var Template $parentTemplate */
                $parentTemplate = $template->Parent;
                if (!is_null($parentTemplate)) {
                    $parentTemplate->downPropagateTemplateMetaFields($template);
                }
            }
        }

        if($this->isChanged('IsHidden') && !$this->isChanged('ID')) {
            $this->setChildInstitutesIsHiddenAttribute();
        }

        // Logic for selecting a Secretary and in case the level is being changed
        $selectedSecretaryID = $this->getField('SecretaryID');
        $isLevelChanged = $this->isChanged('Level') && $this->Level !== 'consortium';

        foreach ($this->ConsortiumChildren() as $child) {
            $isSelectedSecretary = ($child->ID == $selectedSecretaryID);
            $secretaryStatus = ($isSelectedSecretary && !$isLevelChanged) ? 1 : 0;

            $this->ConsortiumChildren()->add($child, ['Secretary' => $secretaryStatus]);
        }

        $this->updateRelevantRepoItems();
    }

    public function ensureTemplatesExist() {
        $repoTypes = Template::getRepoTypes();
        foreach ($repoTypes as $repoType) {
            $templateOfType = $this->Templates()->filter(['RepoType' => $repoType])->first();

            if (!$templateOfType || !$templateOfType->Exists()) {
                $templateOfType = Template::create();
                $templateOfType->InstituteID = $this->ID;
                $templateOfType->RepoType = $repoType;
                $templateOfType->Title = "$repoType Template ($this->Title)";
                $templateOfType->Description = "Automatisch gegenereerd template";
                $templateOfType->write();
            }
        }
    }

    function isRootInstitute() {
        return $this->InstituteID == 0;
    }

    /** @return Institute|null */
    function getRootInstitute() {
        /** @var Institute $institute */
        $institute = $this;
        while ($institute) {
            if ($institute->isRootInstitute()) {
                return $institute;
            }
            $institute = $institute->Institute();
        }
        return null;
    }

    public static function getAllChildInstitutes(string $instituteUuid, $inclusive = false): DataList {
        $childIds = self::getAllChildInstituteUuids($instituteUuid, $inclusive) ?: 0;
        return Institute::get()->filter(["Uuid" => $childIds]);
    }

    public static function getAllChildInstituteUuids(string $instituteUuid, $inclusive = false): array {
        $idToExclude = $inclusive ? '' : $instituteUuid;
        $queryString = "
            WITH RECURSIVE TreeTraversal (Uuid, InstituteUuid, Level) AS (
                SELECT Uuid, InstituteUuid, 0 AS Level FROM SurfSharekit_Institute WHERE Uuid = ?
                UNION ALL
                SELECT child.Uuid, child.InstituteUuid, child.Level + 1 FROM SurfSharekit_Institute child
                INNER JOIN TreeTraversal p ON child.InstituteUuid = p.Uuid
            )
            SELECT Uuid
            FROM TreeTraversal
            WHERE Uuid != ?;
        ";

        return DB::prepared_query($queryString, [$instituteUuid, $idToExclude])->column();
    }

    /** @return Institute|null */
    function getParentDepartment() {
        /** @var Institute $institute */
        $institute = $this->Institute();
        $recursiveCount = 0;
        while ($institute && $institute->exists() && $recursiveCount < 10) {
            if ($institute->getField('Level') == 'department') {
                return $institute;
            }
            $institute = $institute->Institute();
            $recursiveCount++;
        }
        return null;
    }

    /** @return Institute|null */
    function getParentLectorate() {
        /** @var Institute $institute */
        $institute = $this->Institute();
        $recursiveCount = 0;
        while ($institute && $institute->exists() && $recursiveCount < 10) {
            if ($institute->getField('Level') == 'lectorate') {
                return $institute;
            }
            $institute = $institute->Institute();
            $recursiveCount++;
        }
        return null;
    }

    function getLoggedInUserPermissions() {
        $loggedInMember = Security::getCurrentUser();

        return [
            'canCreateLearningObject' => $this->canCreateRepoItem('LearningObject'),
            'canCreatePublicationRecord' => $this->canCreateRepoItem('PublicationRecord'),
            'canCreateResearchObject' => $this->canCreateRepoItem('ResearchObject'),
            'canCreateDataset' => $this->canCreateRepoItem('Dataset'),
            'canCreateProject' => $this->canCreateRepoItem('Project'),
            'canEdit' => $this->canEdit($loggedInMember),
            'canDelete' => $this->canDelete($loggedInMember),
            'canCreateSubInstitute' => $this->canCreateSubInstitute($loggedInMember)
        ];
    }

    function canCreateSubInstitute($member, $context = []) {
        if ($this->Level == 'consortium') {
            return false;
        }
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }

        if (Permission::checkMember($member, 'ADMIN')) {
            return true;
        }

        if ($member->isWorksAdmin()) {
            return true;
        }

        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            $groupScopeToThis = InstituteScoper::getScopeLevel($this->ID, $group->InstituteID);
            if (in_array($groupScopeToThis, [InstituteScoper::SAME_LEVEL, InstituteScoper::HIGHER_LEVEL])) {
                $permissions = ScopeCache::getPermissionsFromRequestCache($group);
                if (in_array('INSTITUTE_CREATE_LOWERLEVEL', $permissions)) {
                    return true;
                }
            }
        }

        return false;
    }

    function canCreateSubInstituteViaApi($parentInstitute) {
        return $parentInstitute->canCreateSubInstitute(Security::getCurrentUser());
    }

    function setIsRemovedFromApi($value) {
        if ($this->IsRemoved !== $value) { //Soft removing
            if (!$this->canDelete(Security::getCurrentUser())) {
                throw new Exception('Changing isRemoved would cause a deletion or reset on (Sub)Institute ' . $this->Uuid . ', you have no permission to do so');
            }

            $this->IsRemoved = $value;

            //Remove all subInstitutes as well
            foreach ($this->Institutes() as $subInstitute) {
                $subInstitute->setIsRemovedFromApi($value);
                $subInstitute->write();
            }
        }
    }

    function getSummary() {
        return [
            'title' => $this->Title,
            'level' => $this->Level,
            'id' => $this->Uuid
        ];
    }

    private function updateRelevantRepoItems() {
        if (!$this->isChanged('ID')) { //implied not the first time writing this object
            if ($this->isChanged('Title')) {
                RepoItem::updateAttributeBasedOnMetafield($this->Title, "InstituteID = $this->ID");
            }
        }
    }

    private function getReport() {
        return InstituteReport::getReportOf($this);
    }

    public function getChildrenInstitutesCount() {
        return $this->Institutes()->count();
    }

    public function getChildrenInstitutesByLevel($level) {
        $resultSet = [];
        $childrenInstitutes = $this->Institutes();
        foreach ($childrenInstitutes as $childInstitute) {
            if ($childInstitute->Level == $level) {
                $resultSet[] = $childInstitute->Uuid;
            }
            $childResultSet = $childInstitute->getChildrenInstitutesByLevel($level);
            if (count($childResultSet)) {
                $resultSet = array_merge($resultSet, $childResultSet);
            }
        }
        return $resultSet;
    }

    public function getIsBaseScopeForUser() {
        if ($this->InstituteID == 0) {
            return true;
        } else {
            return $this->canView() && !$this->Institute()->canView();
        }
    }

    public function getIsUsersConextInstitute() {
        $member = Security::getCurrentUser();
        foreach ($member->ScopedGroups($this->dataObj()->getRelatedInstitute()->ID) as $group) {
            if ($group->InstituteID == $this->ID) {
                foreach ($group->Roles() as $role) {
                    if ($role->Title === RoleConstant::MEMBER) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function canCreateRepoItem($repoType) {
        $repoItem = new RepoItem();
        $repoItem->InstituteID = $this->ID;
        $repoItem->RepoType = $repoType;
        return $repoItem->canCreate();
    }

    public function canAddConsortiumChildViaApi($instituteToAdd) {
        return false;
    }

    public function canRemoveConsortiumCogehildViaApi($instituteToRemove) {
        return false;
    }

    public function canAddConsortiumParentViaApi($instituteToAdd) {
        return false;
    }

    public function canRemoveConsortiumParentViaApi($instituteToRemove) {
        return false;
    }

    protected function onBeforeDelete() {
        $this->validatePredelete();
        parent::onBeforeDelete();
    }

    public function onAfterDelete() {
        parent::onAfterDelete();
        SearchObject::get()->filter('InstituteID', $this->ID)->removeAll();
        $instituteImage = $this->InstituteImage();
        if ($instituteImage && $instituteImage->exists()) {
            $instituteImage->delete();
        }
        Template::get()->filter('InstituteID', $this->ID)->removeAll();
    }

    private function validatePredelete() {
        if ($this->Groups()->exists() || $this->getManyManyComponents('AutoAddedGroups')->exists()) {
            throw new ValidationException("Institute has a group, remove group or associate group with different institute before deleting");
        }
        if ($this->Institutes()->exists()) {
            throw new ValidationException("Institute has subinstitutes, remove subinstitutes or associate them with a different institute before deleting");
        }
        if ($this->Channels()->exists()) {
            throw new ValidationException("Institute referenced in External Api Channel, remove link before deleting");
        }
        if ($this->ConsortiumChildren()->exists()) {
            throw new ValidationException("Institute is part of a consortium, remove link to consortia children before deleting");
        }
        if ($this->RepoItems()->exists()) {
            throw new ValidationException("Institute is owner of repoitem(s), associate repoitem(s) with a different institute before deleting");
        }
        if ($this->ConsortiumParents()->exists()) {
            throw new ValidationException("Institute is part of a consortium, remove links before deleting");
        }
        if (RepoItemMetaFieldValue::get()->filter('InstituteID', $this->ID)->filter('IsRemoved', 0)->exists()) {
            throw new ValidationException("Institute is mentioned in Repository Items, remove answers referring to institute before deleting");
        }
        if (DefaultMetaFieldOptionPart::get()->filter('InstituteID', $this->ID)->exists()) {
            throw new ValidationException("Institute is mentioned as a default option, remove this reference before deleting");
        }
    }

    private function setChildInstitutesIsHiddenAttribute() {
        if($this->Level != 'consortium') {
            $allChildInstitutes = $this->Institutes();
            foreach ($allChildInstitutes as $institute) {
                $institute->IsHidden = $this->IsHidden;
                $institute->write();
            }
        }
    }

    /**
     * Syncs groups where user is added to, $groupCodes are the codes the person should have
     * $compareWith is optional and should exist of group codes
     * if $compareWith is set it should only compare these group codes and not all of them
     * It is recommended to always give $compareWith group codes, if not user will lose all given groups (also system groups!)
     *
     * @param Person $person
     * @param array $groupCodes
     * @param array|null $compareWith
     * @return void
     * @throws Exception
     */
   public function syncPersonGroups(Person $person, array $groupCodes, ?array $compareWith = null) {
        $comparedGroups = null;
        if ($compareWith) {
            $comparedGroups = array_diff($compareWith, $groupCodes);
        }

        if (count($groupCodes)) {
            $addTo = $this->Groups()->filter(['Roles.Title' => $groupCodes]);
            foreach ($addTo as $group) {
                /** @var Group $group */
                if ($group->Members()->find('ID', $person->ID) === null) {
                    $group->Members()->add($person);
                }
            }
        }

        $removeFrom = $this->Groups();

        if (count($groupCodes)) {
            $removeFrom = $removeFrom->filter(['Roles.Title:not' => $groupCodes]);
        }

        if ($comparedGroups !== null) {
            $removeFrom = $removeFrom->filter(['Roles.Title' => (count($comparedGroups) ? $comparedGroups : [-1])]);
        }

       foreach ($removeFrom as $group) {
           /** @var Group $group */
           if ($group->Members()->find('ID', $person->ID) !== null) {
               // MB: 2024-06-27 temporary disable removal of user groups
              // $group->Members()->remove($person);
           }
       }
   }

    public function validate() {
        $result = parent::validate();

        if ($this->InstituteID == 0 && $this->ROR) {
            $rorRegex = '/^https:\/\/ror\.org\/0[a-hj-km-np-tv-z0-9]{6}[0-9]{2}$/';

            if (!preg_match($rorRegex, $this->ROR)) {
                $result->addError('Invalid ROR format. Expected format: https://ror.org/0xxxxxxx');
            }
        }

        return $result;
    }

}