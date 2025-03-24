<?php

namespace SurfSharekit\Piwik\Api;
class PiwikColumn {
    private string $columnId;
    private ?string $transformationId;

    public function __construct(
        string  $columnId,
        ?string $transformationId = null
    ) {
        $this->columnId = $columnId;
        $this->transformationId = $transformationId;
    }

    public function getColumnId(): string {
        return $this->columnId;
    }

    public function getTransformationId(): ?string {
        return $this->transformationId;
    }

}