<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\MetaFieldType;
use SurfSharekit\Models\RepoItemMetaField;

class TextMetaFieldProcessor extends MetaFieldProcessor
{
    private static $type = "Text";

    public function validate(): ApiValidationResult {
        $result = parent::validate();

        $value = $this->getValue();
        $metaField = $this->getMetaField();

        if (null !== $regex = MetaFieldType::get()->find("ID", $metaField->MetaFieldTypeID)->ValidationRegex){
            if (!preg_match("/$regex/", $value)){
                $result->addError("The metadata of metafield $metaField->Title was not provided in the correct format");
            }

            return $result;
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