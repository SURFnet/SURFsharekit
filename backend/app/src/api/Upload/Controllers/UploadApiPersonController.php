<?php

namespace SurfSharekit\Api\Upload\Controllers;

use Exception;
use SilverStripe\api\RequestFilter;
use SilverStripe\api\ResponseHelper;
use SilverStripe\api\Upload\Data\Persons\CreatePersonRequest;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Services\Person\PersonService;
use SilverStripe\Validation\Mod11Validator;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use Zooma\SilverStripe\Models\JsonNestedFilter;

class UploadApiPersonController extends UploadApiAuthController {
    private static $url_handlers = [
        'GET $Uuid!' => 'getPerson',
        'GET /' => 'getPersons',
        'POST /' => 'createPerson',
    ];

    private static $allowed_actions = [
        'getPersons',
        'getPerson',
        'createPerson'
    ];

    public function getPerson(HTTPRequest $request) {
        if (null === $id = $request->param('Uuid')) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_002);
        }

        if (null === $person = (new PersonService())->getPerson($id)) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002);
        }

        $personResponse = new \SilverStripe\api\Upload\Data\Persons\Person();
        $personResponse->id = $person->Uuid;
        $personResponse->name = $person->Name;
        $personResponse->surnamePrefix = $person->SurnamePrefix;
        $personResponse->surname = $person->Surname;
        $personResponse->firstName = $person->FirstName;
        $personResponse->organisationId = $person->HogeschoolID;
        $personResponse->dai = $person->PersistentIdentifier;
        $personResponse->orcid = $person->ORCID;
        $personResponse->isni = $person->ISNI;
        $personResponse->position = $person->Position;
        $personResponse->rootInstitutes = $person->RootInstitutesSummary;

        return ResponseHelper::responseSuccess($personResponse->toJson());
    }

    private function validatePersonFilters($filter) {
        // Each filter in this list must be an exact match filter (so either the default or [EQ])
        $mandatoryExactMatchList = ["email", "name", "institute", "orcid", "dai", "isni", "surname", "organisationId"];
        foreach ($mandatoryExactMatchList as $filterField) {
            if (isset($filter[$filterField]) && is_array($filter[$filterField]) && !isset($filter[$filterField]['EQ'])) {
                throw new BadRequestException(ApiErrorConstant::GA_BR_003, ucfirst($filterField) . " filter must use exact match [EQ] operator");
            }
        }
    }

    public function getPersons(HTTPRequest $request) {
        $personService = PersonService::create();
        $persons = $personService->getPersons();

        if (null !== $filter = $request->getVar('filter')) {
            if (is_string($filter)) {
                $filterData = json_decode($filter, true);
                $this->validatePersonFilters($filterData);
                $persons = JsonNestedFilter::filterDataList([
                    "email" => "Email",
                    "surname" => "Surname",
                    "dai" => "PersistentIdentifier",
                    "isni" => "ISNI",
                    "orcid" => "ORCID",
                    "organisationId" => "HogeschoolID"
                ], $filter, $persons);
            } else {
                $this->validatePersonFilters($filter);
                $persons = RequestFilter::filterDataList($persons, $filter, [
                    'email' => '`Member`.`Email`',
                    'name' => "REPLACE(CONCAT(`Member`.`FirstName`,' ',COALESCE(`Member`.`SurnamePrefix`,''),' ', `Member`.`Surname`),'  ',' ')",
                    'orcid' => 'ORCID',
                    'dai' => 'PersistentIdentifier',
                    'isni' => 'ISNI',
                    'surname' => 'Surname',
                    'organisationId' => 'HogeschoolID'
                ]);
            }
        } else {
            $persons = $persons->filter("ID", 0); //Empty list
        }

        return ResponseHelper::responsePaginatedDataList($request, $persons, function (Person $person) {
            $personResponse = new \SilverStripe\api\Upload\Data\Persons\Person();
            $personResponse->id = $person->Uuid;
            $personResponse->name = $person->Name;
            $personResponse->surnamePrefix = $person->SurnamePrefix;
            $personResponse->surname = $person->Surname;
            $personResponse->firstName = $person->FirstName;
            $personResponse->organisationId = $person->HogeschoolID;
            $personResponse->dai = $person->PersistentIdentifier;
            $personResponse->orcid = $person->ORCID;
            $personResponse->isni = $person->ISNI;
            $personResponse->position = $person->Position;
            $personResponse->rootInstitutes = $person->RootInstitutesSummary;

            return $personResponse;
        });
    }

    public function createPerson(HTTPRequest $request) {
        $json = $request->getBody();

        $createPersonRequest = CreatePersonRequest::fromJson($json);

        if (!$createPersonRequest) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_004);
        }

        if (!$createPersonRequest->institute || !$createPersonRequest->firstName || !$createPersonRequest->surname) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_004);
        }

        if (!Institute::get()->find('Uuid', $createPersonRequest->institute)) {
            throw new BadRequestException(ApiErrorConstant::GA_NF_002, "The provided institute does not exist");
        }

        if (!in_array($createPersonRequest->position, Person::getPositionOptions())) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_007,"The provided position does not exist. please choose from this list: " . implode(', ', Person::getPositionOptions()));
        }

        if ($createPersonRequest->dai !== null && !Mod11Validator::Mod11DaiValidator($createPersonRequest->dai)) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_009);
        }

        if ($createPersonRequest->isni !== null && !Mod11Validator::Mod11IsniOrcidValidator($createPersonRequest->isni)) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_010);
        }

        if ($createPersonRequest->orcid !== null && !Mod11Validator::Mod11IsniOrcidValidator($createPersonRequest->orcid)) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_011);
        }

        $personService = PersonService::create();
        $createdPerson = $personService->createPerson($createPersonRequest);

        $person = new \SilverStripe\api\Upload\Data\Persons\Person();
        $person->id = $createdPerson->Uuid;
        $person->name = $createdPerson->Name;
        $person->surnamePrefix = $createdPerson->SurnamePrefix;
        $person->surname = $createdPerson->Surname;
        $person->firstName = $createdPerson->FirstName;
        $person->organisationId = $createdPerson->HogeschoolID;
        $person->dai = $createdPerson->PersistentIdentifier;
        $person->orcid = $createdPerson->ORCID;
        $person->isni = $createdPerson->ISNI;
        $person->position = $createdPerson->Position;
        $person->rootInstitutes = $createdPerson->RootInstitutesSummary;

        return ResponseHelper::responseSuccess($person->toJson());
    }
}



