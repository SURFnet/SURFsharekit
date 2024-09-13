<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Services\Discover\DiscoverUploadService;

class LectorateMetaFieldProcessor extends ScopedMetaFieldProcessor
{
    private static $type = "Lectorate";

    public function validate(): ApiValidationResult {
        $result = parent::validate();

        $instituteUuid = $this->getRootInstituteUuid();
        $value = $this->getValue();
        $discoverUploadService = new DiscoverUploadService();
        $institutes = $discoverUploadService->getLevelBasedInstitutesUuids($instituteUuid, 'lectorate');

        // Check if the given option is in the list of possible MetaFieldOptions
        if (!in_array($value, $institutes)){
            $result->addError('Option is not a valid institute');
        }

        return $result;
    }

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = new RepoItemService();
        $value = $this->getValue();

        $chosenInstitute = Institute::get()->filter('Uuid', $value)->first();
        $repoItemMetaFieldValue = $repoItemService->createRepoItemMetaFieldValue($repoItemMetaField);
        $repoItemMetaFieldValue->InstituteUuid = $value;
        $repoItemMetaFieldValue->InstituteID = $chosenInstitute->ID;
        $repoItemMetaFieldValue->write();
    }

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField) {
        return $repoItemMetaField->RepoItemMetaFieldValues()->first()->InstituteUuid;
    }
}