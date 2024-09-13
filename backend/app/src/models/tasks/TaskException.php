<?php

namespace SurfSharekit\Models;

use Exception;
use Throwable;

class TaskError {
    public $message;
    public $code;
}

class RepItemNotSubmittedError extends TaskError {

    public function __construct() {
        $this->message = "Can not perform action, the publication no longer has the 'Submitted' status";
        $this->code = 1;
    }
}

class TaskNotProcessableException extends Exception {
    public function __construct(TaskError $taskError, Throwable $previous = null) {
        parent::__construct($taskError->message, $taskError->code, $previous);
    }
}