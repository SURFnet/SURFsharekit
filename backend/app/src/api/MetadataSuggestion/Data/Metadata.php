<?php

namespace SilverStripe\api\MetadataSuggestion\Data;

class Metadata {
    private array $suggestions;
    private int $totalCount;

    public function __construct(array $suggestions) {
        $this->suggestions = $suggestions;
        $this->totalCount = count($suggestions);
    }

    /**
     * @return array
     */
    public function getSuggestions(): array {
        return $this->suggestions;
    }

    /**
     * @return int
     */
    public function getTotalCount(): int {
        return $this->totalCount;
    }

    /**
     * @return array
     */
    public function toMetaFieldOptionValues(): array {
        /** @var Term $term */
        return array_map(function ($term) {
            return $term->getId();
        }, $this->suggestions);
    }

}