<?php

namespace SurfSharekit\Models;

use Exception;
use RelationaryPermissionProviderTrait;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\PermissionRole;
use SilverStripe\Security\Security;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;

/**
 * Class GroupPermissionExtension
 * @package SurfSharekit\Models
 * Extension to Silverstripe Group DataObject to make sure permissions can be given by other users when they have the permissions themselves
 */
class GroupPermissionExtension extends DataExtension implements PermissionProvider {
    private static $db = [
        'IsRemoved' => 'Boolean(0)',
        'Label_NL' => 'Varchar(255)',
        'Label_EN' => 'Varchar(255)'
    ];

    private static $has_one = [
        "DefaultRole" => PermissionRole::class
    ];

    const CMS_SECURITY_PERMISSION_CODE = 'CMS_ACCESS_SecurityAdmin';
    const OWN_GROUP = 'OWN';
    use RelationaryPermissionProviderTrait;

    public function updateCMSFields(FieldList $fields) {
        // Insert Labels after the 'Group name' field
        $fields->insertAfter('Title', TextField::create('Label_EN', 'Label (EN)'));
        $fields->insertAfter('Title', TextField::create('Label_NL', 'Label (NL)'));

        $defaultRole = $this->owner->DefaultRole();
        $fields->insertAfter("InstituteID", new ReadonlyField("DefaultRoleTitle", "Default role", $defaultRole ? $defaultRole->Title : "none"));

        // Make Group name read only
        $fields->dataFieldByName('Title')->setReadonly(true);
    }

    public function requireDefaultRecords() {
        parent::requireDefaultRecords();

    }

    /**
     * @return array
     * return all Relationally permissions for this dataObject
     */
    public function providePermissions() {
        //View permissions
        $ownPermissions = $this->provideRelationaryPermissions(GroupPermissionExtension::OWN_GROUP, 'their own group', ['view']);
        $roleGroupPermissions = $this->provideRelationaryPermissions(RoleConstant::STUDENT, 'groups of ' . RoleConstant::STUDENT . 's', ['view']);
        $roleGroupPermissions = array_merge($roleGroupPermissions, $this->provideRelationaryPermissions(RoleConstant::MEMBER, 'groups of ' . RoleConstant::MEMBER . 's', ['view']));
        $roleGroupPermissions = array_merge($roleGroupPermissions, $this->provideRelationaryPermissions(RoleConstant::SUPPORTER, 'groups of ' . RoleConstant::SUPPORTER . 's', ['view']));
        $roleGroupPermissions = array_merge($roleGroupPermissions, $this->provideRelationaryPermissions(RoleConstant::SITEADMIN, 'groups of ' . RoleConstant::SITEADMIN . 's', ['view']));
        $roleGroupPermissions = array_merge($roleGroupPermissions, $this->provideRelationaryPermissions(RoleConstant::STAFF, 'groups of ' . RoleConstant::STAFF . 's', ['view']));

        //Edit etc permissions
        $normalPermissions = $this->provideRelationaryPermissions(Institute::SAME_LEVEL, 'groups of their own institute', ['DELETE', 'EDIT', 'CREATE']);
        $scopedPermissions = $this->provideRelationaryPermissions(Institute::LOWER_LEVEL, 'groups of institutes below their own level', ['DELETE', 'EDIT', 'CREATE']);
        return array_merge($ownPermissions, $normalPermissions, $scopedPermissions, $roleGroupPermissions);
    }

    /**
     * @return string Group this extensions has access to
     */
    public function dataObj() {
        return $this->owner ?: Group::class;
    }

