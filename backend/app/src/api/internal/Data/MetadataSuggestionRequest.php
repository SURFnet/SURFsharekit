<?php

namespace SilverStripe\api\internal\Data;

use Throwable;

class MetadataSuggestionRequest {
    public string $metaFieldUuid;
    public string $repoItemRepoItemFileUuid;
    public ?string $metaFieldOptionUuid;

    public function __construct(
        string $metaFieldUuid,
        string $repoItemRepoItemFileUuid,
        ?string $metaFieldOptionUuid = null
    ) {
        $this->metaFieldUuid = $metaFieldUuid;
        $this->repoItemRepoItemFileUuid = $repoItemRepoItemFileUuid;
        $this->metaFieldOptionUuid = $metaFieldOptionUuid;
    }

    static public function fromJson($json): ?MetadataSuggestionRequest {
        try {
            $decodedJson = json_decode($json, true);
            return new MetadataSuggestionRequest(
                $decodedJson["metaFieldUuid"] ?? null,
                $decodedJson["repoItemRepoItemFileUuid"] ?? null,
                $decodedJson["metaFieldOptionUuid"] ?? null,
            );
        } catch (Throwable $e) {
            return null;
        }
    }
}