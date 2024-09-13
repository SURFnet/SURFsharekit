<?php

namespace SurfSharekit\Api\Exceptions;

class ApiError {

    protected string $code;
    protected string $description;
    protected string $message;

    public function __construct(string $code, string $description, string $message) {
        $this->code = $code;
        $this->description = $description;
        $this->message = $message;
    }

    public function getCode(): string {
        return $this->code;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function getDescription(): string {
        return $this->description;
    }

}