    /**
     * @param $newPermissions
     * @return bool
     * @throws \SilverStripe\ORM\ValidationException
     * @throws Exception
     * Checks whether or not the logged in user is allowed to set the group's permissions to the $newPermissions array and does so if allowed
     */
    public function setPermissionsFromAPI($newPermissions) {
        if (is_null($newPermissions)) {
            throw new Exception('Permissions cannot be null, use an empty array instead');
        } else if (!is_array($newPermissions)) {
            throw new Exception('Permissions must be an array');
        }

        //Create a list of permissions that the user has received from group that can edit this group
        $permissionsUserMayEdit = $this->getPermissionsCurrentUserMayEditForGroup();
        $currentPermissions = $this->getPermissions();
        $permissionsToAdd = [];
        $permissionsToRemove = [];
        foreach ($newPermissions as $newPermission) {
            if (in_array($newPermission, $currentPermissions)) {
                //Nothing changed for this permission, it's both in the old and the new permission list, thus not added or removed
            }
            if (!in_array($newPermission, $currentPermissions)) {
                //Permission is being added, may only happen by admin or someone with the same permission

                if (!(Permission::check('ADMIN') || in_array($newPermission, $permissionsUserMayEdit))) {
                    throw new Exception("Can only add permission " . $newPermission . ' if you have it yourself');
                }
                $permissionsToAdd[] = $newPermission;
            }
        }

        foreach ($currentPermissions as $currentPermission) {
            if (!in_array($currentPermission, $newPermissions)) {
                //Permission is being removed, may only happen by admin or someone with the same permission
                if (!(Permission::check('ADMIN') || in_array($currentPermission, $permissionsUserMayEdit))) {
                    throw new Exception("Can only remove permission $currentPermission if you have it yourself");
                }
                $permissionsToRemove[] = $currentPermission;
            }
        }

        //May set the permissions to what is given, else would've thrown exception by now
        foreach ($this->owner->Permissions() as $permission) {
            if (in_array($permission->Code, $permissionsToRemove)) {
                $this->owner->Permissions()->remove($permission);
            }
        }

        foreach ($permissionsToAdd as $permissionCode) {
            $permission = Permission::create();
            $permission->Code = $permissionCode;
            $permission->write();
            $this->owner->Permissions()->add($permission);
        }

        $this->owner->write();
        return true;
    }

    /**
     * @return array list of the permissionCodes of this group
     */
    public function getPermissions() {
        $codeList = [];
        foreach ($this->owner->Permissions() as $permission) {
            if ($permission && $permission->Code) {
                $codeList[] = $permission->Code;
            }
        }
        return $codeList;
    }

    /**
     * @return array list of the permissionCodes of this group
     */
    public function getPermissionsFromRoles() {
        $codeList = [];
        foreach ($this->owner->Roles() as $role) {
            /** @var PermissionRole $role */
            if ($role) {
                $codeList = array_merge($codeList, $role->Codes()->columnUnique('Code'));
            }
        }
        return array_unique($codeList);
    }

    /**
     * @throws \SilverStripe\ORM\ValidationException
     * Adds or remove CMS_ACCESS_Security permission to be SilverStripe CMS compliant
     */
    public function onBeforeWrite() {
        $hasGroupPermission = false;
        $hasSiteAdminSecurityPermission = null;
        foreach ($this->getPermissions() as $permission) {
            //Only admins and people that have a certain permission can give it
            if (strpos($permission, 'GROUP_') !== false) {
                $hasGroupPermission = true;
            }
            if (strpos($permission, self::CMS_SECURITY_PERMISSION_CODE) !== false) {
                $hasSiteAdminSecurityPermission = $permission;
            }
        }
        if ($hasGroupPermission && !$hasSiteAdminSecurityPermission) {
            $permission = Permission::create();
            $permission->Code = self::CMS_SECURITY_PERMISSION_CODE;
            $permission->write();
            $this->owner->Permissions()->add($permission);
        } else if (!$hasGroupPermission && $hasSiteAdminSecurityPermission) {
            foreach ($this->owner->Permissions() as $perm) {
                if ($perm->Code == self::CMS_SECURITY_PERMISSION_CODE) {
                    $this->owner->Permissions()->remove($perm);
                }
            }
        }

        if ($this->owner->isChanged("Label_NL")) {
            $this->owner->Title = $this->owner->Label_NL;
        }

        parent::onBeforeWrite();
    }

    public function canView($member = null, $context = []) {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isDefaultAdmin()) {
            return true;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }

