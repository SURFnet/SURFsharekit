<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaField;

class RepoItemLinkMetaFieldProcessor extends MetaFieldProcessor
{

    private static $type = "RepoItemLink";

    public function validate(): ApiValidationResult {
        $validationResult = parent::validate();

        if (!in_array($this->getRepoItem()->RepoType, Constants::MAIN_REPOTYPES)) {
            $validationResult->addError("RepoItemLink MetaField can not be nested");
        }

        return $validationResult;
    }

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = RepoItemService::create();
        $repoItem = $this->getRepoItem();

        foreach ($this->getValue() as $value) {
            $repoItemMetaFieldValue = $repoItemService->createRepoItemMetaFieldValue($repoItemMetaField);
            $repoItemLink = $repoItemService->createRepoItem($repoItem->OwnerUuid, $repoItem->InstituteUuid, "RepoItemLink");
            $repoItemService->addMetaData($repoItemLink, $value);

            $repoItemMetaFieldValue->RepoItemID = $repoItemLink->ID;
            $repoItemMetaFieldValue->write();
        }
    }

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField): array {
        $repoItemService = RepoItemService::create();
        $metaFieldValues = $repoItemMetaField->RepoItemMetaFieldValues()->filter('IsRemoved', false);

        $repoItemResponses = [];
        foreach ($metaFieldValues as $value) {
            $repoItem = RepoItem::get_by_id($value->RepoItemID);

            if ($repoItem) {
                $metadata = $repoItemService->getMetaData($repoItem);
                $repoItemResponses[] = $metadata;
            }
        }

        return $repoItemResponses;
    }
}