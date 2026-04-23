<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\Helper\Logger;

class RemoveInactiveUsersTask extends BuildTask {
    protected $title = "Remove inactive users";
    protected $description = "Task to remove all users who are not linked to a RepoItem. As owner or as author";

    private bool $dryRun = true;

    public function run($request) {
        if ($request->getVar('dryRun') !== null) {
            $this->dryRun = !!$request->getVar('dryRun');
        }

        echo "Dry run: " . ($this->dryRun ? "enabled" : "disabled") . "<br />";
        echo "Starting removal of inactive users...<br />";

        $memberIdsToRemove = $this->getAllInActiveMembers();

        if (empty($memberIdsToRemove)) {
            echo "No inactive users found.<br />";
            return;
        }

        echo "Found " . count($memberIdsToRemove) . " inactive users that need to be removed. <br />";

        $chunkedArray = array_chunk($memberIdsToRemove, 100);
        foreach ($chunkedArray as $chunk) {
            $idsToRemove = implode(',', array_map('intval', $chunk));

            echo "Removing members with ids: $idsToRemove...<br />";
            Logger::debugLog("Removing members with ids: $idsToRemove...");

            if (!$this->dryRun) {
                DB::query("
                        UPDATE Member
                        SET IsRemoved = 1
                        WHERE ID IN ($idsToRemove)
                    ");
            }
        }

        if (!$this->dryRun) {
            echo "Dry run disabled, deleting inactive members...<br />";
        } else {
            echo "Dry run enabled, no changes were made.<br />";
        }
    }

    private function getAllInActiveMembers(): array {
        return DB::query("
            SELECT Member.ID
            FROM SurfSharekit_Person
            INNER JOIN Member ON SurfSharekit_Person.ID = Member.ID
            WHERE Member.LastEdited < DATE_SUB(NOW(), INTERVAL 3 MONTH)
              AND Member.IsRemoved = 0
              AND SurfSharekit_Person.HasLoggedIn = 0
              AND NOT EXISTS (
                SELECT 1
                FROM SurfSharekit_RepoItem as rep1
                     INNER JOIN SurfSharekit_RepoItemMetaField as repmet1 ON repmet1.RepoItemID = rep1.ID
                     INNER JOIN SurfSharekit_MetaField as met1 ON repmet1.MetaFieldID = met1.ID
                     INNER JOIN SurfSharekit_MetaFieldType as mett1
                                ON met1.MetaFieldTypeID = mett1.ID AND mett1.Key = 'PersonInvolved'
                     INNER JOIN SurfSharekit_RepoItemMetaFieldValue as repmetfv1 ON repmetfv1.RepoItemMetaFieldID = repmet1.ID
                     INNER JOIN SurfSharekit_RepoItem as rep2 ON rep2.ID = repmetfv1.RepoItemID
                     INNER JOIN SurfSharekit_RepoItemMetaField as repmet2 ON repmet2.RepoItemID = rep2.ID
                     INNER JOIN SurfSharekit_MetaField as met2 ON repmet2.MetaFieldID = met2.ID
                     INNER JOIN SurfSharekit_RepoItemMetaFieldValue as repmetfv2 ON repmetfv2.RepoItemMetaFieldID = repmet2.ID
                WHERE rep1.IsRemoved = 0
                  AND rep2.IsRemoved = 0
                  AND repmetfv1.IsRemoved = 0
                  AND repmetfv1.IsRemoved = 0
                  AND repmetfv2.PersonID = Member.ID
              )
              AND NOT EXISTS (
                SELECT 1
                FROM SurfSharekit_RepoItem
                WHERE IsRemoved = 0
                  AND OwnerID = Member.ID
              );
        ")->column();
    }
}