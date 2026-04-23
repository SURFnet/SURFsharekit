<?php

namespace SilverStripe\api\MetadataSuggestion\Providers;

use SilverStripe\api\MetadataSuggestion\MetadataSuggestionClient;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;

class VocabularyMetadataProvider extends MetadataProvider {

    public function provideMetadataSuggestions(string $fileText, MetaField $metaField, ?string $metaFieldOptionUuid = null): array {
        if (!$metaFieldOptionUuid) {
            return [];
        }

        // This is one of the root MetaFieldOptions of the Vocabulary MetaField, the value of this option is the vocabulary identifier
        // This identifier is used to map a specific vocabulary to an AI model
        /** @var null|MetaFieldOption $metaFieldOption */
        $metaFieldOption = MetaFieldOption::get()->find("Uuid", $metaFieldOptionUuid);
        if (!$metaFieldOption) {
            return [];
        }

        $metadataSuggestionClient = new MetadataSuggestionClient();
        $metadata = $metadataSuggestionClient->getVocabularySuggestions($metaFieldOption->Value, $fileText);

        $suggestions = $metadata->getSuggestions();
        if (!$suggestions) {
            return [];
        }

        $metaFieldOptionValues = $metadata->toMetaFieldOptionValues();
        $metaFieldOptions = MetaFieldOption::get()->filter([
            "Value" => $metaFieldOptionValues,
            "MetaFieldID" => $metaField->ID
        ]);

        $suggestions = [];
        foreach ($metaFieldOptions as $metaFieldOption) {
            $suggestions[] = [
                "metaFieldOptionUuid" => $metaFieldOption->Uuid,
                "value" => $metaFieldOption->Value,
                "labelNL" => $metaFieldOption->Label_NL,
                "labelEN" => $metaFieldOption->Label_EN,
            ];
        }
        return $suggestions;
    }
}