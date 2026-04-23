<?php

namespace SurfSharekit\models\tasks;

use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Task;

class TaskCompleter {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new TaskCompleter();
        }

        return self::$instance;
    }

    public function completeTasksByAssociationUuid(Task $task, string $action) {
        $completedBy = Security::getCurrentUser();
        DB::query("
            UPDATE SurfSharekit_Task SET State = 'DONE', Action = '$action', CompletedByID = $completedBy->ID, CompletedByUuid = '$completedBy->Uuid'
            WHERE AssociationUuid = '$task->AssociationUuid'
        ");
    }

    public function completeTasksByTypeForRepoItem(string $taskType, int $repoItemID, string $action) {
        $completedBy = Security::getCurrentUser();
        DB::prepared_query("
            UPDATE SurfSharekit_Task SET State = 'DONE', Action = ?, CompletedByID = ?, CompletedByUuid = ?
            WHERE RepoItemID = ? AND Type = ? AND State = 'INITIAL'
        ", [$action, $completedBy->ID, $completedBy->Uuid, $repoItemID, $taskType]);
    }
}