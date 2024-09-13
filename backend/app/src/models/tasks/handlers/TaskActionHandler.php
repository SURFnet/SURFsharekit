<?php

namespace SurfSharekit\Models;

use SilverStripe\Security\Security;
use SurfSharekit\models\tasks\TaskRemover;

/**
 * Abstract class that must be extended when creating a handler for a particular type of Task
*/
abstract class TaskActionHandler {

    /**
     * @param Task $task
     * @return mixed
     */
    abstract public static function run(Task $task);

    /**
     * @param Task $task
     * @return void
     */
    protected static function deleteTaskAndAllAssociatedTasks(Task $task) {
        $task->IsMarkedForDeletion = true;
        if ($task->AssociationUuid) {
            TaskRemover::getInstance()->deleteTasksByAssociationUuid($task->AssociationUuid, [$task->ID]);
        } else {
            $task->delete();
        }
    }
}