<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItemMetaField;

class PersonMetaFieldProcessor extends MetaFieldProcessor
{
    private static $type = "Person";

    public function validate(): ApiValidationResult {
        $validationResult = parent::validate();

        if (Person::get()->find('Uuid', $this->getValue()) === null) {
            $validationResult->addError("Person could not be found");
        }

        return $validationResult;
    }

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = RepoItemService::create();
        $repoItemMetaFieldValue = $repoItemService->createRepoItemMetaFieldValue($repoItemMetaField);
        $repoItemMetaFieldValue->PersonID = Person::get()->find('Uuid', $this->getValue())->ID;
        $repoItemMetaFieldValue->write();
    }

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField) {
        return $repoItemMetaField->RepoItemMetaFieldValues()->first()->PersonUuid;
    }
}