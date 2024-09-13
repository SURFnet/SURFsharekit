<?php

namespace SurfSharekit\models\tasks;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\Claim;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\Task;

class TaskRemover {

    private static $instance = null;

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new TaskRemover();
        }

        return self::$instance;
    }
    public function deleteUncompletedTasksByType(DataObject $object, $taskType) {
        switch (true) {
            case $object instanceof RepoItem: {
                self::deleteUncompletedTasksForRepoItemByTaskType($object, $taskType);
                break;
            }
            case $object instanceof Claim: {
                self::deleteUncompletedTasksForClaimByTaskType($object, $taskType);
                break;
            }
        }
    }

    /**
     * @param RepoItem $repoItem
     * @param $taskType
     * @return void
     */
    public function deleteUncompletedTasksForRepoItemByTaskType(RepoItem $repoItem, $taskType) {
        DB::query("
            DELETE FROM SurfSharekit_Task
            WHERE RepoItemID = $repoItem->ID AND State = 'INITIAL' AND Type = '$taskType'
        ");
    }

    /**
     * @param Claim $claim
     * @param $taskType
     * @return void
     */
    public function deleteUncompletedTasksForClaimByTaskType(Claim $claim, $taskType) {
        DB::query("
            DELETE FROM SurfSharekit_Task
            WHERE ClaimID = $claim->ID AND State = 'INITIAL' AND Type = '$taskType'
        ");
    }

    /**
     * @param String $associationUuid
     * @param array|null $taskIDsToExclude
     * Deletes all tasks with the given associationUuid, this Uuid is unique for a batch of task objects
     * which were generated at the same time for different people.
     */
    public function deleteTasksByAssociationUuid(String $associationUuid, Array $taskIDsToExclude = null) {
        if($associationUuid) {
            $tasksToRemove = Task::get()->filter([
                "AssociationUuid" => $associationUuid,
                "ID:not" => $taskIDsToExclude
            ])->getIDList();

            $tasksToRemoveFilterString = $tasksToRemove ? ('' . implode(',', $tasksToRemove)) : '-1';
            DB::query("DELETE FROM SurfSharekit_Task WHERE SurfSharekit_Task.ID IN ($tasksToRemoveFilterString)");
        }
    }


}