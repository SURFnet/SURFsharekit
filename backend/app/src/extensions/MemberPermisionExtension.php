<?php

namespace SurfSharekit\Models;

use Exception;
use Illuminate\Support\Arr;
use RelationaryPermissionProviderTrait;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
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
 * Class MemberPermisionExtension
 * @package SurfSharekit\Models
 * Extension on the SilverStripe Member DataObject to add Relationally permissions
 */
class MemberPermisionExtension extends DataExtension implements PermissionProvider {
    use RelationaryPermissionProviderTrait;

    const SELF = 'SELF';
    const OTHER = 'OTHER';

    public function providePermissions() {
        $normalPermissions = $this->provideRelationaryPermissions(Institute::SAME_LEVEL, 'members of their own institute', ['EDIT', 'DELETE']);
        $mergePermissions = $this->provideRelationaryPermissions(Institute::SAME_LEVEL, 'members of their own institute and below', ['MERGE']);
        $scopedPermissions = $this->provideRelationaryPermissions(Institute::LOWER_LEVEL, 'members of institutes below their own level', ['EDIT', 'DELETE']);
        $selfPermission = $this->provideRelationaryPermissions(MemberPermisionExtension::SELF, 'themselves', ['VIEW', 'EDIT']);
        $othersPermission = $this->provideRelationaryPermissions(MemberPermisionExtension::OTHER, 'others', ['VIEW', 'CLAIM']);
        return array_merge($normalPermissions, $mergePermissions, $scopedPermissions, $selfPermission, $othersPermission);
    }

    //Used so we can use this trait in DataExtension as well
    public function dataObj() {
        return $this->owner ?: Person::class;
    }

    public function canCreate($member = null, $context = []) {
        return true;
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

        // for persons with no institutes
        if ($this->owner->RootInstitutes()->count() === 0) {
            return !!Permission::checkMember($member, 'PERSON_CLAIM_OTHER');
        }


        foreach ($member->Groups() as $group) {
            if ($this->checkRelationPermission(MemberPermisionExtension::SELF, 'VIEW', Security::getCurrentUser(), [Group::class => $group]) ||
                $this->checkRelationPermission(MemberPermisionExtension::OTHER, 'VIEW', Security::getCurrentUser(), [Group::class => $group])) {
                if ($this->owner->IsRemoved) {
                    return $this->canDelete(Security::getCurrentUser());
                }
                return true;
            }
        }
        return false;
    }

    public function canSanitize($member = null, $context = []) {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }

        if (is_null($member)) {
            return false;
        }

        if(Permission::check("REPOITEM_SANITIZE_ALL")) {
            return true;
        }

