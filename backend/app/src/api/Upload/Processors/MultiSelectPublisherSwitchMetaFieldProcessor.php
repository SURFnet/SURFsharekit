<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\RepoItemMetaField;

class MultiSelectPublisherSwitchMetaFieldProcessor extends MetaFieldProcessor
{

    private static $type = "MultiSelectPublisherSwitch";

    public function validate(): ApiValidationResult {
        $result = parent::validate();

        $values = $this->getValue();
        foreach ($values as $value) {
            if (!($chosenInstitute = Institute::get()->filter(['Uuid' => $value, 'InstituteID' => 0])->first()) || !$chosenInstitute->exists()){
                $result->addError("institute with uuid $value is not a valid institute");
            }

            return $result;
        }

        return $result;
    }

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = new RepoItemService();
        $values = $this->getValue();

        foreach ($values as $value) {
            $chosenInstitute = Institute::get()->filter('Uuid', $value)->first();
            $repoItemMetaFieldValue = $repoItemService->createRepoItemMetaFieldValue($repoItemMetaField);
            $repoItemMetaFieldValue->InstituteUuid = $value;
            $repoItemMetaFieldValue->InstituteID = $chosenInstitute->ID;
            $repoItemMetaFieldValue->write();
        }
    }

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField): array {
        $repoItemMetaFieldValues = $repoItemMetaField->RepoItemMetaFieldValues()->filter('IsRemoved', false);
        $response = [];

        foreach($repoItemMetaFieldValues as $repoItemMetaFieldValue) {
            $response[] = $repoItemMetaFieldValue->InstituteUuid;
        }

        return $response;
    }
}