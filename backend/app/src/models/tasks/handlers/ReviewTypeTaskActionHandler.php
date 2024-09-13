<?php

namespace SurfSharekit\Models;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\models\tasks\TaskCompleter;
use SurfSharekit\models\tasks\TaskRemover;

/**
 * This class handles all actions performed on Tasks with Type 'REVIEW'
*/
class ReviewTypeTaskActionHandler extends TaskActionHandler {

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
        $repoItem->Status = "Approved";
        $repoItem->write();
        $task->State = Constants::TASK_STATE_DONE;

        TaskCompleter::getInstance()->completeTasksByAssociationUuid($task, Constants::TASK_ACTION_APPROVE);
        TaskRemover::getInstance()->deleteUncompletedTasksByType($repoItem, Constants::TASK_TYPE_REVIEW);
    }

    /**
     * @param $task
     * @param $repoItem
     * @return void
     */
    private static function handleDeclineAction($task, $repoItem) {
        $repoItem->Status = "Declined";
        $repoItem->DeclineReason = $task->ReasonOfDecline;
        $repoItem->write();
        $task->State = Constants::TASK_STATE_DONE;

        TaskCompleter::getInstance()->completeTasksByAssociationUuid($task, Constants::TASK_ACTION_DECLINE);
        TaskRemover::getInstance()->deleteUncompletedTasksByType($repoItem, Constants::TASK_TYPE_REVIEW);
    }

    /**
     * @param Task $task
     * @return RepoItem|null
     * @throws TaskNotProcessableException
     * Checks the status of the RepoItem linked to this task. If the status is not 'Submitted' the task is no longer relevant
     * This can happen when changing a submitted RepoItem's status to 'Draft', changing some data, to then publish said RepoItem.
     */
    private static function validateLinkedRepoItem($task) : ?RepoItem {
        /** @var null|RepoItem $repoItem */
        $repoItem = RepoItem::get()->byID($task->RepoItemID);
        if($repoItem && $repoItem->Status == 'Submitted') {
            return $repoItem;
        }
        self::deleteTaskAndAllAssociatedTasks($task);
        throw new TaskNotProcessableException(new RepItemNotSubmittedError());
    }
}