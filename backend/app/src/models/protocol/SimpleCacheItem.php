<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;

/**
 *  Dataobject used to cache static or not often changing json to increase encoding of other dataobjects
 */
class SimpleCacheItem extends DataObject {
    private static $table_name = 'SurfSharekit_SimpleCacheItem';

    private static $db = [
        'Json' => 'Text',
        'Key' => 'Varchar(255)' // unique for the connected dataobject, has information on WHAT is cached
    ];

    private static $has_one = [
        'DataObject' => DataObject::class,
        'SecondaryDataObject' => DataObject::class
    ];

    public static function getFor($dataObject, $key, $secondaryDataObject = null) {
        $cache = SimpleCacheItem::get()->filter(['DataObjectID' => $dataObject->ID, 'DataObjectClass' => $dataObject->ClassName, 'Key' => $key]);
        if ($secondaryDataObject) {
            $cache = $cache->filter(['SecondaryDataObjectID' => $secondaryDataObject->ID, 'SecondaryDataObjectClass' => $secondaryDataObject->ClassName]);
        }
        $cacheItem = $cache->first();
        if ($cacheItem && $cacheItem->exists()) {
            return $cacheItem;
        }
        return null;
    }

    public static function cacheFor($dataObject, $key, $arrayOrString, $secondaryDataObject = null) {
        $SimpleCacheItem = SimpleCacheItem::get()->filter(['DataObjectID' => $dataObject->ID, 'DataObjectClass' => $dataObject->ClassName, 'Key' => $key])->first();
        if (!$SimpleCacheItem || !$SimpleCacheItem->exists()) {
            $SimpleCacheItem = new SimpleCacheItem();
        }
        $SimpleCacheItem->DataObject = $dataObject;
        if ($secondaryDataObject) {
            $SimpleCacheItem->SecondaryDataObject = $secondaryDataObject;
        }
        $SimpleCacheItem->Key = $key;
        $SimpleCacheItem->Json = json_encode($arrayOrString);
        $SimpleCacheItem->write();
        return $SimpleCacheItem;
    }

    public function getValue() {
        if ($this->Json) {
            return json_decode($this->Json, true);
        }
        return null;
    }
}