<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Services\Discover\DiscoverUploadService;

class MultiSelectSuborganisationSwitchMetaFieldProcessor extends ScopedMetaFieldProcessor
{
    private static $type = "MultiSelectSuborganisationSwitch";

    public function validate(): ApiValidationResult {
        $result = parent::validate();

        $values = $this->getValue();
        $instituteUuid = $this->getRootInstituteUuid();
        $discoverUploadService = new DiscoverUploadService();
        $institutes = $discoverUploadService->getLevelBasedInstitutesUuids($instituteUuid, ['lectorate','discipline','department']);

        // Check if the given option is in the list of possible MetaFieldOptions
        foreach ($values as $value) {
            if (!in_array($value, $institutes)){
                $result->addError("Institute with identifier $value does not exist or is not valid in this context");
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