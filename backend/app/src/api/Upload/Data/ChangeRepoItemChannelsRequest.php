<?php

namespace SilverStripe\api\Upload\Data;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;
use Throwable;

class ChangeRepoItemChannelsRequest {
    use SerializableTrait;

    public array $channels;

    public static function fromJson($json): ?ChangeRepoItemChannelsRequest {
        try {
            $decodedJson = json_decode($json, true);
            $changeRepoItemChannelRequest = new ChangeRepoItemChannelsRequest();
            $changeRepoItemChannelRequest->channels = $decodedJson["channels"] ?? null;
            return $changeRepoItemChannelRequest;
        } catch (Throwable $e) {
            return null;
        }
    }
}