<?php

namespace SilverStripe\api\MetadataSuggestion\Data;

class Term {
    private string $id;
    private string $type;
    private Label $label;

    public function __construct(string $id, string $type, Label $label) {
        $this->id = $id;
        $this->type = $type;
        $this->label = $label;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getLabel(): Label {
        return $this->label;
    }

}