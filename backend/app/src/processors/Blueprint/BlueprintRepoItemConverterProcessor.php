<?php

namespace SilverStripe\processors\Blueprint;

use SilverStripe\Assets\File;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use SurfSharekit\constants\RepoItemTypeConstant;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;

class BlueprintRepoItemConverterProcessor extends BlueprintConverterProcessor
{
    public function getTargetClass(): string
    {
        return RepoItem::class;
    }

    /**
     * Convert a blueprint object to a RepoItem.
     */
    public function convert($blueprint): ?RepoItem
    {
        $data = $this->extractBlueprintData($blueprint);
        if (!$data) {
            return null;
        }

        // This block is to make sure everything is deleted without deleting the RepoItemFiles associated with this item.
        // It is far from ideal, but prevents us to
        if (!empty($data['uuid'])) {
            $existingRepoItem = RepoItem::get()->filter(['Uuid' => $data['uuid'], "RepoType" => RepoItemTypeConstant::PRIMARY_TYPES])->first();
            if ($existingRepoItem) {
                // Loop through each RepoItemMetaField associated with the RepoItem
                $repoItemMetaFields = $existingRepoItem->RepoItemMetaFields();
                foreach ($repoItemMetaFields as $repoItemMetaField) {
                    $repoItemMetaFieldValues = $repoItemMetaField->RepoItemMetaFieldValues();
                    foreach ($repoItemMetaFieldValues as $repoItemMetaFieldValue) {
                        if (!$repoItemMetaFieldValue->IsRemoved) {
                            $repoItemMetaFieldValue->DeleteFromUploadApiPatch = true;
                            $repoItemMetaFieldValue->IsRemoved = true;
                            $repoItemMetaFieldValue->write();
                        }
                        $repoItemMetaFieldValue->delete();
                    }
                    $repoItemMetaField->delete();
                }
                DB::prepared_query("DELETE FROM SurfSharekit_RepoItem WHERE ID = ?", [$existingRepoItem->ID]);
            }
        }

        // Process subrepo items first (e.g. for persons, links, files)
        $this->processSubRepoItems($data, 'repoItemPersons', 'RepoItemPerson');
        $this->processSubRepoItems($data, 'repoItemLinks', 'RepoItemLink');
        $this->processSubRepoItems($data, 'repoItemFiles', 'RepoItemRepoItemFile');

        // Create and update the main RepoItem.
        $repoItem = $this->createMainRepoItem($data);
        if (!$repoItem) {
            return null;
        }
        $repoItem = $this->updateRepoItem($repoItem, $data);

        // Process meta fields and their values.
        $this->processMetaFields($data, $repoItem);


        return $repoItem;
    }

    /**
     * Extracts and normalizes the blueprint data.
     */
    private function extractBlueprintData($blueprint): ?array
    {
        $json = json_decode($blueprint->JSON, true);
        if (!$json) {
            return null;
        }
        
        return isset($json['data']) ? $json['data'] : $json;
    }

    /**
     * Creates the main RepoItem by looking up the associated Institute.
     */
    private function createMainRepoItem(array $data): ?RepoItem
    {
        $institute = Institute::get()->filter('Uuid', $data['institute'] ?? '')->first();
        if (!$institute) {
            return null;
        }

        $owner = Person::get()->filter('Uuid', $data['owner'] ?? '')->first();
        if (!$owner) {
            return null;
        }

        $repoItem = new RepoItem();
        if (!empty($data['uuid'])) {
            $repoItem->Uuid = $data['uuid'];
        }
        $repoItem->OwnerID     = $owner->ID;
        $repoItem->RepoType    = $data['repoType'];
        $repoItem->InstituteID = $institute->ID;
        $repoItem->write();

        return $repoItem;
    }

    /**
     * Processes subrepo items (nested RepoItems) recursively.
     *
     * If a sub-item does not include a "uuid", we assume it’s new.
     */
    private function processSubRepoItems(array $data, string $key, string $repoType): void
    {
        if (!empty($data[$key]) && is_array($data[$key])) {
            foreach ($data[$key] as $subData) {
                // If a uuid exists, attempt to see if the item already exists.
                if (!empty($subData['uuid'])) {
                    $existing = RepoItem::get()->filter('Uuid', $subData['uuid'])->first();
                    if ($existing) {
                        continue;
                    }
                }
                // Set the repo type for the sub-item.
                $subData['repoType'] = $repoType;
                $subBlueprint = $this->buildSubBlueprint($subData);
                $this->convert($subBlueprint);
            }
        }
    }

    /**
     * Helper method to build a temporary blueprint object from sub-data.
     */
    private function buildSubBlueprint(array $subData): \stdClass
    {
        $blueprint = new \stdClass();
        $blueprint->JSON = json_encode($subData);
        return $blueprint;
    }

