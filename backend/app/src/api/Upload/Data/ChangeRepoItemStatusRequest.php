<?php

namespace SilverStripe\api\Upload\Data;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;
use Throwable;

class ChangeRepoItemStatusRequest {
    use SerializableTrait;

    public string $status;

    public static function fromJson($json): ?ChangeRepoItemStatusRequest {
        try {
            $decodedJson = json_decode($json, true);
            $changeRepoItemStatusRequest = new ChangeRepoItemStatusRequest();
            $changeRepoItemStatusRequest->status = $decodedJson["status"] ?? null;
            return $changeRepoItemStatusRequest;
        } catch (Throwable $e) {
            return null;
        }
    }
}