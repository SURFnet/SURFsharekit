<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\ValidationException;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\models\tasks\TaskCompleter;
use SurfSharekit\models\tasks\TaskRemover;

/**
 * This class handles all actions performed on Tasks with Type 'CLAIM'
 */
class ClaimTypeTaskActionHandler extends TaskActionHandler {

    /**
     * @param $task
     * @return void
     * @throws ValidationException
     */
    public static function run($task) {
        switch ($task->Action) {
            case Constants::TASK_ACTION_APPROVE: {
                self::handleApproveAction($task);
                break;
            }
            case Constants::TASK_ACTION_DECLINE: {
                self::handleDeclineAction($task);
                break;
            }
        }
    }

    /**
     * @param $task
     * @return void
     * @throws ValidationException
     */
    private static function handleApproveAction($task) {
        $claim = Claim::get()->byID($task->ClaimID);
        if($claim) {
            $claim->Status = "Approved";
            $claim->write();
            $task->State = Constants::TASK_STATE_DONE;
            TaskCompleter::getInstance()->completeTasksByAssociationUuid($task, Constants::TASK_ACTION_APPROVE);
            TaskRemover::getInstance()->deleteUncompletedTasksByType($claim, Constants::TASK_TYPE_CLAIM);
        } else {
            self::deleteTaskAndAllAssociatedTasks($task);
        }
    }

    /**
     * @param $task
     * @return void
     * @throws ValidationException
     */
    private static function handleDeclineAction($task) {
        $claim = Claim::get()->byID($task->ClaimID);
        if($claim) {
            $claim->Status = "Declined";
            $claim->ReasonOfDecline = $task->ReasonOfDecline;
            $claim->write();
            $task->State = Constants::TASK_STATE_DONE;
            TaskCompleter::getInstance()->completeTasksByAssociationUuid($task, Constants::TASK_ACTION_DECLINE);
            TaskRemover::getInstance()->deleteUncompletedTasksByType($claim, Constants::TASK_TYPE_CLAIM);
        } else {
            self::deleteTaskAndAllAssociatedTasks($task);
        }
    }
}