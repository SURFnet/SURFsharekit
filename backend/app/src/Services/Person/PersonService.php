<?php

namespace SilverStripe\Services\Person;

use SilverStripe\api\Upload\Data\Persons\CreatePersonRequest;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Models\Person;
use SurfSharekit\Services\Person\IPersonService;

class PersonService implements IPersonService {
    use Injectable;
    use Configurable;

    private $personIdentifiers = [
        "email" => "Email",
        "dai" => "PersistentIdentifier",
        "isni" => "ISNI",
        "orcid" => "ORCID",
        "hogeschoolId" => "HogeschoolID",
//        "sramId" => "SRAMID", TODO: add after sram implementation
    ];

    public function getPerson($personId): ?Person {
        return Person::get()->find('Uuid', $personId);
    }

    public function getPersons(): DataList {
        return Person::get()
            ->leftJoin('SurfSharekit_Person_RootInstitutes', 'SurfSharekit_Person_RootInstitutes.SurfSharekit_PersonID = Member.ID')
            ->leftJoin('SurfSharekit_Institute', 'SurfSharekit_Institute.ID = SurfSharekit_Person_RootInstitutes.SurfSharekit_InstituteID');
    }

    public function createPerson(CreatePersonRequest $createRequest): Person {
        foreach ($this->personIdentifiers as $field => $DBField) {
            if (!empty($createRequest->{$field}) && Person::get()->find($DBField, $createRequest->{$field})) {
                throw new BadRequestException(ApiErrorConstant::GA_BR_005, "Person already exists with identifier: $field");
            }
        }

        $person = Person::create([
            "FirstName"             => $createRequest->firstName,
            "SurnamePrefix"         => $createRequest->surnamePrefix,
            "Surname"               => $createRequest->surname,
            "Position"              => $createRequest->position,
            "Email"                 => $createRequest->email,
            "PersistentIdentifier"  => $createRequest->dai,
            "ISNI"                  => $createRequest->isni,
            "ORCID"                 => $createRequest->orcid,
            "HogeschoolID"          => $createRequest->organisationId,
            "DisableEmailChange"    => $createRequest->exposeEmail
        ]);

        $person->setSkipEmail(empty($createRequest->email));
        $person->setBaseInstitute($createRequest->institute);
        $person->write();

        return  $person;
    }
}