<?php

namespace SilverStripe\EnvironmentExport\Models;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;
use SurfSharekit\Models\Helper\Logger;
use Throwable;

class ExportedDataObject {
    use SerializableTrait;

    private ExportedDataObjectMetadata $metadata;
    private array $data;

    public function __construct(ExportedDataObjectMetadata $metadata, array $data) {
        $this->metadata = $metadata;
        $this->data = $data;
    }

    public static function fromJson($json): ?ExportedDataObject {
        try {
            if (is_string($json)) {
                $decodedJson = json_decode($json, true);
            } else {
                $decodedJson = $json;
            }

            $metadata = $decodedJson["meta"] ?? [];
            $exportedDataObjectMetadata = ExportedDataObjectMetadata::fromJson($metadata);

            $exportedDataObjectData = $decodedJson["data"] ?? [];

            return new ExportedDataObject(
                $exportedDataObjectMetadata,
                $exportedDataObjectData
            );
        } catch(Throwable $e) {
            Logger::debugLog("Failed deserializing ExportedDataObjectMetadata: {$e->getMessage()}");
            return null;
        }
    }

    public function getMetadata(): ExportedDataObjectMetadata {
        return $this->metadata;
    }

    public function getData(): array {
        return $this->data;
    }
}