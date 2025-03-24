<?php

namespace SilverStripe\Piwik;

use Exception;
use SilverStripe\constants\ApplicationEnvironment;
use SilverStripe\Core\Environment;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Piwik\CustomEventDimension;

class PiwikCustomDimensionMapping {

    private static array $devMapping = [
        CustomEventDimension::REPO_ITEM_FILE_ID => [8, 1],
        CustomEventDimension::REPO_ITEM_ID => [9, 2],
        CustomEventDimension::REPO_TYPE => [10, 3],
        CustomEventDimension::ROOT_INSTITUTE_ID => [11, 4],
        CustomEventDimension::UTM_SOURCE => [12, 5],
        CustomEventDimension::UTM_CONTENT => [13, 6],
        CustomEventDimension::REPO_ITEM_LINK_ID => [14, 7]
    ];

    private static array $tstMapping = [
        CustomEventDimension::REPO_ITEM_FILE_ID => [1, 1],
        CustomEventDimension::REPO_ITEM_ID => [2, 2],
        CustomEventDimension::REPO_TYPE => [3, 3],
        CustomEventDimension::ROOT_INSTITUTE_ID => [4, 4],
        CustomEventDimension::UTM_SOURCE => [5, 5],
        CustomEventDimension::UTM_CONTENT => [6, 6],
        CustomEventDimension::REPO_ITEM_LINK_ID => [7, 7],
    ];

    private static array $accMapping = [
        CustomEventDimension::REPO_ITEM_FILE_ID => [8, 1],
        CustomEventDimension::REPO_ITEM_ID => [9, 2],
        CustomEventDimension::REPO_TYPE => [10, 3],
        CustomEventDimension::ROOT_INSTITUTE_ID => [11, 4],
        CustomEventDimension::UTM_SOURCE => [12, 5],
        CustomEventDimension::UTM_CONTENT => [13, 6],
        CustomEventDimension::REPO_ITEM_LINK_ID => [14, 7],
    ];

    private static array $prdMapping = [
        CustomEventDimension::REPO_ITEM_FILE_ID => [8, 1],
        CustomEventDimension::REPO_ITEM_ID => [9, 2],
        CustomEventDimension::REPO_TYPE => [10, 3],
        CustomEventDimension::ROOT_INSTITUTE_ID => [11, 4],
        CustomEventDimension::UTM_SOURCE => [12, 5],
        CustomEventDimension::UTM_CONTENT => [13, 6],
        CustomEventDimension::REPO_ITEM_LINK_ID => [14, 7],
    ];

    public static function getDevMapping(): array {
        return PiwikCustomDimensionMapping::$devMapping;
    }

    public static function getTstMapping(): array {
        return PiwikCustomDimensionMapping::$tstMapping;
    }

    public static function getAccMapping(): array {
        return PiwikCustomDimensionMapping::$accMapping;
    }

    public static function getPrdMapping(): array {
        return PiwikCustomDimensionMapping::$prdMapping;
    }

    public static function getCustomDimension(string $eventName): CustomEventDimension {
        $environment = Environment::getEnv("APPLICATION_ENVIRONMENT");
        $values = null;
        switch ($environment) {
            case ApplicationEnvironment::DEV: {
                $values = PiwikCustomDimensionMapping::$devMapping[$eventName] ?? null;
                break;
            }
            case ApplicationEnvironment::TST: {
                $values = PiwikCustomDimensionMapping::$tstMapping[$eventName] ?? null;
                break;
            }
            case ApplicationEnvironment::ACC: {
                $values = PiwikCustomDimensionMapping::$accMapping[$eventName] ?? null;
                break;
            }
            case ApplicationEnvironment::PRD: {
                $values = PiwikCustomDimensionMapping::$prdMapping[$eventName] ?? null;
                break;
            }
        }

        if ($values === null) {
            Logger::infoLog("Could not find a custom dimension with name '$eventName'");
            throw new Exception("Could not find a custom dimension with name '$eventName'");
        }

        [$id, $slot] = $values;
        return new CustomEventDimension($id, $slot);
    }

}