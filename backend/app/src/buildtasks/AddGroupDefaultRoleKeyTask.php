<?php

namespace SurfSharekit\buildtasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionRole;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\helper\InstituteGroupManager;
use SurfSharekit\Models\Institute;

class AddGroupDefaultRoleKeyTask extends BuildTask {

    protected $title = 'Add default role keys to groups';
    protected $description = 'This task adds default role keys for each group. This establishes a link between role and group. This is a one-time use migration task';


    public function run($request) {
        set_time_limit(0);

        $this->addKeyToDefaultRoles();

        $this->migrateMemberGroups();
        $this->migrateStudentGroups();
        $this->migrateStaffGroups();
        $this->migrateSupporterGroups();
        $this->migrateSiteAdminGroups();
        $this->migrateExternalApiUserGroups();
        $this->migrateWorkAdminGroups();

    }

    private function migrateSiteAdminGroups() {
        $siteAdminGroups = Group::get()->filter([
            "Code:PartialMatch" => ["siteadmins", "sitemanager"],
            "DefaultRoleID" => 0
        ]);

        $roleKeys = RoleConstant::MAIN_TO_SUB_ROLE_MAP[RoleConstant::SITEADMIN];
        $roleKeys[] = RoleConstant::SITEADMIN;
        $mainRole = PermissionRole::get()->filter(["Key" => RoleConstant::SITEADMIN])->first();
        $allDefaultRoles = PermissionRole::get()->filter(["Key" => $roleKeys])->column();

        /** @var Group $siteAdminGroup */
        foreach ($siteAdminGroups as $siteAdminGroup) {
            // Ensure the default role is added to the group
            $siteAdminGroup->Roles()->addMany($allDefaultRoles);
            $siteAdminGroup->DefaultRoleID = $mainRole->ID;
            $siteAdminGroup->write();
        }
    }

    private function migrateSupporterGroups() {
        $supporterGroups = Group::get()->filter([
            "Code:PartialMatch" => "ondersteuners",
            "DefaultRoleID" => 0
        ]);

        $roleKeys = RoleConstant::MAIN_TO_SUB_ROLE_MAP[RoleConstant::SUPPORTER];
        $roleKeys[] = RoleConstant::SUPPORTER;
        $mainRole = PermissionRole::get()->filter(["Key" => RoleConstant::SUPPORTER])->first();
        $allDefaultRoles = PermissionRole::get()->filter(["Key" => $roleKeys])->column();

        /** @var Group $supporterGroup */
        foreach ($supporterGroups as $supporterGroup) {
            // Ensure the default role is added to the group
            $supporterGroup->Roles()->addMany($allDefaultRoles);
            $supporterGroup->DefaultRoleID = $mainRole->ID;
            $supporterGroup->write();
        }
    }
    private function migrateStaffGroups() {
        $staffGroups = Group::get()->filter([
            "Code:PartialMatch" => "medewerkers",
            "DefaultRoleID" => 0
        ]);

        $roleKeys = RoleConstant::MAIN_TO_SUB_ROLE_MAP[RoleConstant::STAFF];
        $roleKeys[] = RoleConstant::STAFF;
        $mainRole = PermissionRole::get()->filter(["Key" => RoleConstant::STAFF])->first();
        $allDefaultRoles = PermissionRole::get()->filter(["Key" => $roleKeys])->column();

        /** @var Group $staffGroup */
        foreach ($staffGroups as $staffGroup) {
            // Ensure the default role is added to the group
            $staffGroup->Roles()->addMany($allDefaultRoles);
            $staffGroup->DefaultRoleID = $mainRole->ID;
            $staffGroup->write();
        }
    }

    private function migrateStudentGroups() {
        $studentGroups = Group::get()->filter([
            "Code:PartialMatch" => ["studenten", "student"],
            "DefaultRoleID" => 0
        ]);


        $roleKeys = RoleConstant::MAIN_TO_SUB_ROLE_MAP[RoleConstant::STUDENT];
        $roleKeys[] = RoleConstant::STUDENT;
        $mainRole = PermissionRole::get()->filter(["Key" => RoleConstant::STUDENT])->first();
        $allDefaultRoles = PermissionRole::get()->filter(["Key" => $roleKeys])->column();

        /** @var Group $studentGroup */
        foreach ($studentGroups as $studentGroup) {
            // Ensure the default role is added to the group
            $studentGroup->Roles()->addMany($allDefaultRoles);
            $studentGroup->DefaultRoleID = $mainRole->ID;
            $studentGroup->write();
        }
    }

    private function migrateMemberGroups() {
        $memberGroups = Group::get()->filter([
            "Code:PartialMatch" => "leden",
            "DefaultRoleID" => 0
        ]);

        $roleKeys = RoleConstant::MAIN_TO_SUB_ROLE_MAP[RoleConstant::MEMBER];
        $roleKeys[] = RoleConstant::MEMBER;
        $mainRole = PermissionRole::get()->filter(["Key" => RoleConstant::MEMBER])->first();
        $allDefaultRoles = PermissionRole::get()->filter(["Key" => $roleKeys])->column();

        /** @var Group $memberGroup */
        foreach ($memberGroups as $memberGroup) {
            // Ensure the default role is added to the group
            $memberGroup->Roles()->addMany($allDefaultRoles);
            $memberGroup->DefaultRoleID = $mainRole->ID;
            $memberGroup->write();
        }
    }

    private function migrateExternalApiUserGroups() {
        $apiUserGroups = Group::get()->filter([
            "Code" => "external-api-users",
            "DefaultRoleID" => 0
        ]);

        $role = PermissionRole::get()->filter(["Key" => RoleConstant::APIUSER])->first();

        /** @var Group $apiUserGroup */
        foreach ($apiUserGroups as $apiUserGroup) {
            // Ensure the default role is added to the group
            $apiUserGroup->Roles()->add($role);
            $apiUserGroup->DefaultRoleID = $role->ID;
            $apiUserGroup->write();
        }
    }

    private function migrateWorkAdminGroups() {
        $worksAdminGroups = Group::get()->filter([
            "Code" => "works-admins",
            "DefaultRoleID" => 0
        ]);

        $role = PermissionRole::get()->filter(["Key" => RoleConstant::WORKSADMIN])->first();

        /** @var Group $worksAdminGroup */
        foreach ($worksAdminGroups as $worksAdminGroup) {
            // Ensure the default role is added to the group
            $worksAdminGroup->Roles()->add($role);
            $worksAdminGroup->DefaultRoleID = $role->ID;
            $worksAdminGroup->write();
        }
    }

    private function addKeyToDefaultRoles() {
        foreach (RoleConstant::MAIN_ROLES as $mainRole) {
            $role = PermissionRole::get()->find("Title", $mainRole);
            $role->Key = $mainRole;
            $role->IsDefault = true;
            $role->write();
        }
    }

}