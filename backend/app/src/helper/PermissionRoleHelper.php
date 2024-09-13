<?php

namespace SurfSharekit\Models\Helper;


use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionRole;
use SilverStripe\Security\PermissionRoleCode;
use SurfSharekit\constants\GroupConstant;
use SurfSharekit\constants\RoleConstant;

class PermissionRoleHelper {

    /**
     * Default and necessary permissions:
     * Access to 'Security' section (CMS_ACCESS_SecurityAdmin)
     * View their own Groups (GROUP_VIEW_OWN)
     * View their own Institute (MEMBER_VIEW_SELF)
     * View themselves (INSTITUTE_VIEW_SAMELEVEL)
     * With these permissions enabled, a user can always view it's own profile data in the frontend, which is required by design.
     **/

    static function createDefaultRolePermissions() {

        $defaultPermissionCodes = [
            'CMS_ACCESS_SecurityAdmin',
            'GROUP_VIEW_OWN',
            'MEMBER_VIEW_SELF',
            'INSTITUTE_VIEW_SAMELEVEL'
        ];

        $defaultPermissionRoleCodes = [];
        foreach ($defaultPermissionCodes as $permissionCode) {
            $permissionRoleCode = new PermissionRoleCode();
            $permissionRoleCode->setField('Code', $permissionCode);
            $permissionRoleCode->write();
            $defaultPermissionRoleCodes[] = $permissionRoleCode;
        }

        return $defaultPermissionRoleCodes;
    }

    static function addDefaultPermissionRoles() {
        $defaultPermissionRoles = [
            RoleConstant::STUDENT,
            RoleConstant::SUPPORTER,
            RoleConstant::SITEADMIN,
            RoleConstant::MEMBER,
            RoleConstant::STAFF,
            RoleConstant::WORKSADMIN,
            RoleConstant::APIUSER
        ];

        foreach ($defaultPermissionRoles as $permissionRoleTitle) {
            if(!PermissionRole::get()->filter('Title', $permissionRoleTitle)->first()) {
                $defaultPermissionRole = new PermissionRole();
                $defaultPermissionRole->setField('Title', $permissionRoleTitle);
                $defaultPermissionRole->setField('OnlyAdminCanApply', 0);
                $defaultPermissionRole->write();

                $defaultPermissionRoleCodes = PermissionRoleHelper::createDefaultRolePermissions();
                $defaultPermissionRole->Codes()->addMany($defaultPermissionRoleCodes);
                $defaultPermissionRole->write();
            }
        }

        // Works admins group
        $worksadminGroup = Group::get()->filter(['Title' => GroupConstant::WORKSADMIN_TITLE])->first();
        if(!($worksadminGroup && $worksadminGroup->exists())){
            $worksadminGroup = Group::create();
            $worksadminGroup->setField('Title', GroupConstant::WORKSADMIN_TITLE);
            $worksadminGroup->setField('Code', GroupConstant::WORKSADMIN_CODE);
            $worksadminGroup->write();
        }

        // API group
        $apiGroup = Group::get()->filter(['Title' => GroupConstant::API_TITLE])->first();
        if(!($apiGroup && $apiGroup->exists())){
            $apiGroup = Group::create();
            $apiGroup->setField('Title', GroupConstant::API_TITLE);
            $apiGroup->setField('Code', GroupConstant::API_CODE);
            $apiGroup->write();
        }
    }
}