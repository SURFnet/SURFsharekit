<?php

namespace SurfSharekit\Models\Helper;


use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionRole;
use SilverStripe\Security\PermissionRoleCode;

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
            Constants::TITLE_OF_STUDENT_ROLE,
            Constants::TITLE_OF_SUPPORTER_ROLE,
            Constants::TITLE_OF_SITEADMIN_ROLE,
            Constants::TITLE_OF_MEMBER_ROLE,
            Constants::TITLE_OF_STAFF_ROLE,
            Constants::TITLE_OF_WORKSADMIN_ROLE,
            Constants::TITLE_OF_APIUSER_ROLE
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
        $worksadminGroup = Group::get()->filter(['Title' => Constants::TITLE_OF_WORKSADMIN_GROUP])->first();
        if(!($worksadminGroup && $worksadminGroup->exists())){
            $worksadminGroup = Group::create();
            $worksadminGroup->setField('Title', Constants::TITLE_OF_WORKSADMIN_GROUP);
            $worksadminGroup->setField('Code', Constants::CODE_OF_WORKSADMIN_GROUP);
            $worksadminGroup->write();
        }

        // API group
        $apiGroup = Group::get()->filter(['Title' => Constants::TITLE_OF_API_GROUP])->first();
        if(!($apiGroup && $apiGroup->exists())){
            $apiGroup = Group::create();
            $apiGroup->setField('Title', Constants::TITLE_OF_API_GROUP);
            $apiGroup->setField('Code', Constants::CODE_OF_API_GROUP);
            $apiGroup->write();
        }
    }
}