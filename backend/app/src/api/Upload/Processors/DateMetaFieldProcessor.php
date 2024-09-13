<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\RepoItemMetaField;

class DateMetaFieldProcessor extends MetaFieldProcessor
{
    private static $type = "Date";

    public function validate(): ApiValidationResult {
        $result = parent::validate();

        if (null !== $regex = $this->getMetaField()->MetaFieldType()->ValidationRegex){
            if (!preg_match("/$regex/", $this->getValue())){
                $result->addError("Date format invalid, please provide a date string with format: YYYY-MM-DD");
            }
        }

        if ($datetime = \DateTime::createFromFormat('Y-m-d', $this->getValue())) {
            if (!$datetime instanceof \DateTime || $datetime->format('Y-m-d') !== $this->getValue()) {
                $result->addError("Date format invalid, please provide a date string with format: YYYY-MM-DD");
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

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField) {
        return $repoItemMetaField->RepoItemMetaFieldValues()->first()->Value;
    }
}