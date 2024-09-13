<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Assets\File;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemMetaField;

class FileMetaFieldProcessor extends MetaFieldProcessor
{
    private static $type = "File";

    public function validate(): ApiValidationResult {
        $validationResult = parent::validate();

        if (RepoItemFile::get()->find('Uuid', $this->getValue()) === null) {
            $validationResult->addError("File with identifier " . $this->getValue() . " could not be found");
        }

        return $validationResult;
    }

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = RepoItemService::create();
        $repoItemMetaFieldValue = $repoItemService->createRepoItemMetaFieldValue($repoItemMetaField);
        $repoItemMetaFieldValue->RepoItemFileID = RepoItemFile::get()->find('Uuid', $this->getValue())->ID;
        $repoItemMetaFieldValue->write();
    }

    public function convertValueToJson(RepoItemMetaField $repoItemMetaField) {
        return $repoItemMetaField->RepoItemMetaFieldValues()->first()->RepoItemFileUuid;
    }
}