<?php

namespace SilverStripe\api\MetadataSuggestion;

use SilverStripe\api\MetadataSuggestion\Providers\MetadataProvider;
use SilverStripe\api\MetadataSuggestion\Providers\VocabularyMetadataProvider;

class MetafieldMetadataProviderMapper {
    static private array $metaFieldJsonKeyToMetadataProviderClass = [
        "vocabulary" => VocabularyMetadataProvider::class
    ];

    /**
     * @param string $jsonKey
     * @return MetadataProvider|null
     */
    public static function getMetadataProvider(string $jsonKey): ?MetadataProvider {
        $metadataProviderClass = MetafieldMetadataProviderMapper::$metaFieldJsonKeyToMetadataProviderClass[$jsonKey];
        if (!$metadataProviderClass) {
            return null;
        }

        return new $metadataProviderClass;
    }
}