<?php

namespace SurfSharekit\Services\Person;

use SilverStripe\api\Upload\Data\Persons\CreatePersonRequest;
use SilverStripe\ORM\DataList;
use SurfSharekit\Models\Person;

interface IPersonService {
    public function getPerson($uuid): ?Person;
    public function getPersons(): DataList;

    public function createPerson(CreatePersonRequest $createRequest): Person;
}