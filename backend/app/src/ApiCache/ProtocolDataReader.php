<?php

namespace SurfSharekit\ApiCache;

use SurfSharekit\Models\Person;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItem;

/**
 * Base interface to deal with reading to and from the cache for a specific protocol, so that everything happens in a central location.
 * Each protocol that wishes to cache data should implement this interface, the ApiCacheController will then automatically recognise
 * it as such and direct data calls to the correct protocol.
 */
interface ProtocolDataReader {
    public static function getProtocolSystemKey(): String;

    public static function extractItemData(RepoItem|Person $item);
}