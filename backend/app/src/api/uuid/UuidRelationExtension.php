<?php

use SilverStripe\ORM\DataExtension;
use SurfSharekit\Models\Helper\Logger;

/**
 * Class UuidRelationExtension
 * This extension automatically creates UUID fields for ID fields and updates those relations
 */
class UuidRelationExtension extends DataExtension {
    public static $relationChecking = null; //Used to block recursion

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
        //Recursion blocker
        if (static::$relationChecking == $class) {
            return [];
        }

        static::$relationChecking = $class;

        $getUuidFields = static::getUuidFields($class);

        static::$relationChecking = null; //Clearing recursion block, needed to allow next class that uses this extension to use this method

        return ['db' => $getUuidFields];
    }

    private static function getUuidFields($class) {
        /**
         * @var \SilverStripe\ORM\DataObject $dataObject
         */
        $dataObject = $class::create();

        $hasOneUuidFields = [];

        foreach ($dataObject->hasOne() as $relationName => $relationClass) {
            if($relationName != 'CreatedBy' and $relationName != 'ModifiedBy') {
                try {
                    /**
                     * @var \SilverStripe\ORM\DataObject $instanceOfRelatedType
                     */
                    $instanceOfRelatedType = $relationClass::create();//must set singleton to true
                    if ($instanceOfRelatedType->hasExtension(UuidExtension::class)) {
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
        parent::onBeforeWrite();

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