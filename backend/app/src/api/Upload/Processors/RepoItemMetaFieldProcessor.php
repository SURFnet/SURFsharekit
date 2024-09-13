<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaField;

class RepoItemMetaFieldProcessor extends MetaFieldProcessor
{
    private static $type = "RepoItem";

    public function validate(): ApiValidationResult {
        $validationResult = parent::validate();

        if (!(RepoItem::get()->find('Uuid', $this->getValue()))) {
            $validationResult->addError("RepoItem could not be found");
        }

        return $validationResult;
    }

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = RepoItemService::create();
        $repoItemMetaFieldValue = $repoItemService->createRepoItemMetaFieldValue($repoItemMetaField);
        $repoItemMetaFieldValue->RepoItemID = RepoItem::get()->find('Uuid', $this->getValue())->ID;
        $repoItemMetaFieldValue->write();
    }

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField) {
        return $repoItemMetaField->RepoItemMetaFieldValues()->first()->RepoItemUuid;
    }
}