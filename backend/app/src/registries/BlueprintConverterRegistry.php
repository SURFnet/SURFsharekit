<?php

namespace SilverStripe\registries;

use SilverStripe\processors\Blueprint\BlueprintConverterProcessor;

class BlueprintConverterRegistry
{
    private static $converters = [];

    public static function register($blueprintClass, BlueprintConverterProcessor $converter)
    {
        self::$converters[$blueprintClass] = $converter;
    }

    public static function getConverter($blueprintClass)
    {
        return self::$converters[$blueprintClass] ?? null;
    }
}