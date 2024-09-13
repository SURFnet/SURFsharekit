<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Task;

class GenerateTaskDataTask extends BuildTask
{
    protected $title = 'Generate task data';
    protected $description = '(re)generates task data json';

    public function run($request) {
        set_time_limit(600);
        $tasks = Task::get();
        $numOfTasks = $tasks->count();
        $updatedTasks = 0;
        foreach ($tasks as $task) {
            try {
                $updatedTasks++;
                $task->generateDataJSON();
                Logger::debugLog('Update tasks with ID = ' . $task->ID . ' ' . $updatedTasks . ' of ' . $numOfTasks);
                // do this with query because validation is failing on Action check...
                DB::prepared_query("UPDATE ". Task::getSchema()->tableName(Task::class) ." SET Data = ? WHERE ID = ?", [
                    $task->Data,
                    $task->ID
                ]);
            } catch (\Exception $exception) {
                // no-op
                echo $task->ID .": " . $exception->getMessage() . "<br>";
            }
        }
    }
}