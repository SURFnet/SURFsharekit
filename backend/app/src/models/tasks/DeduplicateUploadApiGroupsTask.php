<?php

namespace SilverStripe\models\tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Group;
use SurfSharekit\constants\RoleConstant;

class DeduplicateUploadApiGroupsTask extends BuildTask {
    protected $title = 'Deduplicate Upload API groups';
    protected $description = 'Merges duplicate Upload API groups per institute and removes the redundant copies.';

    public function run($request) {
        set_time_limit(0);

        $groups = Group::get()
            ->innerJoin('PermissionRole', '"DefaultRole"."ID" = "Group"."DefaultRoleID"', 'DefaultRole')
            ->filter(['DefaultRole.Key' => RoleConstant::UPLOAD_API_USER])
            ->sort('InstituteID ASC, Created ASC, ID ASC');

        $groupedByInstitute = [];
        foreach ($groups as $group) {
            $groupedByInstitute[$group->InstituteID] = $groupedByInstitute[$group->InstituteID] ?? [];
            $groupedByInstitute[$group->InstituteID][] = $group;
        }

        $removedGroups = 0;
        foreach ($groupedByInstitute as $instituteId => $instituteGroups) {
            if (count($instituteGroups) < 2) {
                continue;
            }

            $primaryGroup = $this->selectPrimaryGroup($instituteGroups);

            foreach ($instituteGroups as $duplicateGroup) {
                if ((int) $duplicateGroup->ID === (int) $primaryGroup->ID) {
                    continue;
                }

                if ($duplicateGroup->Members()->exists()) {
                    echo sprintf(
                        "Skipped Upload API group \"%s\" (ID %d) for institute %d because it still has members.\n",
                        $duplicateGroup->Title,
                        $duplicateGroup->ID,
                        $instituteId
                    );
                    continue;
                }

                $title = $duplicateGroup->Title;
                $duplicateId = $duplicateGroup->ID;
                $duplicateGroup->delete();
                $removedGroups++;

                echo sprintf(
                    "Removed duplicate Upload API group \"%s\" (ID %d) for institute %d\n",
                    $title,
                    $duplicateId,
                    $instituteId
                );
            }

            $primaryGroup->removeCachedPermissions();
        }

        if ($removedGroups === 0) {
            echo "No duplicate Upload API groups found.\n";
            return;
        }

        echo sprintf("Removed %d duplicate Upload API groups.\n", $removedGroups);
    }

    protected function selectPrimaryGroup(array $instituteGroups): Group {
        foreach ($instituteGroups as $group) {
            if ($group->Members()->exists()) {
                return $group;
            }
        }

        return reset($instituteGroups);
    }
}
