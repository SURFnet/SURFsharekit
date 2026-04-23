<?php

namespace SurfSharekit\ShareControl\Model;

use DateTime;
use Exception;
use JsonSerializable;

class File {
    public string $uuid;
    public ?string $fileName;
    public ?string $accessRight;
    public string $originalUrl;
    public ?string $downloadUrl;
    public string $resourceMimeType;
    public ?string $originalIdentifier;
    public ?DateTime $lastVerified;
    public ?DateTime $fileNotFoundFirstVerifiedAt;

    public static function fromJSON(string|array $json): static {
        $obj = new static();
        if (is_string($json)) {
            $jsonObj = json_decode($json, true);
        } else {
            $jsonObj = $json;
        }

        // Required string fields
        $obj->uuid = $jsonObj['uuid'] ?? throw new Exception("Input json is missing property uuid");
        $obj->originalUrl = $jsonObj['originalUrl'] ?? "";
        $obj->resourceMimeType = $jsonObj['resourceMimeType'] ?? "";

        // Nullable string fields
        $obj->fileName = $jsonObj['fileName'] ?? null;
        $obj->accessRight = $jsonObj['accessRight'] ?? null;
        $obj->downloadUrl = $jsonObj['downloadUrl'] ?? null;
        $obj->originalIdentifier = $jsonObj['originalIdentifier'] ?? null;

        // Nullable DateTime fields
        $obj->lastVerified = isset($jsonObj['lastVerified']) ? new DateTime($jsonObj['lastVerified']) : null;
        $obj->fileNotFoundFirstVerifiedAt = isset($jsonObj['fileNotFoundFirstVerifiedAt']) ? new DateTime($jsonObj['fileNotFoundFirstVerifiedAt']) : null;

        return $obj;
    }
} 