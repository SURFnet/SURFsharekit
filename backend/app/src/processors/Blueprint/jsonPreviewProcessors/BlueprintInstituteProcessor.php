<?php

namespace SilverStripe\processors\Blueprint\jsonPreviewProcessors;

use SurfSharekit\Models\Institute;
use SurfSharekit\models\webhooks\exceptions\InvalidTypeException;

class BlueprintInstituteProcessor extends BlueprintProcessor
{
    public function convertDataObjectToJson(): string {
        if (!($this->dataObject instanceof Institute)){
            throw new InvalidTypeException('DataObject is not of type institute');
        }

        $data = [
            'uuid' => $this->dataObject->Uuid,
            'parentInstitute' => $this->dataObject->Institute()->Uuid,
            'title' => $this->dataObject->Title,
            'licenseActive' => $this->dataObject->LicenseActive,
            'conextCode' => $this->dataObject->ConextCode,
            'ror' => $this->dataObject->ROR,
            'abbreviation' => $this->dataObject->Abbreviation,
            'level' => $this->dataObject->Level,
            'type' => $this->dataObject->Type,
            'isRemoved' => $this->dataObject->IsRemoved,
            'isHidden' => $this->dataObject->IsHidden,
            'updateInstituteLabels' => $this->dataObject->UpdateInstituteLabels,
            'description' => $this->dataObject->Description
        ];

        return $this->createBlueprintJsonResponse($data);
    }
}