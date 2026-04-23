<?php

namespace SurfSharekit\Orcid\Service;

class OrcidProfile {
    public function __construct(
        public string $orcidId,
        public ?string $name,
        public ?string $givenName,
        public ?string $familyName,
        public array $emails,
        public array $affiliations
    ) {}
}