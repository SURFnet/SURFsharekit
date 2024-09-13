<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\RepoItemMetaField;

class DropdownTagFieldMetaFieldProcessor extends MetaFieldProcessor
{
    private static $type = "DropdownTag";

    public function save(RepoItemMetaField $repoItemMetaField): void {
        $repoItemService = new RepoItemService();
        $values = $this->getValue();
        $metaField = MetaField::get()->find('Uuid', $repoItemMetaField->MetaFieldUuid);

        foreach ($values as $value) {
            if (null === $metaFieldOption = $metaField->MetaFieldOptions()->find('Uuid', $value)){
                $metaFieldOption = new MetaFieldOption();
                $metaFieldOption->MetaFieldID = $metaField->ID;
                $metaFieldOption->Value = $value;
                $metaFieldOption->Label_NL = $value;
                $metaFieldOption->Label_EN = $value;
                $metaFieldOption->write();
            }
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