        return ScopeCache::isViewableFromCache($this->dataObj());
    }

    public function canDelete($member = null, $context = []) {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }


        if ($member->isWorksAdmin()) {
            return true;
        }
        foreach ($member->Groups() as $group) {
            if ($this->checkRelationPermission(Institute::LOWER_LEVEL, 'DELETE', Security::getCurrentUser(), [Group::class => $group]) ||
                $this->checkRelationPermission(Institute::SAME_LEVEL, 'DELETE', Security::getCurrentUser(), [Group::class => $group]) ||
                $this->checkRelationPermission(GroupPermissionExtension::OWN_GROUP, 'DELETE', Security::getCurrentUser(), [Group::class => $group])) {
                return true;
            }
        }

        return false;
    }

    public function canEdit($member = null, $context = []) {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        $onlyWorksAdminCanEdit = false;
        $rolesInGroup = $this->owner->Roles();
        foreach ($rolesInGroup as $roleInGroup) {
            if ($roleInGroup->Title == RoleConstant::MEMBER) {
                $onlyWorksAdminCanEdit = true;
            }
        }

        if ($member->isWorksAdmin()) {
            return true;
        }
        foreach ($member->Groups() as $group) {
            if (!$onlyWorksAdminCanEdit) {
                if ($this->checkRelationPermission(Institute::LOWER_LEVEL, 'EDIT', Security::getCurrentUser(), [Group::class => $group]) ||
                    $this->checkRelationPermission(Institute::SAME_LEVEL, 'EDIT', Security::getCurrentUser(), [Group::class => $group])) {
                    return true;
                }
            }
        }
        return false;
    }

    public function canCreate($member = null, $context = []) {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isDefaultAdmin()) {
            return true;
        }
        if ($this->owner->InstituteID == 0) {
            return false;
        }

        if ($member->isWorksAdmin()) {
            return true;
        }
        foreach ($member->Groups() as $group) {
            if ($this->checkRelationPermission(Institute::LOWER_LEVEL, 'CREATE', Security::getCurrentUser(), [Group::class => $group]) ||
                $this->checkRelationPermission(Institute::SAME_LEVEL, 'CREATE', Security::getCurrentUser(), [Group::class => $group])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $member
     * @return bool if the group is part of a scope below that of $member
     */
    public function isLowerlevel($member, $context) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            return InstituteScoper::getScopeLevel($group->InstituteID, $this->owner->InstituteID) == InstituteScoper::LOWER_LEVEL;
        }
        return false;
    }

    /**
     * @param $member
     * @return bool if the group is part of the same scope of that of $member
     */
    public function isSameLevel($member, $context) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            return InstituteScoper::getScopeLevel($group->InstituteID, $this->owner->InstituteID) == InstituteScoper::SAME_LEVEL;
        }
        return false;
    }

    /**
     * @param $member
     * @return bool if the $member is part of this group
     */
    public function isOwn($member) {
        if ($member->Groups()->filter('ID', $this->owner->ID)->count() > 0) {
            return true;
        }
        return false;
    }

    public function canRemovePerson($person) {
        return $person->canRemoveGroup($this->owner);
    }

    /**
     * @return mixed
     * Returns a summary of all possible permissions codes per class and descriptions thereof
     */
    public function getCodeMatrix() {
        $permissionGroups = Permission::get_codes();
        unset($permissionGroups['CMS Access']);
        unset($permissionGroups['Administrator']);
        unset($permissionGroups['Content permissions']);
        unset($permissionGroups['Roles and access permissions']);

        $permissionsOfGroup = $this->getPermissions();

        $permissionsUserMayEdit = $this->getPermissionsCurrentUserMayEditForGroup();
        $permissionTitles = [];
        foreach ($permissionGroups as $permissionGroupTitle => $permissions) {
            $pGroups[$permissionGroupTitle] = [];
            foreach ($permissions as $permissionTitle => $permissionInfo) {
                $titleParts = explode('_', $permissionTitle);
                if (count($titleParts) == 3) {
                    $permissionTitles[$titleParts[0] . '_' . $titleParts[2]][$titleParts[1]] = [
                        'isSet' => in_array($permissionTitle, $permissionsOfGroup),
                        'canEdit' => in_array($permissionTitle, $permissionsUserMayEdit),
                        'fromRole' => false
                    ];
                }
            }
        }

        /**
         * @var $role PermissionRole
         */
        foreach ($this->owner->Roles() as $role) {
            foreach ($role->Codes() as $code) {
                $titleParts = explode('_', $code->Code);
                if (count($titleParts) == 3) {
                    if ($titleParts[0] != 'CMS') {
                        $permissionTitles[$titleParts[0] . '_' . $titleParts[2]][$titleParts[1]] = [
                            'isSet' => true,
                            'canEdit' => false,
                            'fromRole' => true
                        ];
                    }
                }
            }
        }

        return $permissionTitles;
    }

    function getLoggedInUserPermissions() {
        $loggedInMember = Security::getCurrentUser();
        return [
            'canEdit' => $this->canEdit($loggedInMember),
            'canDelete' => $this->canDelete($loggedInMember)
        ];
    }

    private function getPermissionsCurrentUserMayEditForGroup() {
        $permissionsUserMayEdit = [];
        if ($member = Security::getCurrentUser()) {
            if ($this->canEdit($member)) {
                foreach ($member->Groups() as $group) {
                    if ($this->checkRelationPermission(Institute::LOWER_LEVEL, 'EDIT', Security::getCurrentUser(), [Group::class => $group]) ||
                        $this->checkRelationPermission(Institute::SAME_LEVEL, 'EDIT', Security::getCurrentUser(), [Group::class => $group])) {
                        $permissionsUserMayEdit = array_merge($permissionsUserMayEdit, ScopeCache::getPermissionsFromRequestCache($group));
                    }
                }
            }
        }
        foreach ($this->owner->Roles() as $role) {
            $permissionsUserMayEdit = array_diff($permissionsUserMayEdit, $role->Codes()->column('Code'));
        }

        if (count($permissionsUserMayEdit)) {
            $permissionsUserMayEdit[] = 'CMS_ACCESS_SecurityAdmin';
        }

        return array_unique($permissionsUserMayEdit);
    }

    static function getPermissionCases() {
        $member = Security::getCurrentUser();
        $permissionRoles = PermissionRole::get();

        $findIDOfPermissionRole = function ($roleTitle) use ($permissionRoles) {
            foreach ($permissionRoles as $permissionRole) {
                if ($permissionRole->Title == $roleTitle) {
                    return $permissionRole->ID;
                }
            }
            return null;
        };

        $rolePermissionCheck = function ($roleTitle) use ($findIDOfPermissionRole) {
            return "`Group`.`ID` IN (SELECT Group_Roles.GroupID FROM Group_Roles WHERE Group_Roles.PermissionRoleID = " . $findIDOfPermissionRole($roleTitle) . ")";
        };

        return [
            "GROUP_VIEW_OWN" => "`Group`.`ID` IN (SELECT `GroupID` FROM `Group_Members` WHERE `MemberID` = $member->ID)",
            "GROUP_VIEW_SITEADMIN" => $rolePermissionCheck(RoleConstant::SITEADMIN),
            "GROUP_VIEW_SUPPORTER" => $rolePermissionCheck(RoleConstant::SUPPORTER),
            "GROUP_VIEW_DEFAULT MEMBER" => $rolePermissionCheck(RoleConstant::MEMBER),
            "GROUP_VIEW_STAFF" => $rolePermissionCheck(RoleConstant::STAFF),
            "GROUP_VIEW_STUDENT" => $rolePermissionCheck(RoleConstant::STUDENT)
        ];
    }

    public function onAfterWrite() {
        parent::onAfterWrite();

        // Remove all cache when group changes
        if ($this->owner->isChanged()) {
            ScopeCache::removeAllCachedPermissions();
            ScopeCache::removeAllCachedViewables();
            ScopeCache::removeAllCachedDataLists();
            $this->removeCachedPermissions();
        }

        if($this->owner->isChanged('Label_NL') || $this->owner->isChanged('Label_EN')) {
            foreach ($this->owner->Members() as $member) {
                if($member->getClassName() == Person::class) {
                    PersonSummary::updateFor($member);
                    SearchObject::updateForPerson($member);
                }
            }
        }
    }

    public function onBeforeDelete() {
        $this->validatePredelete();
        parent::onBeforeDelete();
    }

    private function validatePredelete() {
        if ($this->dataObj()->Members()->exists()) {
            throw new ValidationException("Group has members, associate members with different group or unlink them before deleting");
        }
    }

    public function removeCachedPermissions() {
        SimpleCacheItem::get()->filter(['DataObjectID' => $this->owner->ID, 'DataObjectClass' => $this->owner->ClassName])->removeAll();
    }

    public function validate(ValidationResult $validationResult) {
        parent::validate($validationResult);

        if ($this->owner->DefaultRoleID) {
            $groupContainsDefaultRole = $this->owner->Roles()->find("ID", $this->owner->DefaultRoleID);
            if (!$groupContainsDefaultRole) {
                $validationResult->addError("You're not allowed to remove the default role for this group");
            }
        }

        return $validationResult;
    }
}