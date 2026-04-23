<?php

namespace SilverStripe\api\MetadataSuggestion\Data;

class Label {
    public string $language;
    public string $value;

    public function __construct(string $language, string $value) {
        $this->language = $language;
        $this->value = $value;
    }
}