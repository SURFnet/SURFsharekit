<?php

namespace SilverStripe\api\Upload\Data;

use Exception;
use SilverStripe\api\Upload\Data\Traits\SerializableTrait;
use Throwable;

class CreateRepoItemRequest {
    use SerializableTrait;

    public string $owner;
    public string $institute;
    public string $repoItemType;
    public array $metadata;

    public function __construct(
        string $owner,
        string $institute,
        string $repoItemType,
        array $metadata
    ) {
        $this->owner = $owner;
        $this->institute = $institute;
        $this->repoItemType = $repoItemType;
        $this->metadata = $metadata;
    }

    static public function fromJson($json): ?CreateRepoItemRequest {
        try {
            $decodedJson = json_decode($json, true);
            return new CreateRepoItemRequest(
                $decodedJson["owner"] ?? null,
                $decodedJson["institute"] ?? null,
                $decodedJson["repoItemType"] ?? null,
                $decodedJson["metadata"] ?? null,
            );
        } catch (Throwable $e) {
            return null;
        }
    }
}