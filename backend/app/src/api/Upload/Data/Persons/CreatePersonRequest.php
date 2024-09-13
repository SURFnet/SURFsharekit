<?php

namespace SilverStripe\api\Upload\Data\Persons;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;
use Throwable;

class CreatePersonRequest {
    use SerializableTrait;

    public string $firstName;
    public ?string $surnamePrefix;
    public string $surname;
    public string $institute;
    public string $position;
    public ?string $email;
    public ?string $dai;
    public ?string $isni;
    public ?string $orcid;
    public ?string $organisationId;
    public bool $exposeEmail = false;

    public function __construct(
        string  $firstName,
        ?string $surnamePrefix,
        string  $surname,
        string  $institute,
        string  $position,
        ?string $email,
        ?string $dai,
        ?string $isni,
        ?string $orcid,
        ?string $organisationId,
        bool    $exposeEmail = false
    ) {
        $this->firstName = $firstName;
        $this->surnamePrefix = $surnamePrefix;
        $this->surname = $surname;
        $this->institute = $institute;
        $this->position = $position;
        $this->email = $email;
        $this->dai = $dai;
        $this->isni = $isni;
        $this->orcid = $orcid;
        $this->organisationId = $organisationId;
        $this->exposeEmail = $exposeEmail;
    }

    public static function fromJson(string $json): ?CreatePersonRequest {
        try {
            if (empty($json)) {
                return null;
            }

            $decodedJson = json_decode($json, true);
            return new CreatePersonRequest(
                $decodedJson["firstName"] ?? null,
                $decodedJson["surnamePrefix"] ?? null,
                $decodedJson["surname"] ?? null,
                $decodedJson["institute"] ?? null,
                $decodedJson["position"] ?? null,
                $decodedJson["email"] ?? null,
                $decodedJson["dai"] ?? null,
                $decodedJson["isni"] ?? null,
                $decodedJson["orcid"] ?? null,
                $decodedJson["organisationId"] ?? null,
                $decodedJson["exposeEmail"] ?? false,
            );
        } catch (Throwable $e) {
            return null;
        }
    }
}