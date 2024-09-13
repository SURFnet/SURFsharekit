<?php

namespace SilverStripe\api\Upload\Data\Persons;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;

class Person {
    use SerializableTrait;

    public string $id;
    public ?string $name;
    public ?string $surnamePrefix;
    public ?string $surname;
    public ?string $firstName;
    public ?string $organisationId;
    public ?string $dai;
    public ?string $orcid;
    public ?string $isni;
    public ?string $position;
    public array $rootInstitutes = [];
}