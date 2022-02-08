<?php

namespace SurfSharekit\Models;

use Exception;
use RelationaryPermissionProviderTrait;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Helper\Constants;

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
        $scopedPermissions = $this->provideRelationaryPermissions(Institute::LOWER_LEVEL, 'members of institutes below their own level', ['EDIT', 'DELETE']);
        $selfPermission = $this->provideRelationaryPermissions(MemberPermisionExtension::SELF, 'themselves', ['VIEW', 'EDIT']);
        $othersPermission = $this->provideRelationaryPermissions(MemberPermisionExtension::OTHER, 'others', ['VIEW']);
        return array_merge($normalPermissions, $scopedPermissions, $selfPermission, $othersPermission);
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
            $institutesOfScope = $this->owner->getRelatedInstitute();
            foreach ($institutesOfScope as $instituteOfScope) {
                $scopeLevel = InstituteScoper::getScopeLevel($instituteOfScope, $group->InstituteID);
                if ($scopeLevel != InstituteScoper::UNKNOWN) {
                    $permissionsCodesInGroup = ScopeCache::getPermissionsFromCache($group);
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
}