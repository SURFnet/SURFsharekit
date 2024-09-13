<?php

namespace SurfSharekit\Api\Exceptions;

use Exception;

class ApiException extends Exception {
    protected ApiError $apiError;

    public function __construct(array $apiErrorConstant, ?string $customMessage = null) {
        parent::__construct();
        $code = $apiErrorConstant["code"];
        $message = $customMessage ?: $apiErrorConstant["message"];
        $description = $apiErrorConstant["description"];

        $apiError = new ApiError($code, $description, $message);
        $this->apiError = $apiError;
    }

    public function getApiError(): ApiError {
        return $this->apiError;
    }

    public function getApiErrorCode(): ApiError {
        return $this->apiError;
    }
}