    /**
     * Updates the RepoItem with properties from the blueprint data.
     */
    private function updateRepoItem(RepoItem $repoItem, $data): RepoItem
    {
        $repoItem->setField('Status', $data['status'] ?? 'Draft');
        $repoItem->DeclineReason           = $data['declineReason'] ?? '';
        $repoItem->SubType                 = $data['subType'] ?? null;
        $repoItem->Subtitle                = $data['subtitle'] ?? null;
        $repoItem->Title                   = $data['title'] ?? null;
        $repoItem->Alias                   = $data['alias'] ?? null;
        $repoItem->Language                = $data['language'] ?? null;
        $repoItem->IsRemoved               = $data['isRemoved'] ?? false;
        $repoItem->PendingForDestruction   = $data['pendingForDestruction'] ?? false;
        $repoItem->IsArchived              = $data['isArchived'] ?? false;
        $repoItem->IsPublic                = $data['isPublic'] ?? false;
        $repoItem->IsHistoricallyPublished = $data['isHistoricallyPublished'] ?? false;
        $repoItem->NeedsToBeFinished       = $data['needsToBeFinished'] ?? false;
        $repoItem->UploadedFromApi         = $data['uploadedFromApi'] ?? false;
        $repoItem->EmbargoDate             = $data['embargoDate'] ?? null;
        $repoItem->PublicationDate         = $data['publicationDate'] ?? null;
        $repoItem->AccessRight             = $data['accessRight'] ?? null;
        $repoItem->SkipValidation          = true;
        $repoItem->GeneratedThroughBlueprint = true;
        $repoItem->GeneratedBy             = Security::getCurrentUser()->Email;
        $repoItem->write();

        return $repoItem;
    }

    /**
     * Processes meta fields and their nested values.
     */
    private function processMetaFields(array $data, RepoItem $repoItem): void
    {
        if (empty($data['repoItemMetaFields']) || !is_array($data['repoItemMetaFields'])) {
            return;
        }

        foreach ($data['repoItemMetaFields'] as $metaFieldData) {
            $metaField = MetaField::get()->filter('JsonKey', $metaFieldData['metaFieldJsonKey'] ?? '')->first();
            if (!$metaField instanceof MetaField) {
                continue;
            }

            $repoItemMetaField = $this->getOrCreateRepoItemMetaField($metaField, $repoItem);

            if (!empty($metaFieldData['metaFieldValues']) && is_array($metaFieldData['metaFieldValues'])) {
                foreach ($metaFieldData['metaFieldValues'] as $metaFieldValueData) {
                    $this->createRepoItemMetaFieldValue($metaFieldValueData, $repoItemMetaField);
                }
            }
        }
    }

    /**
     * Creates a RepoItemMetaField linking a MetaField with a RepoItem.
     */
    private function getOrCreateRepoItemMetaField(MetaField $metaField, RepoItem $repoItem): RepoItemMetaField
    {
        $existing = RepoItemMetaField::get()->filter([
            'MetaFieldID' => $metaField->ID,
            'RepoItemID'  => $repoItem->ID,
        ])->first();
        if ($existing && $existing instanceof RepoItemMetaField) {
            return $existing;
        }

        $repoItemMetaField = RepoItemMetaField::create();
        $repoItemMetaField->MetaFieldID = $metaField->ID;
        $repoItemMetaField->RepoItemID  = $repoItem->ID;
        $repoItemMetaField->write();
        return $repoItemMetaField;
    }

    /**
     * Creates a RepoItemMetaFieldValue based on the blueprint data.
     */
    private function createRepoItemMetaFieldValue(array $metaFieldValue, RepoItemMetaField $repoItemMetaField) {
        $repoItemMetaFieldValue = RepoItemMetaFieldValue::create();
        $repoItemMetaFieldValue->Value = $metaFieldValue['value'] ?? null;
        $repoItemMetaFieldValue->IsRemoved = $metaFieldValue['isRemoved'] ?? false;

        // Safely retrieve related IDs
        $metaFieldOption = MetaFieldOption::get()->filter('Uuid', $metaFieldValue['metaFieldOptionUuid'])->first();
        $repoItemMetaFieldValue->MetaFieldOptionID = $metaFieldOption ? $metaFieldOption->ID : 0;

        $repoItem = RepoItem::get()->filter('Uuid', $metaFieldValue['repoItemUuid'])->first();
        $repoItemMetaFieldValue->RepoItemID = $repoItem ? $repoItem->ID : 0;

        $person = Person::get()->filter('Uuid', $metaFieldValue['personUuid'])->first();
        $repoItemMetaFieldValue->PersonID = $person ? $person->ID : 0;

        $institute = Institute::get()->filter('Uuid', $metaFieldValue['instituteUuid'])->first();
        $repoItemMetaFieldValue->InstituteID = $institute ? $institute->ID : 0;

        $repoItemFile = RepoItemFile::get()->filter("Uuid", $metaFieldValue['repoItemFile']['uuid'] ?? null)->first();
        $repoItemMetaFieldValue->RepoItemFileID = $repoItemFile ? $repoItemFile->ID : 0;

        $repoItemMetaFieldValue->RepoItemMetaFieldID = $repoItemMetaField->ID;
        $repoItemMetaFieldValue->write();
    }
}