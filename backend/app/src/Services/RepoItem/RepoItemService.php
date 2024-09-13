<?php

namespace SilverStripe\Services\RepoItem;

use SilverStripe\api\Upload\Processors\MetaFieldProcessor;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Services\Person\PersonService;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;

class RepoItemService implements IRepoItemService {
    use Injectable;
    use Configurable;

    public static $metaFieldBlacklist = ['Switch-row', 'Tree-MultiSelect', 'RepoItems'];

    public function createRepoItem(string $ownerUuid, string $instituteUuid, string $repoItemType): RepoItem {
        $personService = PersonService::create();
        if (!$person = $personService->getPerson($ownerUuid)) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002, "Could not find person $ownerUuid");
        }

        if (!$institute = Institute::get()->find('Uuid', $instituteUuid)) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002, "Could not find institute $instituteUuid");
        }

        $repoItem = new RepoItem();
        $repoItem->OwnerID = $person->ID;
        $repoItem->RepoType = $repoItemType;
        $repoItem->InstituteID = $institute->ID;

        $repoItem->UploadedFromApi = true;
        $repoItem->write();

        return $repoItem;
    }

    public function getMetaData(RepoItem $repoItem, ?string $rootInstituteUuid = null) {
        /** @var MetaFieldProcessor[] $metaFieldProcessorCache */
        $metaFieldProcessorCache = [];
        $metaFieldResponses = [];

        $repoItemMetaFieldValues = $repoItem->getAllRepoItemMetaFieldValues();
        foreach ($repoItemMetaFieldValues as $repoItemMetaFieldValue) {
            $repoItemMetaField = RepoItemMetaField::get()->find('Uuid', $repoItemMetaFieldValue->RepoItemMetaFieldUuid);

            /** @var MetaFieldProcessor $processorClass */
            $metaField = MetaField::get()->find('Uuid', $repoItemMetaField->MetaFieldUuid);

            // Check if MetaFieldType.Key is in the blacklist
            if (in_array($metaField->MetaFieldType->Key, static::$metaFieldBlacklist)) {
                continue; // Skip this iteration if the key is blacklisted
            }

            $processor = $metaFieldProcessorCache[$metaField->JsonKey] ?? null;
            if (!$processor) {
                $processorClass = $this->resolveMetaFieldProcessorClassByMetaFieldType($metaField->MetaFieldType->Key);
                $processor = $processorClass::create($repoItem, $metaField, null, $rootInstituteUuid);
            }

            // Get every type of RepoItemMetaFields
            $metaFieldResponses[strtolower($metaField->JsonKey)] = $processor->convertValueToJson($repoItemMetaField);

            $metaFieldProcessorCache[$metaField->JsonKey] = $processor;
        }

        return $metaFieldResponses;
    }

    public function addMetaData(RepoItem $repoItem, array $metaData = [], ?string $rootInstituteUuid = null): void {
        /** @var MetaFieldProcessor[] $metaFieldProcessorCache */
        $metaFieldProcessorCache = [];

        foreach ($metaData as $fieldName => $value) {
            /** @var MetaField $metaField */
            if (null === $metaField = MetaField::get()->find('JsonKey', $fieldName)) {
                throw new BadRequestException(ApiErrorConstant::GA_BR_004, "$fieldName is not an existing MetaField");
            }

            // Check if MetaFieldType.Key is in the blacklist
            if (in_array($metaField->MetaFieldType->Key, static::$metaFieldBlacklist)) {
                continue; // Skip this iteration if the key is blacklisted
            }

            $processor = $metaFieldProcessorCache[$fieldName] ?? null;
            if (!$processor) {
                /** @var MetaFieldProcessor $processorClass */
                $processorClass = MetaFieldProcessor::resolveMetaFieldProcessorClassByMetaFieldType($metaField->MetaFieldType()->Key);
                $processor = $processorClass::create($repoItem, $metaField, $value, $rootInstituteUuid);
            }

            $validationResult = $processor->validate();
            if ($validationResult->hasErrors()) {
                Throw new BadRequestException(ApiErrorConstant::GA_BR_001,
                    "$fieldName: " . implode(', ', $validationResult->getErrors())
                );
            }

            $metaFieldProcessorCache[$fieldName] = $processor;
            $repoItemMetaField = $this->findOrCreateRepoItemMetaField($repoItem, $metaField);
            $processor->save($repoItemMetaField);
        }
    }

    private function resolveMetaFieldProcessorClassByMetaFieldType(string $type): string {
        $processorClass = null;

        foreach (ClassInfo::subclassesFor(MetaFieldProcessor::class, false) as $class) {
            if (Config::forClass($class)->get('type') === $type) {
                $processorClass = $class;
                break;
            }
        }

        if ($processorClass == null) {
            throw new \Exception("Meta field with type $type processor not found");
        }

        return $processorClass;
    }

    public function findOrCreateRepoItemMetaField(RepoItem $repoItem, MetaField $metaField): RepoItemMetaField {
        /** @var RepoItemMetaField|null $existingRepoItemMetaField */
        $existingRepoItemMetaField = RepoItemMetaField::get()->filter([
            "RepoItemID" => $repoItem->ID,
            "MetaFieldID" => $metaField->ID
        ])->first();

        if ($existingRepoItemMetaField && $existingRepoItemMetaField->exists()) {
            return $existingRepoItemMetaField;
        }

        $repoItemMetaField = new RepoItemMetaField();
        $repoItemMetaField->MetaFieldID = $metaField->ID;
        $repoItemMetaField->RepoItemID = $repoItem->ID;
        $repoItemMetaField->write();

        return $repoItemMetaField;
    }

    public function createRepoItemMetaFieldValue(RepoItemMetaField $repoItemMetaField): RepoItemMetaFieldValue {
        $repoItemMetaFieldValue = new RepoItemMetaFieldValue();
        $repoItemMetaFieldValue->RepoItemMetaFieldID = $repoItemMetaField->ID;
        $repoItemMetaFieldValue->write();

        return $repoItemMetaFieldValue;
    }

    public function findByUuid(string $repoItemUuid): ?RepoItem {
        return RepoItem::get()->find("Uuid", $repoItemUuid);
    }

    public function changeRepoItemStatus(RepoItem $repoItem, string $status): void {
        $repoItem->Status = $status;
        $repoItem->write();
    }
}