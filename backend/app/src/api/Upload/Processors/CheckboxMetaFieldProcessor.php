<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\RepoItemMetaField;

class CheckboxMetaFieldProcessor extends MetaFieldProcessor
{
    private static $type = "Checkbox";

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = RepoItemService::create();

        // Calling first, because checkboxes only have one MetaFieldOption
        $metaFieldOption = $this->getMetaField()->MetaFieldOptions()->first();

        $repoItemMetaFieldValue = $repoItemService->createRepoItemMetaFieldValue($repoItemMetaField);
        $repoItemMetaFieldValue->MetaFieldOptionID = $metaFieldOption->ID;
        $repoItemMetaFieldValue->IsRemoved = !$this->getValue();
        $repoItemMetaFieldValue->write();
    }

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField): bool {
        return !$repoItemMetaField->RepoItemMetaFieldValues()->first()->IsRemoved;
    }
}