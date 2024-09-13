<?php

namespace SurfSharekit\Models;

use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\models\tasks\TaskCompleter;
use SurfSharekit\models\tasks\TaskRemover;

/**
 * This class handles all actions performed on Tasks with Type 'REVIEW'
 */
class FillTypeTaskActionHandler extends TaskActionHandler {

    /**
     * @param $task
     * @return void
     * @throws TaskNotProcessableException
     */
    public static function run($task) {
        $repoItem = self::validateLinkedRepoItem($task);
        switch ($task->Action) {
            case Constants::TASK_ACTION_APPROVE: {
                self::handleApproveAction($task, $repoItem);
                break;
            }
            case Constants::TASK_ACTION_DECLINE: {
                self::handleDeclineAction($task, $repoItem);
                break;
            }
        }
    }

    /**
     * @param $task
     * @param $repoItem
     * @return void
     */
    private static function handleApproveAction($task, $repoItem) {
        // Do nothing
        $task->State = Constants::TASK_STATE_DONE;
        TaskCompleter::getInstance()->completeTasksByAssociationUuid($task, Constants::TASK_ACTION_APPROVE);
        TaskRemover::getInstance()->deleteUncompletedTasksByType($repoItem, Constants::TASK_TYPE_FILL);
    }

    /**
     * @param $task
     * @param $repoItem
     * @return void
     */
    private static function handleDeclineAction($task, $repoItem) {
        // Delete RepoItem
        $repoItem->IsRemoved = true;
        $repoItem->write();
        $task->State = Constants::TASK_STATE_DONE;
        TaskCompleter::getInstance()->completeTasksByAssociationUuid($task, Constants::TASK_ACTION_DECLINE);
        TaskRemover::getInstance()->deleteUncompletedTasksByType($repoItem, Constants::TASK_TYPE_FILL);
    }

    /**
     * @param Task $task
     * @throws TaskNotProcessableException
     * Checks the status of the RepoItem linked to this task. If the status is not 'Submitted' the task is no longer relevant
     * This can happen when changing a submitted RepoItem's status to 'Draft', changing some data, to then publish said RepoItem.
     */
    private static function validateLinkedRepoItem($task) : ?RepoItem {
        /** @var null|RepoItem $repoItem */
        $repoItem = RepoItem::get()->byID($task->RepoItemID);
        if($repoItem) {
            return $repoItem;
        }
        self::deleteTaskAndAllAssociatedTasks($task);
        throw new TaskNotProcessableException(new RepItemNotSubmittedError());
    }

}
