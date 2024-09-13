<?php

namespace SilverStripe\api;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataList;
use SurfSharekit\Api\Exceptions\ErrorResponse;

class ResponseHelper {

    private static function getJsonResponse(int $statusCode, $body = null): HTTPResponse {
        $response = new HTTPResponse();
        $response->addHeader('content-type', 'application/json');
        $response->setStatusCode($statusCode);
        if ($body || is_array($body)) {
            $response->setBody(is_array($body) ? json_encode($body) : $body);
        }
        return $response;
    }

    public static function errorResponse(string $errorCode, string $description, string $message): ErrorResponse {
        $errorResponse = new ErrorResponse();
        $errorResponse->code = $errorCode;
        $errorResponse->description = $description;
        $errorResponse->message = $message;
        return $errorResponse;
    }

    public static function responseSuccess($body = ""): HTTPResponse {
        return self::getJsonResponse(200, $body);
    }

    public static function responseList(array $list): HTTPResponse {
        return self::responseSuccess([
            "meta" => [
               "count" => count($list)
            ],
            "data" => $list
        ]);
    }

    public static function responseDataList(DataList $list, \Closure $map): HTTPResponse {
        $mappedList = [];

        foreach ($list as $item) {
            if (null !== $mappedItem = $map($item)) {
                $mappedList[] = $mappedItem;
            }
        }

        return self::responseSuccess([
            "meta" => [
                "count" => count($list)
            ],
            "data" => $mappedList
        ]);
    }

    public static function responsePaginatedDataList(HTTPRequest $request, DataList $list, \Closure $map, $defaultPageSize = 100): HTTPResponse {
        $pageSize = (int)$request->getVar('pageSize') ?? $defaultPageSize;
        $pageNumber = (int)$request->getVar('pageNumber') ?? 1;

        $count = $list->count();
        $items = $list->limit($pageSize, ($pageNumber - 1) * $pageSize);

        $mappedList = [];

        foreach ($items as $item) {
            if (null !== $mappedItem = $map($item)) {
                $mappedList[] = $mappedItem;
            }
        }

        return self::responseSuccess([
            "meta" => [
                "total" => $count,
                "count" => count($items),
                "pageNumber" => $pageNumber,
                "pageSize" => $pageSize
            ],
            "data" => $mappedList
        ]);
    }

    public static function responseCreated($body = "") {
        return self::getJsonResponse(201, $body);
    }

    public static function responseNotModified($statusCode = 304) {
        return self::getJsonResponse(304);
    }

    public static function responseBadRequest(ErrorResponse $errorResponse) {
        return self::getJsonResponse(400, json_encode($errorResponse));
    }

    public static function responseNotFound(ErrorResponse $errorResponse) {
        return self::getJsonResponse(404, json_encode($errorResponse));
    }

    public static function responseUnauthorized(ErrorResponse $errorResponse) {
        return self::getJsonResponse(401, json_encode($errorResponse));
    }

    public static function responseForbidden(ErrorResponse $errorResponse) {
        return self::getJsonResponse(403, json_encode($errorResponse));
    }

    public static function responseMethodNotAllowed(ErrorResponse $errorResponse) {
        return self::getJsonResponse(405, json_encode($errorResponse));
    }

    public static function responsePayloadTooLarge(ErrorResponse $errorResponse) {
        return self::getJsonResponse(413, json_encode($errorResponse));
    }

    public static function responseInternalServerError(ErrorResponse $errorResponse) {
        return self::getJsonResponse(500, json_encode($errorResponse));
    }

    public static function responseNotImplemented(ErrorResponse $errorResponse) {
        return self::getJsonResponse(501, json_encode($errorResponse));
    }
}