<?php

namespace SilverStripe\EnvironmentExport\Models;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;
use SurfSharekit\Models\Helper\Logger;
use Throwable;

class ExportedDataObjectMetadata {
    use SerializableTrait;

    private string $class;
    private int $count;
    private string $table;
    private array $relations;

    public function __construct(string $class, int $count, string $table, array $relations = []) {
        $this->class = $class;
        $this->count = $count;
        $this->table = $table;
        $this->relations = $relations;
    }

    public function getClass(): string {
        return $this->class;
    }

    public function getCount(): int {
        return $this->count;
    }

    public function getTable(): string {
        return $this->table;
    }

    public function getRelations(): array {
        return $this->relations;
    }

    public static function fromJson($json): ?ExportedDataObjectMetadata {
        try {
            if (is_string($json)) {
                $decodedJson = json_decode($json, true);
            } else {
                $decodedJson = $json;
            }
            $relations = $decodedJson["relations"] ?? [];
            $dataObjectRelationMetadataArray = [];
            foreach ($relations as $relation) {
                $dataObjectRelationMetadataArray[] = ExportedDataObjectRelationMetadata::fromJson($relation);
            }
            return new ExportedDataObjectMetadata(
                $decodedJson["class"] ?? null,
                $decodedJson["count"] ?? null,
                $decodedJson["table"] ?? null,
                $dataObjectRelationMetadataArray,
            );
        } catch(Throwable $e) {
            Logger::debugLog("Failed deserializing ExportedDataObjectMetadata: {$e->getMessage()}");
            return null;
        }
    }

}