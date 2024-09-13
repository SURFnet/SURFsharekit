<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Services\Discover\DiscoverUploadService;

class MultiSelectDropdownFieldMetaFieldProcessor extends MetaFieldProcessor
{

    private static $type = "MultiSelectDropdown";

    public function validate(): ApiValidationResult {
        $result = parent::validate();

        $value = $this->getValue();
        $metaField = $this->getMetaField();

        if (count($value)) {
            $foundOptions = $metaField->MetaFieldOptions()->filter('Uuid', $value)->column('Uuid');

            if (count(array_diff($value, $foundOptions))) {
                $result->addError('One or more options are not valid options');
            }
        }

        return $result;
    }

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = new RepoItemService();
        $values = $this->getValue();

        foreach ($values as $value) {
            $metaFieldOption = MetaFieldOption::get()->filter('Uuid', $value)->first();
            $repoItemMetaFieldValue = $repoItemService->createRepoItemMetaFieldValue($repoItemMetaField);
            $repoItemMetaFieldValue->MetaFieldOptionUuid = $metaFieldOption->Uuid;
            $repoItemMetaFieldValue->MetaFieldOptionID = $metaFieldOption->ID;
            $repoItemMetaFieldValue->write();
        }
    }

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField): array {
        $repoItemMetaFieldValues = $repoItemMetaField->RepoItemMetaFieldValues()->filter('IsRemoved', false);
        $response = [];

        foreach($repoItemMetaFieldValues as $repoItemMetaFieldValue) {
            $response[] = $repoItemMetaFieldValue->MetaFieldOptionUuid;
        }

        return $response;
    }
}