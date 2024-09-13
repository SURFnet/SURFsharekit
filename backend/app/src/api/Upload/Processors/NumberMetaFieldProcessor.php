<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\RepoItemMetaField;

class NumberMetaFieldProcessor extends MetaFieldProcessor
{
    private static $type = "Number";

    public function validate(): ApiValidationResult {
        $result = parent::validate();

        // 0 value is validated as "false"...
        if ($this->getValue() !== 0) {
            if (is_numeric($this->getValue()) && !filter_var($this->getValue(), FILTER_VALIDATE_INT)) {
                $result->addError("value contains decimals");
            }
        }

        return $result;
    }

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = new RepoItemService();
        $value = $this->getValue();

        // Write MetaFieldValue
        $repoItemMetaFieldValue = $repoItemService->createRepoItemMetaFieldValue($repoItemMetaField);
        $repoItemMetaFieldValue->Value = $value;
        $repoItemMetaFieldValue->write();
    }

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField): int {
        return (int)$repoItemMetaField->RepoItemMetaFieldValues()->first()->Value;
    }
}