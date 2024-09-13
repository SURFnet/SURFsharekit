<?php

namespace SurfSharekit\Services\Discover;


use SilverStripe\api\Upload\Data\GetInstitutesRequest;
use SilverStripe\ORM\DataList;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Api\Upload\UploadApiController;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;

class DiscoverUploadService implements IDiscoverUploadService {


    public static $metaFieldBlacklist = ['Switch-row', 'Tree-MultiSelect', 'RepoItems'];

    public function getInstitutes($instituteUuid): DataList {
        return Institute::getAllChildInstitutes($instituteUuid, true)->sort("Level");
    }

    /*
     * Metafield functions
     */
    public function getMetafields($instituteUuid, $allowedRepoTypes): DataList {
        $institutes = InstituteScoper::getInstitutesOfLowerScope([$instituteUuid]);

        $instituteIds = implode(",", $institutes->getIDList());
        $allowedRepoTypesList = implode("','", $allowedRepoTypes);
        $metaFieldBlacklistSQLString = implode("','", static::$metaFieldBlacklist);

        return MetaField::get()
            ->innerJoin('SurfSharekit_TemplateMetaField', 'SurfSharekit_TemplateMetaField.MetaFieldUuid = SurfSharekit_MetaField.Uuid')
            ->innerJoin('SurfSharekit_Template', 'SurfSharekit_TemplateMetaField.TemplateUuid = SurfSharekit_Template.Uuid')
            ->innerJoin('SurfSharekit_MetaFieldType', 'SurfSharekit_MetaField.MetaFieldTypeID = SurfSharekit_MetaFieldType.ID')
            ->where([
                "SurfSharekit_Template.InstituteID IN ($instituteIds)",
                "SurfSharekit_Template.RepoType IN ('$allowedRepoTypesList')",
                'SurfSharekit_MetaField.JsonKey IS NOT NULL',
                "`SurfSharekit_MetaFieldType`.`Key` NOT IN ('$metaFieldBlacklistSQLString')"
            ]);

    }

    /*
     * This function returns either json or a string based on the value of 'Example' within the MetaFieldJsonExample object.
     */

    public function checkAndReturnValue($value){
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        } else {
            return $value;
        }
    }

    public function getMetaFieldOptions($metafield) {
        $metafieldOptions = $metafield->MetaFieldOptions()->limit(100);
        $output = [];

        if (count($metafieldOptions) > 0) {
            foreach ($metafieldOptions as $metafieldOption) {
                $output[] = [
                    'id' => $metafieldOption->Uuid,
                    'value' => $metafieldOption->Value
                ];
            }
        }

        return $output;
    }

    public function getArrayOfMetaFieldOptions(MetaField $metafield): array {
        $options = $metafield->MetaFieldOptions();
        $result = [];
        foreach ($options as $option) {
            $result[] = $this->collectUuids($option);
        }
        return $result;
    }

    private function collectUuids(MetaFieldOption $option): array {
        $uuids = [
            'Uuid' => $option->Uuid,
            'Children' => []
        ];
        $children = $option->MetaFieldOptions();
        foreach ($children as $child) {
            $uuids['Children'][] = $this->collectUuids($child);
        }
        return $uuids;
    }

    public function getLevelBasedInstitutesUuids(string $instituteUuid, $level): array {
        return InstituteScoper::getInstitutesOfLowerScope([$instituteUuid])->filter('Level', $level)->sort('Title', 'DESC')->column('Uuid');
    }
}