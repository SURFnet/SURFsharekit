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
}