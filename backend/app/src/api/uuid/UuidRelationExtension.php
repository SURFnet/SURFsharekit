<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\EnvironmentExport\DataObjects\EnvironmentExportRequest;
use SurfSharekit\Models\Helper\Logger;

/**
 * Class UuidRelationExtension
 * This extension automatically creates UUID fields for ID fields and updates those relations
 */
class UuidRelationExtension extends Extension {

    /**
     * @param $class
     * @param $extension
     * @param $args
     * @return array|string[][]
     * This function somehow gets called due to it having the same fingerprint as the function @see Hierarchy::get_extra_config()
     */
    public static function get_extra_config($class, $extension, $args) {
        if($class == 'SurfSharekit\\Models\\Institute'){
            return ['db' => ['InstituteUuid' => DBUuid::class]];
        }
        if($class == 'SurfSharekit\\Models\\RepoItem'){
            return ['db' => ['InstituteUuid' => DBUuid::class, 'OwnerUuid' => DBUuid::class]];
        }
        if($class == 'SurfSharekit\\Models\\RepoItemSummary'){
            return ['db' => ['InstituteUuid' => DBUuid::class, 'OwnerUuid' => DBUuid::class]];
        }
        if($class == 'SurfSharekit\\Models\\MetaField'){
            return ['db' => ['MetaFieldTypeUuid' => DBUuid::class]];
        }

        $getUuidFields = static::getUuidFields($class);

        return ['db' => $getUuidFields];
    }

    private static function getUuidFields($class) {
        $extensionSourceConfig = Config::inst()->get($class, null, true);

        $hasOneUuidFields = [];

        // Get all has_ones from extensions manually, because we can't make use of config's ExtensionMiddleware
        $classExtensions = $extensionSourceConfig['extensions'] ?? [];
        $hasOneList = $extensionSourceConfig['has_one'] ?? [];
        foreach ($classExtensions as $extension) {
            $hasOneList = array_merge($hasOneList, Config::forClass($extension)->get('has_one') ?? []);
        }

        foreach ($hasOneList as $relationName => $relationClass) {
            if($relationName != 'CreatedBy' and $relationName != 'ModifiedBy') {
                try {
                    $relationExtensions = Config::inst()
                        ->get($relationClass, 'extensions', Config::EXCLUDE_EXTRA_SOURCES) ?? [];

                    if (in_array(UuidExtension::class, $relationExtensions)) {
                        $hasOneUuidFields[$relationName . 'Uuid'] = DBUuid::class;
                    }
                } catch (Exception $e) {
                    Logger::errorLog("$class " . $e->getMessage());
                }
            }
        }

        return $hasOneUuidFields;
    }

    public function onBeforeWrite() {
        foreach (array_keys($this->owner->hasOne()) as $relationName) {
            if($relationName != 'CreatedBy' and $relationName != 'ModifiedBy') {
                $relatedObject = $this->owner->$relationName();
                if ($relatedObject && $relatedObject->exists()) {
                    $relationUuidField = $relationName . 'Uuid';
                    $this->owner->$relationUuidField = $relatedObject->Uuid;
                }
            }
        }
    }
}