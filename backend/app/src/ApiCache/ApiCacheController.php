<?php

namespace SurfSharekit\ApiCache;

use Exception;
use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\ValidationException;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItem;

class ApiCacheController {
    private static array|null $dataReaderCache = null;

    /**
     * Take either a RepoItem or Person and create an output that's formatted according to the protocol's
     * specifications. To speed up the process, the result is loaded from a cache or stored in the cache if it's
     * not cached yet.
     *
     * @param Protocol $protocol
     * @param RepoItem|Person $item
     * @param bool $purgeCache
     * @return string
     * @throws ReflectionException
     * @throws ValidationException
     */
    public static function getRepoItemData(Protocol $protocol, RepoItem|Person $item, bool $purgeCache): string {
        $cacheData = null;
        if (!$purgeCache) {
            $cacheData = static::loadFromCache($protocol, $item);
        }

        if (!is_null($cacheData)) {
            Logger::debugLog("HIT : " . $item->Uuid);
            return $cacheData;
        } else {
            Logger::debugLog("MISS : " . $item->Uuid);
            $data = static::getUncachedRepoItemData($protocol, $item);
            static::storeToCache($protocol, $item, $data);
            return $data;
        }
    }

    /**
     * Returns the formatted data of a given item without using the cache, using the DataReader that
     * belongs to the given protocol.
     *
     * @throws ReflectionException
     */
    private static function getUncachedRepoItemData(Protocol $protocol, RepoItem|Person $item): String {
        $dataReader = static::getDataReaderForProtocol($protocol);
        if ($dataReader) {
            return $dataReader::extractItemData($item);
        } else {
            throw new Exception("Can't find a data reader for protocol " . $protocol->SystemKey);
        }
    }

    private static function loadFromCache(Protocol $protocol, RepoItem|Person $item): ?string {
        if ($item instanceof RepoItem) {
            $itemType = "RepoItem";
        } else {
            $itemType = "Person";
        }

        $cacheItem = Cache_RecordNode::get()->filter([
            "ProtocolID" => $protocol->ID,
            "{$itemType}ID" => $item->ID,
            "CachedLastEdited" => $item->LastEdited,
            "ProtocolVersion" => $protocol->Version
        ])->first();

        /** @var ?Cache_RecordNode $cacheItem */
        if ($cacheItem && $cacheItem->exists()) {
            return $cacheItem->Data;
        } else {
            return null;
        }
    }

    /**
     * This function is responsible for creating a cache entry for a given protocol and item. To do so,
     * it first uses the DataReader to create the data string that must be cached, and then updates an existing row
     * if an entry for this protocol and repoItem already exists or creates a new row for the pair if it doesn't. In
     * the case of an update the cache's current protocol version, item data and CachedLastEdited are updated.
     *
     * @param Protocol $protocol
     * @param RepoItem|Person $item
     * @param string $data
     * @return void
     * @throws ValidationException
     */
    private static function storeToCache(Protocol $protocol, RepoItem|Person $item, string $data): void {
        if (!$protocol->SystemKey) {
            throw new Exception("Cannot modify cache for protocol $protocol->Title: SystemKey is missing");
        }

        if ($item instanceof RepoItem) {
            $itemType = "RepoItem";
        } else {
            $itemType = "Person";
        }

        $cacheItem = Cache_RecordNode::get()->filter([
            "ProtocolID" => $protocol->ID,
            "{$itemType}ID" => $item->ID
        ])->first();

        /** @var Cache_RecordNode $cacheItem */
        if (!$cacheItem) {
            $cacheItem = Cache_RecordNode::create();
            Logger::debugLog("$protocol->SystemKey : " . $item->Uuid . ' create cache');
            $cacheItem->setField("{$itemType}ID", $item->ID);
            $cacheItem->setField("ProtocolID", $protocol->ID);
        } else {
            Logger::debugLog("$protocol->SystemKey : " . $item->Uuid . ' update cache');
        }

        $cacheItem->setField('Data', $data);
        $cacheItem->setField('ProtocolVersion', $protocol->Version);
        $cacheItem->setField('CachedLastEdited', $item->LastEdited);
        $cacheItem->write();
    }

    /**
     * Returns the class of the ProtocolDataReader class that belongs to the provided protocol, as determined by the
     * {@link ProtocolDataReader::getProtocolClass()} function. This class is then responsible for constructing the
     * data for a given repo item and protocol.
     *
     * @param Protocol $protocol
     * @return string|ProtocolDataReader|null
     * @throws ReflectionException
     * @throws Exception
     */
    private static function getDataReaderForProtocol(Protocol $protocol): string|ProtocolDataReader|null {
        if (!$protocol->SystemKey) {
            throw new Exception("Cannot get a data reader for protocol $protocol->Title: SystemKey is missing");
        }

        if (!is_null(static::$dataReaderCache)) {
            return static::$dataReaderCache[$protocol->SystemKey] ?? null;
        }

        // Build the cache, then call the function again to return the correct data reader
        static::$dataReaderCache = [];

        /** @var array<string|ProtocolDataReader> $candidates */
        $candidates = ClassInfo::implementorsOf(ProtocolDataReader::class);
        foreach ($candidates as $candidate) {
            static::$dataReaderCache[$candidate::getProtocolSystemKey()] = $candidate;
        }

        return static::getDataReaderForProtocol($protocol);
    }
}