<?php

namespace SurfSharekit\ShareControl\Model;

use DateTime;
use Exception;
use JsonSerializable;

class Availability {
    public string $nlbronId;
    public ?DateTime $startDate;
    public ?DateTime $endDate;
    public ?int $months;

    public static function fromJSON(string|array $json): static {
        $obj = new static();
        if (is_string($json)) {
            $jsonObj = json_decode($json, true);
        } else {
            $jsonObj = $json;
        }

        // Required string field
        $obj->nlbronId = $jsonObj['nlbronId'] ?? "";

        // Nullable DateTime fields
        $obj->startDate = isset($jsonObj['startDate']) ? new DateTime($jsonObj['startDate']) : null;
        $obj->endDate = isset($jsonObj['endDate']) ? new DateTime($jsonObj['endDate']) : null;

        // Nullable int field
        $obj->months = $jsonObj['months'] ?? null;

        return $obj;
    }
} 