<?php

namespace SilverStripe\api;

use Exception;
use SilverStripe\api\Exceptions\InternalServerErrorException;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DB;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\ForbiddenException;
use SurfSharekit\Api\Exceptions\MethodNotAllowedException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Api\Exceptions\NotImplementedException;
use SurfSharekit\Api\Exceptions\PayloadTooLargeException;
use SurfSharekit\Api\Exceptions\UnauthorizedException;
use SurfSharekit\Api\LoginProtectedApiController;
use SurfSharekit\Models\Helper\AfterCommit;
use SurfSharekit\Models\Helper\Logger;
use Throwable;

class BaseController extends LoginProtectedApiController {
    public function index() {
        throw new NotFoundException(ApiErrorConstant::GA_NF_001);
    }

    public function handleRequest(HTTPRequest $request): HTTPResponse {
        try {
            return parent::handleRequest($request);
        } catch (BadRequestException $e) {
            Logger::debugLog($e->getMessage());
            $errorResponse = ResponseHelper::errorResponse($e->getApiError()->getCode(), $e->getApiError()->getDescription(), $e->getApiError()->getMessage());
            return ResponseHelper::responseBadRequest($errorResponse);
        } catch (NotFoundException $e) {
            Logger::debugLog($e->getMessage());
            $errorResponse = ResponseHelper::errorResponse($e->getApiError()->getCode(), $e->getApiError()->getDescription(), $e->getApiError()->getMessage());
            return ResponseHelper::responseNotFound($errorResponse);
        } catch (ForbiddenException $e) {
            Logger::debugLog($e->getMessage());
            $errorResponse = ResponseHelper::errorResponse($e->getApiError()->getCode(), $e->getApiError()->getDescription(), $e->getApiError()->getMessage());
            return ResponseHelper::responseForbidden($errorResponse);
        } catch (UnauthorizedException $e) {
            Logger::debugLog($e->getMessage());
            $errorResponse = ResponseHelper::errorResponse($e->getApiError()->getCode(), $e->getApiError()->getDescription(), $e->getApiError()->getMessage());
            return ResponseHelper::responseUnauthorized($errorResponse);
        } catch (MethodNotAllowedException $e) {
            Logger::debugLog($e->getMessage());
            $errorResponse = ResponseHelper::errorResponse($e->getApiError()->getCode(), $e->getApiError()->getDescription(), $e->getApiError()->getMessage());
            return ResponseHelper::responseMethodNotAllowed($errorResponse);
        } catch (NotImplementedException $e) {
            Logger::debugLog($e->getMessage());
            $errorResponse = ResponseHelper::errorResponse($e->getApiError()->getCode(), $e->getApiError()->getDescription(), $e->getApiError()->getMessage());
            return ResponseHelper::responseNotImplemented($errorResponse);
        } catch (PayloadTooLargeException $e) {
            Logger::debugLog($e->getMessage());
            $errorResponse = ResponseHelper::errorResponse($e->getApiError()->getCode(), $e->getApiError()->getDescription(), $e->getApiError()->getMessage());
            return ResponseHelper::responsePayloadTooLarge($errorResponse);
        } catch (InternalServerErrorException $e) {
            Logger::errorLog($e->getMessage());
            $errorResponse = ResponseHelper::errorResponse($e->getApiError()->getCode(), $e->getApiError()->getDescription(), $e->getApiError()->getMessage());
            return ResponseHelper::responseInternalServerError($errorResponse);
        } catch (Exception $e) {
            Logger::errorLog($e->getMessage());
            if (method_exists($e, "getResponse") && $e->getResponse()->getStatusCode() == 404) {
                $errorResponse = ResponseHelper::errorResponse(ApiErrorConstant::GA_NF_001["code"], ApiErrorConstant::GA_NF_001["description"], ApiErrorConstant::GA_NF_001["message"]);
                return ResponseHelper::responseBadRequest($errorResponse);
            } else {
                if (Director::isDev()) {
                    $message = $e->getMessage();
                } else {
                    $message = ApiErrorConstant::GA_ISE_001["message"];
                }

                $errorResponse = ResponseHelper::errorResponse(ApiErrorConstant::GA_ISE_001["code"], ApiErrorConstant::GA_ISE_001["description"], $message);
                return ResponseHelper::responseInternalServerError($errorResponse);
            }
        } finally {
            try {
                DB::get_conn()->transactionRollback();
                AfterCommit::clear();
            } catch (Throwable $e) {
                // Probably not in a transaction, that's fine
            }
        }
    }
}