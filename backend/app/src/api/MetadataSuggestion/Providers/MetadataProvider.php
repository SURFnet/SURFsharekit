<?php

namespace SilverStripe\api\MetadataSuggestion\Providers;

use SurfSharekit\Models\MetaField;

abstract class MetadataProvider {
    public int $maxSuggestions = 3;
    abstract protected function provideMetadataSuggestions(string $fileText, MetaField $metaField, ?string $metaFieldOptionUuid = null): array;

    public function getMetadataSuggestions(string $fileText, MetaField $metaField, ?string $metaFieldOptionUuid = null): array {
        $metadataSuggestions = $this->provideMetadataSuggestions($fileText, $metaField, $metaFieldOptionUuid);
        return array_slice($metadataSuggestions, 0, $this->maxSuggestions);
    }
}