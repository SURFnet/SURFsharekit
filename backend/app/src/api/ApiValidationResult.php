<?php

namespace SilverStripe\api;

use SilverStripe\Core\Injector\Injectable;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;

class ApiValidationResult
{
    use Injectable;

    private array $errors = [];

    public function setErrors($errors): self {
        $this->errors = $errors;

        return $this;
    }

    public function throwError($errorDescription): void {
        throw new BadRequestException(ApiErrorConstant::GA_BR_001, $errorDescription);
    }

    public function addError($error): self {
        $this->errors[] = $error;

        return $this;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function hasErrors(): bool {
        return count($this->errors) > 0;
    }

}