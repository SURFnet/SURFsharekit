<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionRole;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\PermissionRoleExtension;

class MigratePermissions032026 extends BuildTask {
    protected $description = "Adds subroles to groups where the corresponding main role is already linked. See story #1536 for more information";

    public function run($request) {
        $newRoleKeys = [RoleConstant::LMS_CONNECTOR];

        $toMigrateSubRoles = [
            RoleConstant::THESIS_UPLOADER => [RoleConstant::STAFF, RoleConstant::STUDENT, RoleConstant::SUPPORTER, RoleConstant::SITEADMIN],
            RoleConstant::RESEARCH_UPLOADER => [RoleConstant::STAFF, RoleConstant::SUPPORTER, RoleConstant::SITEADMIN],
            RoleConstant::LEARNING_MATERIAL_UPLOADER => [RoleConstant::STAFF, RoleConstant::SUPPORTER, RoleConstant::SITEADMIN],
            RoleConstant::REPO_ITEM_ADMIN => [RoleConstant::SUPPORTER, RoleConstant::SITEADMIN],
            RoleConstant::ARCHIVE_ADMIN => [RoleConstant::SITEADMIN],
            RoleConstant::MEMBER_MANAGER => [RoleConstant::SITEADMIN],
            RoleConstant::MEMBER_EDITOR => [RoleConstant::SUPPORTER],
            RoleConstant::INSTITUTE_MANAGER => [RoleConstant::SITEADMIN],
            RoleConstant::UPLOAD_API_USER => [] // Still a proposal
        ];

        $this->print("Creating new roles\n");
        foreach ($newRoleKeys as $subRole) {
            $this->print("Creating new role $subRole\n");
            if (PermissionRole::get()->find("Key", $subRole) === null) {
                /** @var PermissionRoleExtension $newRole */
                $newRole = PermissionRole::create([
                    "Title" => $subRole,
                    "Key" => $subRole
                ]);
                $newRole->write();
            }
        }

        foreach ($toMigrateSubRoles as $subRole => $mainRoles) {
            $this->print("Migrating subrole $subRole\n");

            if (!$subRoleDataObject = PermissionRole::get()->filter(["Key" => $subRole])->first()) {
                $this->print("Subrole with key: $subRole not found, skip to next subRole iteration\n");
                continue;
            }

            foreach ($mainRoles as $mainRole) {
                $this->print("Migrating subrole $subRole to main role $mainRole\n");

                $groups = Group::get()
                    ->leftJoin("Group_Roles", "Group.ID = GR.GroupID", "GR")
                    ->leftJoin("PermissionRole", "GR.PermissionRoleID = PR.ID", "PR")
                    ->where(["PR.Key" => $mainRole]);

                if ($groups->count() <= 0) {
                    $this->print("No groups found for main role $mainRole, skip to next mainRole iteration\n");
                    continue;
                }

                $this->print("Found " . $groups->count() . " groups\n");
                /** @var Group $group */
                foreach ($groups as $group) {
                    $alreadyLinked = $group->Roles()->filter('ID', $subRoleDataObject->ID)->exists();

                    // Should not add to group if link already exists
                    if ($alreadyLinked) {
                        continue;
                    }

                    $group->Roles()->add($subRoleDataObject);
                }
            }
        }
    }

    private function print($message) {
        if (Director::is_cli()) {
            echo $message;
        } else {
            echo "<span>$message</span><br>";
        }
    }
}