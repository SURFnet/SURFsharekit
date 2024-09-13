<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionRole;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Institute;
use UuidExtension;

class UpdateGroupLabelsTask extends BuildTask {

    public function run($request) {
        set_time_limit(0);

            $changedRoles = PermissionRole::get()->filter(['UpdateGroupLabels' => true]);

            foreach ($changedRoles as $changedRole) {
                $groups = Group::get()->filter(['Roles.ID' => $changedRole->ID]);

                foreach ($groups as $group) {
                    $rolesInGroup = $group->Roles();

                    if($rolesInGroup->count() > 1 && $rolesInGroup->first()->ID == $changedRole->ID) {
                        /*
                         * Ensure to update group labels only when the current Role is the first role that wss added to this group
                         * This applies to groups with more than 1 role. ->first() returns the last added role
                         * */
                        return;
                    }

                    echo("Setting labels for group with ID: $group->ID <br>");
                    if($group->InstituteUuid) {
                        $institute = UuidExtension::getByUuid(Institute::class, $group->InstituteUuid);
                        if($institute && in_array($changedRole->Title, [RoleConstant::MEMBER, RoleConstant::STUDENT, RoleConstant::SUPPORTER, RoleConstant::SITEADMIN, RoleConstant::STAFF])) {
                            $group->Label_NL = $changedRole->Label_NL . ' van ' . $institute->Title;
                            $group->Label_EN = $changedRole->Label_EN . ' of ' . $institute->Title;
                        } else {
                            $group->Label_NL = $changedRole->Label_NL;
                            $group->Label_EN = $changedRole->Label_EN;
                        }
                    } else {
                        $group->Label_NL = $changedRole->Label_NL;
                        $group->Label_EN = $changedRole->Label_EN;
                    }
                    $group->write();
                }

                $changedRole->UpdateGroupLabels = false;
                $changedRole->write();
            }

    }
}