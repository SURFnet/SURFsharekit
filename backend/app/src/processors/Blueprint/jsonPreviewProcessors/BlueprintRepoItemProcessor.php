<?php

namespace SilverStripe\processors\Blueprint\jsonPreviewProcessors;

use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;
use SurfSharekit\models\webhooks\exceptions\InvalidTypeException;

class BlueprintRepoItemProcessor extends BlueprintProcessor
{
    protected bool $includeNestedBlueprints;

    public function __construct($dataObject = null, string $type = 'RepoItem', bool $includeNestedBlueprints = true)
    {
        parent::__construct($dataObject, $type);
        $this->includeNestedBlueprints = $includeNestedBlueprints;
    }

    /**
     * Converts the data object into an array structure.
     */
    public function convertDataObjectToArray(): array
    {
        if (!($this->dataObject instanceof RepoItem)) {
            throw new InvalidTypeException('DataObject is not of type RepoItem');
        }

        // Main fields conversion.
        $data = [
            'uuid'                    => $this->dataObject->Uuid,
            'institute'               => $this->dataObject->InstituteUuid,
            'owner'                   => $this->dataObject->OwnerUuid,
            'repoType'                => $this->dataObject->RepoType,
            'status'                  => $this->dataObject->Status,
            'declineReason'           => $this->dataObject->DeclineReason,
            'title'                   => $this->dataObject->Title,
            'alias'                   => $this->dataObject->Alias,
            'subType'                 => $this->dataObject->SubType,
            'subtitle'                => $this->dataObject->Subtitle,
            'language'                => $this->dataObject->Language,
            'isRemoved'               => (bool)$this->dataObject->IsRemoved,
            'pendingForDestruction'   => (bool)$this->dataObject->PendingForDestruction,
            'isArchived'              => (bool)$this->dataObject->IsArchived,
            'isPublic'                => (bool)$this->dataObject->IsPublic,
            'isHistoricallyPublished' => (bool)$this->dataObject->IsHistoricallyPublished,
            'needsToBeFinished'       => (bool)$this->dataObject->NeedsToBeFinished,
            'uploadedFromApi'         => (bool)$this->dataObject->UploadedFromApi,
            'embargoDate'             => $this->dataObject->EmbargoDate,
            'publicationDate'         => $this->dataObject->PublicationDate,
            'accessRight'             => $this->dataObject->AccessRight,
            'repoItemMetaFields'      => $this->convertMetaFieldsToArray(),
        ];

        // Process nested blueprint fields using a configuration array.
        if ($this->includeNestedBlueprints) {
            $nestedConfig = [
                'repoItemPersons' => ['metaFieldJsonKey' => "auteursEnBetrokkenen", 'blueprintType' => "RepoItemPerson"],
                'repoItemLinks'   => ['metaFieldJsonKey' => "link",                   'blueprintType' => "RepoItemLink"],
                'repoItemFiles'   => ['metaFieldJsonKey' => "file",                'blueprintType' => "RepoItemRepoItemFile"]
            ];

            foreach ($nestedConfig as $key => $config) {
                $data[$key] = $this->processNestedMetaField($config['metaFieldJsonKey'], $config['blueprintType']);
            }
        }

        return $data;
    }

    /**
     * Converts the data object to a JSON string.
     */
    public function convertDataObjectToJson(): string
    {
        return $this->createBlueprintJsonResponse($this->convertDataObjectToArray());
    }

    /**
     * Converts all meta fields to an array.
     */
    private function convertMetaFieldsToArray(): array
    {
        return array_map(
            fn(RepoItemMetaField $metaField) => $this->convertMetaFieldToArray($metaField),
            $this->dataObject->RepoItemMetaFields()->toArray()
        );
    }

    /**
     * Converts a single meta field to an array.
     */
    private function convertMetaFieldToArray(RepoItemMetaField $metaField): ?array
    {
        if (!$metaField->exists()) {
            return null;
        }

        $metaFieldValues = array_map(
            fn(RepoItemMetaFieldValue $value) => $this->convertMetaFieldValueToArray($value),
            $metaField->RepoItemMetaFieldValues()->toArray()
        );

        return [
            'metaFieldTitle'   => $metaField->MetaField()->Title,
            'metaFieldJsonKey' => $metaField->MetaField()->JsonKey,
            'metaFieldUuid'    => $metaField->MetaField()->Uuid,
            'metaFieldValues'  => $metaFieldValues
        ];
    }

    /**
     * Converts a meta field value to an array.
     */
    private function convertMetaFieldValueToArray(RepoItemMetaFieldValue $metaFieldValue): ?array
    {
        if (!$metaFieldValue->exists()) {
            return null;
        }

        return [
            'value'                 => $metaFieldValue->Value,
            'isRemoved'             => (bool)$metaFieldValue->IsRemoved,
            'metaFieldOptionUuid'   => $metaFieldValue->MetaFieldOption()->Uuid,
            'repoItemUuid'          => $metaFieldValue->RepoItem()->Uuid,
            'repoItemMetaFieldUuid' => $metaFieldValue->RepoItemMetaField()->Uuid,
            'personUuid'            => $metaFieldValue->Person()->Uuid,
            'instituteUuid'         => $metaFieldValue->Institute()->Uuid,
            'repoItemFile'          => $this->convertRepoItemFileToArray($metaFieldValue->RepoItemFile())
        ];
    }

    /**
     * Converts a repo item file to an array.
     */
    private function convertRepoItemFileToArray(RepoItemFile $repoItemFile): ?array
    {
        if (!$repoItemFile->exists()) {
            return null;
        }

        return [
            'uuid'                 => $repoItemFile->Uuid,
            'link'                 => $repoItemFile->Link,
            's3Key'                => $repoItemFile->S3Key,
            'eTag'                 => trim($repoItemFile->ETag, '"'),
            'objectStoreCheckedAt' => $repoItemFile->ObjectStoreCheckedAt,
            'name'                 => $repoItemFile->Name,
            'title'                => $repoItemFile->Title,
        ];
    }

    /**
     * Process a nested meta field by title and blueprint type.
     * Each nested blueprint object is converted directly to an array,
     * avoiding an encode–decode cycle.
     */
    private function processNestedMetaField(string $metaFieldJsonKey, string $blueprintType): array
    {
        $result = [];
        $targetMetaField = $this->dataObject->RepoItemMetaFields()
            ->filter(['MetaField.JsonKey' => $metaFieldJsonKey])
            ->first();

        if ($targetMetaField) {
            foreach ($targetMetaField->RepoItemMetaFieldValues() as $metaFieldValue) {
                $relatedRepoItem = $metaFieldValue->RepoItem();
                if ($relatedRepoItem && $relatedRepoItem->exists()) {
                    // Create nested processor with nested blueprint processing disabled.
                    $relatedProcessor = new BlueprintRepoItemProcessor($relatedRepoItem, $blueprintType, false);
                    $nestedData = $relatedProcessor->convertDataObjectToArray();
                    // Assuming that the inner structure is wrapped under "data"
                    $result[] = $nestedData['data'] ?? $nestedData;
                }
            }
        }

        return $result;
    }
}