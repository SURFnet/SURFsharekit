<?php

namespace SilverStripe\EnvironmentExport\Models;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;
use SurfSharekit\Models\Helper\Logger;
use Throwable;

class ExportedDataObjectRelationMetadata {
    use SerializableTrait;

    private string $type;
    private string $table;
    private string $class;
    private string $name;
    public function __construct(string $type, string $table, string $class, string $name) {
        $this->type = $type;
        $this->table = $table;
        $this->class = $class;
        $this->name = $name;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getTable(): string {
        return $this->table;
    }

    public function getClass(): string {
        return $this->class;
    }

    public function getName(): string {
        return $this->name;
    }

    public static function fromJson($json): ?ExportedDataObjectRelationMetadata {
        try {
            if (is_string($json)) {
                $decodedJson = json_decode($json, true);
            } else {
                $decodedJson = $json;
            }
            return new ExportedDataObjectRelationMetadata(
                $decodedJson["type"] ?? null,
                $decodedJson["table"] ?? null,
                $decodedJson["class"] ?? null,
                $decodedJson["name"] ?? null,
            );
        } catch(Throwable $e) {
            Logger::debugLog("Failed deserializing ExportedDataObjectRelationMetadata: {$e->getMessage()}");
            return null;
        }
    }

}