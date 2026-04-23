<?php

namespace SurfSharekit\Piwik\Events;

abstract class DownloadEvent {
    protected array $dimensions = [];

    public function getDimensions(): array {
        return $this->dimensions;
    }
}