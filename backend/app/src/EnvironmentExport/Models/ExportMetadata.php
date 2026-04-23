<?php

namespace SilverStripe\EnvironmentExport\Models;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;
use SurfSharekit\Models\Helper\Logger;
use Throwable;

class ExportMetadata {
    use SerializableTrait;

    private string $environment;
    private string $exportStarted;
    private string $exportCompleted;
    private string $exportedBy;
    private string $totalCount;

    public function __construct(string $environment, string $exportStarted, string $exportCompleted, string $exportedBy, int $totalCount) {
        $this->environment = $environment;
        $this->exportStarted = $exportStarted;
        $this->exportCompleted = $exportCompleted;
        $this->exportedBy = $exportedBy;
        $this->totalCount = $totalCount;
    }

    public static function fromJson($json): ?ExportMetadata {
        try {
            if (is_string($json)) {
                $decodedJson = json_decode($json, true);
            } else {
                $decodedJson = $json;
            }
            return new ExportMetadata(
                $decodedJson["environment"] ?? null,
                $decodedJson["exportStarted"] ?? null,
                $decodedJson["exportCompleted"] ?? null,
                $decodedJson["exportedBy"] ?? null,
                $decodedJson["totalCount"] ?? null,
            );
        } catch(Throwable $e) {
            Logger::debugLog("Failed deserializing ExportMetadata: {$e->getMessage()}");
            return null;
        }
    }

    public function getEnvironment(): string {
        return $this->environment;
    }

    public function getExportStarted(): string {
        return $this->exportStarted;
    }

    public function getExportCompleted(): string {
        return $this->exportCompleted;
    }

    public function getExportedBy(): string {
        return $this->exportedBy;
    }

    public function getTotalCount(): string {
        return $this->totalCount;
    }
}