<?php

namespace SurfSharekit\ShareControl\Model;

use DateTime;
use Exception;
use JsonSerializable;

class Item {
    public string $uuid;
    public string $ibronId;
    public string $sourceId;
    public string $type;
    public string $originalIdentifier;
    public DateTime $firstHarvested;
    public DateTime $lastModified;
    public ?string $sskRepoItemUuid;
    public DateTime $lastModifiedInSource;
    public bool $deletedBySource;
    public string $title;
    public ?string $subTitle;
    public string $abstract_;
    public string $language;
    public array $keywords;
    /** @var Author[] */
    public array $authors;
    /** @var File[] */
    public array $files;
    /** @var Availability[] */
    public array $availabilities;
    public ?string $courseId;
    public ?DateTime $purgeAfter;
    public bool $purged;
    public ?DateTime $expiryDateForNlBron;
    public array $sourceObjects;

    public static function fromJSON(string|array $json): static {
        $obj = new static();
        if (is_string($json)) {
            $jsonObj = json_decode($json, true);
        } else {
            $jsonObj = $json;
        }

        // Required string fields
        $obj->uuid = $jsonObj['uuid'] ?? throw new Exception("Input json is missing property uuid");
        $obj->ibronId = $jsonObj['ibronId'] ?? "";
        $obj->sourceId = $jsonObj['sourceId'] ?? "";
        $obj->type = $jsonObj['type'] ?? "";
        $obj->originalIdentifier = $jsonObj['originalIdentifier'] ?? "";
        $obj->title = $jsonObj['title'] ?? "";
        $obj->subTitle = $jsonObj['subTitle'] ?? "";
        $obj->abstract_ = $jsonObj['abstract_'] ?? "";
        $obj->language = $jsonObj['language'] ?? "";

        // Required DateTime fields
        $obj->firstHarvested = new DateTime($jsonObj['firstHarvested'] ?? "");
        $obj->lastModified = new DateTime($jsonObj['lastModified'] ?? "");
        $obj->lastModifiedInSource = new DateTime($jsonObj['lastModifiedInSource'] ?? "");

        // Required boolean fields
        $obj->deletedBySource = $jsonObj['deletedBySource'] ?? "";
        $obj->purged = $jsonObj['purged'] ?? "";

        // Required arrays
        $obj->keywords = $jsonObj['keywords'] ?? "";
        $obj->authors = array_map(fn($author) => Author::fromJSON($author), $jsonObj['authors'] ?? "");
        $obj->files = array_map(fn($file) => File::fromJSON($file), $jsonObj['files'] ?? "");
        $obj->availabilities = array_map(fn($availability) => Availability::fromJSON($availability), $jsonObj['availabilities'] ?? "");
        $obj->sourceObjects = $jsonObj['sourceObjects'] ?? "";

        // Nullable fields
        $obj->sskRepoItemUuid = $jsonObj['sskRepoItemUuid'] ?? null;
        $obj->courseId = $jsonObj['courseId'] ?? null;
        $obj->purgeAfter = isset($jsonObj['purgeAfter']) ? new DateTime($jsonObj['purgeAfter']) : null;
        $obj->expiryDateForNlBron = isset($jsonObj['expiryDateForNlBron']) ? new DateTime($jsonObj['expiryDateForNlBron']) : null;

        return $obj;
    }
}