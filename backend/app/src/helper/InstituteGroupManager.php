<?php

namespace SurfSharekit\helper;

use Exception;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionRole;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Institute;

/**
 * Class to manage the default groups created when a new Institute is created
 * It makes sure all the required groups are present and are assigned the correct default roles
 */
class InstituteGroupManager {

    /**
     * @param Institute $institute
     * @return void
     * @throws ValidationException
     * Creates default groups for an Institute, these groups should always be present for every Institute
     */
    public static function createDefaultGroups(Institute $institute): void {
        // join to improve performance
        $defaultRoles = $institute->Groups()
            ->innerJoin("Group_Roles", "gr.GroupID = Group.ID", "gr")
            ->innerJoin("PermissionRole", "pr.ID = gr.PermissionRoleID", "pr")
            ->where(["pr.IsDefault" => true])
            ->column("pr.Key");

        foreach (RoleConstant::DEFAULT_INSTITUTE_ROLES as $defaultRole) {
            $groupDoesNotExistForRole = !in_array($defaultRole, $defaultRoles);
            if ($groupDoesNotExistForRole) {
                switch ($defaultRole) {
                    case RoleConstant::MEMBER: self::createDefaultRoleGroup($institute, $defaultRole, "Leden van "); break;
                    case RoleConstant::STUDENT: self::createDefaultRoleGroup($institute, $defaultRole, "Studenten van "); break;
                    case RoleConstant::STAFF: self::createDefaultRoleGroup($institute, $defaultRole, "Medewerkers van "); break;
                    case RoleConstant::SUPPORTER: self::createDefaultRoleGroup($institute, $defaultRole, "Ondersteuners van "); break;
                    case RoleConstant::SITEADMIN: self::createDefaultRoleGroup($institute, $defaultRole, "Siteadmins van "); break;
                    case RoleConstant::UPLOAD_API_USER: {
                        if ($institute->isRootInstitute()) {
                            self::createDefaultRoleGroup($institute, $defaultRole, "Upload API gebruikers van ");
                        }
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param Institute $institute
     * @param string $roleKey
     * @param string $groupTitlePrefix
     * @return void
     * @throws ValidationException
     */
    private static function createDefaultRoleGroup(Institute $institute, string $roleKey, string $groupTitlePrefix): void {
        $role = PermissionRole::get()->filter('Key', $roleKey)->first();
        if ($role) {
            $newGroup = Group::create();
            $newGroup->Title = $groupTitlePrefix . $institute->Title;
            $newGroup->Label_NL = $role->Label_NL . ' van ' . $institute->Title;
            $newGroup->Label_EN = $role->Label_EN . ' of ' . $institute->Title;
            $newGroup->InstituteID = $institute->ID;
            $newGroup->DefaultRoleID = $role->ID;
            self::addDefaultRolesToGroup($newGroup, $role);
            $newGroup->write();
        }
    }

    /**
     * @param Group $group
     * @param PermissionRole $mainRole
     * @return void
     * @throws Exception Adds all default roles to a particular group of an Institute
     */
    private static function addDefaultRolesToGroup(Group $group, PermissionRole $mainRole): void {
        $group->Roles()->Add($mainRole);
        $additionalRoles = RoleConstant::MAIN_TO_SUB_ROLE_MAP[$mainRole->Key] ?? [];
        foreach ($additionalRoles as $additionalRole) {
            $role = PermissionRole::get()->filter('Key', $additionalRole)->first();
            if ($role) {
                $group->Roles()->add($role);
            }
        }
    }


}