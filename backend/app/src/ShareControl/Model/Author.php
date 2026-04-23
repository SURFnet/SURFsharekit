<?php

namespace SurfSharekit\ShareControl\Model;

use Exception;
use JsonSerializable;

class Author {
    public string $uuid;
    public string $fullName;
    public ?string $middleName;
    public ?string $firstName;
    public ?string $lastName;
    public ?string $email;
    public ?string $roleTerm;
    public ?string $originalIdentifier;

    public static function fromJSON(string|array $json): static {
        $obj = new static();
        if (is_string($json)) {
            $jsonObj = json_decode($json, true);
        } else {
            $jsonObj = $json;
        }

        // Required string fields
        $obj->uuid = $jsonObj['uuid'] ?? throw new Exception("Input json is missing property uuid");
        $obj->fullName = $jsonObj['fullName'] ?? "";

        // Nullable fields
        $obj->firstName = $jsonObj['firstName'] ?? null;
        $obj->middleName = $jsonObj['middleName'] ?? null;
        $obj->lastName = $jsonObj['lastName'] ?? null;
        $obj->email = $jsonObj['email'] ?? null;
        $obj->roleTerm = $jsonObj['roleTerm'] ?? null;
        $obj->originalIdentifier = $jsonObj['originalIdentifier'] ?? null;

        return $obj;
    }
} 