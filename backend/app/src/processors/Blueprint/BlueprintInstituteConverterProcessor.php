<?php

namespace SilverStripe\processors\Blueprint;

use SilverStripe\Security\Security;
use SurfSharekit\Models\Institute;

class BlueprintInstituteConverterProcessor extends BlueprintConverterProcessor
{

    public function getTargetClass() {
        return Institute::class;
    }

    public function convert($blueprintObject) {
        $json = json_decode($blueprintObject->JSON, true);
        if (!$json || !isset($json['data'])) {
            return null;
        }

        $data = $json['data'];

        // If Institute exists, overwrite. Else, create a new Institute object
        $institute = Institute::get()->filter('Uuid', $data['uuid'])->first();
        if (!$institute) {
            $institute = Institute::create();
        }

        $parentInstitute = Institute::get()->filter('Uuid', $data['parentInstitute'])->first();

        if ($parentInstitute === null) {
            if (!empty($data['level']) && !in_array($data['level'], ['organisation', 'consortium'])) {
                return null;
            }
        }

        $institute->InstituteUuid = $parentInstitute->Uuid ?? null;
        $institute->InstituteID = $parentInstitute->ID ?? 0;
        $institute->Uuid = $data['uuid'] ?? '';
        $institute->Title = $data['title'] ?? '';
        $institute->LicenseActive = $data['licenseActive'] ?? false;
        $institute->ConextCode = $data['conextCode'] ?? '';
        $institute->ROR = $data['ror'] ?? '';
        $institute->Abbreviation = $data['abbreviation'] ?? '';
        $institute->Level = $data['level'] ?? null;
        $institute->Type = $data['type'] ?? null;
        $institute->IsRemoved = $data['isRemoved'] ?? false;
        $institute->IsHidden = $data['isHidden'] ?? false;
        $institute->UpdateInstituteLabels = $data['updateInstituteLabels'] ?? false;
        $institute->Description = $data['description'] ?? '';
        $institute->GeneratedThroughBlueprint = true;
        $institute->GeneratedBy = Security::getCurrentUser()->getTitle();

        return $institute;
    }
}