        return false;
    }

    public function canEdit($member = null, $context = []) {
        $member = $member ?: Security::getCurrentUser();

        if (!$member) {
            return false;
        }
        if ($member->isDefaultAdmin()) {
            return true;
        }

        if ($member->isWorksAdmin()) {
            return true;
        }

        // for persons with no institutes
        if ($this->owner->RootInstitutes()->count() === 0) {
            return !!Permission::checkMember($member, 'PERSON_CLAIM_OTHER');
        }

        foreach ($member->Groups() as $group) {
            if ($this->checkRelationPermission(Institute::LOWER_LEVEL, 'EDIT', Security::getCurrentUser(), [Group::class => $group]) ||
                $this->checkRelationPermission(Institute::SAME_LEVEL, 'EDIT', Security::getCurrentUser(), [Group::class => $group]) ||
                $this->checkRelationPermission(MemberPermisionExtension::SELF, 'EDIT', Security::getCurrentUser(), [Group::class => $group])) {
                return true;
            }
        }
        return false;
    }

    public function canDelete($member = null, $context = []) {
        if (parent::canDelete($member)) {
            return true;
        }
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
                $this->checkRelationPermission(Institute::SAME_LEVEL, 'DELETE', Security::getCurrentUser(), [Group::class => $group])) {
                return true;
            }
        }

        return false;
    }

    public function onBeforeWrite() {
        if (Security::getCurrentUser()) {
            if ($this->owner->exists()) {
                foreach ($this->owner->getChangedFields(true, DataObject::CHANGE_VALUE) as $changedFieldName => $fieldInfo) {
                    if (!in_array($changedFieldName, ['Email', 'CreatedByID', 'ModifiedByID']) && !$this->canEdit()) {
                        throw new Exception('Cannot edit person information besides email information');
                    }
                }
            }
        }
        parent::onBeforeWrite();
    }

    public function isLowerlevel($member, $context) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            foreach ($this->dataObj()->Groups() as $ownerGroup) {
                if (InstituteScoper::getScopeLevel($group->InstituteID, $ownerGroup->InstituteID) == InstituteScoper::LOWER_LEVEL) {
                    return true;
                }
            }
        }
        return false;
    }

    public function isSameLevel($member, $context) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            foreach ($this->dataObj()->Groups() as $ownerGroup) {
                if (InstituteScoper::getScopeLevel($group->InstituteID, $ownerGroup->InstituteID) == InstituteScoper::SAME_LEVEL) {
                    return true;
                }
            }
        }
        return false;
    }

    public function ScopedGroups($instituteID) {
        if ($this->dataObj() instanceof Person) {
            $groups = $this->dataObj()->Groups()->innerJoin(
                "(SELECT p1.ID as p1ID, p2.ID as p2ID, p3.ID as p3ID, p4.ID as p4ID, p5.ID as p5ID, p6.ID as p6ID, p7.ID as p7ID, p8.ID as p8ID, p9.ID as p9ID 
                FROM SurfSharekit_Institute p1
                LEFT JOIN   SurfSharekit_Institute p2 on p2.ID = p1.InstituteID 
                LEFT JOIN   SurfSharekit_Institute p3 on p3.ID = p2.InstituteID 
                LEFT JOIN   SurfSharekit_Institute p4 on p4.ID = p3.InstituteID  
                LEFT JOIN   SurfSharekit_Institute p5 on p5.ID = p4.InstituteID  
                LEFT JOIN   SurfSharekit_Institute p6 on p6.ID = p5.InstituteID
                LEFT JOIN   SurfSharekit_Institute p7 ON p7.ID = p6.InstituteID
                LEFT JOIN   SurfSharekit_Institute p8 ON p8.ID = p7.InstituteID
                LEFT JOIN   SurfSharekit_Institute p9 ON p9.ID = p8.InstituteID
                WHERE       p1.ID = $instituteID)",

                'Group.InstituteID = upperScope.p1ID OR 
                 Group.InstituteID = upperScope.p2ID OR
                 Group.InstituteID = upperScope.p3ID OR
                 Group.InstituteID = upperScope.p4ID OR
                 Group.InstituteID = upperScope.p5ID OR
                 Group.InstituteID = upperScope.p6ID OR
                 Group.InstituteID = upperScope.p7ID OR
                 Group.InstituteID = upperScope.p8ID OR
                 Group.InstituteID = upperScope.p9ID'
                ,

                'upperScope'
            );
        } else {
            $groups = $this->dataObj()->Groups();
        }
        return $groups;
    }

    public function isSelf($member) {
        return $this->dataObj()->ID == $member->ID;
    }

    public function isOther($member) {
        return !$this->isSelf($member);
    }

    /**
     * @param $code
     * @param string $arg
     * @param null $member
     * @param array $context
     * Gateway to Permission::check, allows for filters such as permissions based on group context
     */
    private function checkPermission($code, $arg = "any", $member = null, $context = []) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            $institutesOfScope = $this->owner->getRelatedInstitute() ?? [];
            foreach ($institutesOfScope as $instituteOfScope) {
                $scopeLevel = InstituteScoper::getScopeLevel($instituteOfScope, $group->InstituteID);
                if ($scopeLevel != InstituteScoper::UNKNOWN) {
                    $permissionsCodesInGroup = ScopeCache::getPermissionsFromRequestCache($group);
                    return in_array($code, $permissionsCodesInGroup);
                }
            }
            return false;
        }
        return Permission::check($code, $arg, $member);
    }

    public function canRemoveGroup($group) {
        if ($this->owner->Groups()->where("ID != $group->ID")->count() > 0) {
            return true;
        }
        throw new Exception('Cannot remove last group from member');
    }

    static function getPermissionCases() {
        $member = Security::getCurrentUser();

        return [
            "PERSON_VIEW_SELF" => "Member.ID = $member->ID",
            "PERSON_VIEW_OTHER" => "Member.ID != $member->ID"
        ];
    }

    public function allPermissionRoles(): DataList {
        $groupIds = $this->owner->Groups()->column();
        $queryString = $groupIds ? ('' . implode(',', $groupIds)) : '-1';
        return PermissionRole::get()
            ->innerJoin("Group_Roles", "Group_Roles.PermissionRoleID = PermissionRole.ID")
            ->innerJoin("Group", "Group.ID = Group_Roles.GroupID")
            ->where([
                "Group.ID IN ($queryString)"
            ]);
    }

    public function getHighestInstituteRole(Institute $institute) {
        $groupIds = $this->owner->Groups()->filter('Institute.ID', $institute->ID)->column();

        if (count($groupIds) == 0) {
            return $this->getHighestInstituteRole($institute->Institute());
        }

        $roles = PermissionRole::get()
            ->innerJoin("Group_Roles", "Group_Roles.PermissionRoleID = PermissionRole.ID")
            ->innerJoin("Group", "Group.ID = Group_Roles.GroupID")
            ->where([
                "Group.ID IN (" . implode(',', $groupIds) . ")"
            ])->column('Title');

        $sortedRoles = Arr::sort($roles, function($role) {
            $index = array_search($role, RoleConstant::ROLE_SORT);
            if ($index === false) {
                return count(RoleConstant::ROLE_SORT) + 100;
            }

            return $index;
        });

        // reset keys
        $sortedRoles = array_values($sortedRoles);

        return $sortedRoles[0] ?? null;
    }
}