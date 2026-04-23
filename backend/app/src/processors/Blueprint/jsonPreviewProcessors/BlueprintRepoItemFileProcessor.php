<?php

namespace SilverStripe\processors\Blueprint\jsonPreviewProcessors;

use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\models\webhooks\exceptions\InvalidTypeException;

class BlueprintRepoItemFileProcessor extends BlueprintProcessor
{

    public function convertDataObjectToJson(): string {

        if (!($this->dataObject instanceof RepoItemFile)) {
            throw new InvalidTypeException('DataObject is not of type person');
        }

        $data = [
            'uuid' => $this->dataObject->Uuid,
            'link' => $this->dataObject->Link,
            's3Key' => $this->dataObject->S3Key,

            // Escape double quotes in ETag so that the JSON remains valid.
            // Without this, the inner quotes would break the JSON string formatting.
            'etag' => str_replace('"', '\\"', $this->dataObject->ETag),
            'objectStoreCheckedAt' => $this->dataObject->ObjectStoreCheckedAt,
        ];

        return $this->createBlueprintJsonResponse($data);
    }
}