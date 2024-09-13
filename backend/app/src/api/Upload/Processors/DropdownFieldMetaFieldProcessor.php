<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Services\Discover\DiscoverUploadService;

class DropdownFieldMetaFieldProcessor extends MetaFieldProcessor
{

    private static $type = "Dropdown";

    public function validate(): ApiValidationResult {
        $result = parent::validate();

        $metaField = $this->getMetaField();
        if ($metaField->MetaFieldOptions()->find('Uuid', $this->getValue()) == null) {
            $result->addError('Option is not a valid option');
        }

        return $result;
    }

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = new RepoItemService();
        $value = $this->getValue();
        $metaFieldOption = MetaFieldOption::get()->filter('Uuid', $value)->first();
        $repoItemMetaFieldValue = $repoItemService->createRepoItemMetaFieldValue($repoItemMetaField);
        $repoItemMetaFieldValue->MetaFieldOptionUuid = $metaFieldOption->Uuid;
        $repoItemMetaFieldValue->MetaFieldOptionID = $metaFieldOption->ID;
        $repoItemMetaFieldValue->write();
    }

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField) {
        return $repoItemMetaField->RepoItemMetaFieldValues()->first()->MetaFieldOptionUuid;
    }
}