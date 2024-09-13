<?php
namespace SurfSharekit\Models;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;
use SurfSharekit\Models\Helper\Logger;

const GENERAL_LOG = 'general';
const AUTHENTICATION_LOG = 'authentication';

class LogItem extends DataObject implements PermissionProvider
{
    use \PermissionProviderTrait;

    private static $table_name = 'SurfSharekit_Log';

    private static $db = [
        "Type" => "Enum('". GENERAL_LOG .",". AUTHENTICATION_LOG ."', '". GENERAL_LOG ."')",
        "Class" => "Varchar(255)",
        "Function" => "Varchar(255)",
        "Content" => "Text"
    ];

    private static $summary_fields = [
        "Created" => "Timestamp",
        "Type",
        "Class",
        "Function",
        "ContentSummary" => "Content",
    ];

    private static $indexes = [
        'Type' => true,
        'Created' => true
    ];

    private static $default_sort = 'Created DESC';

    public function ContentSummary() {
        return $this->Content;
    }

    public function canCreate($member = null, $context = []) {
        return false;
    }

    public function canEdit($member = null, $context = []) {
        return false;
    }

    public static function debugLog($object, $class = "", $function = "", $type = GENERAL_LOG) {
        if (is_array($object)) {
            foreach ($object as $line) {
                Logger::debugLog($line, $class, $function);
            }
        } else {
            Logger::debugLog($object, $class, $function);
        }

        self::create([
            "Type" => $type,
            "Class" => $class,
            "Function" => $function,
            "Content" => self::objectToString($object),
        ])->write();
    }

    public static function infoLog($object, $class = "", $function = "", $type = GENERAL_LOG) {
        if (is_array($object)) {
            foreach ($object as $line) {
                Logger::infoLog($line, $class, $function);
            }
        } else {
            Logger::infoLog($object, $class, $function);
        }

        self::create([
            "Type" => $type,
            "Class" => $class,
            "Function" => $function,
            "Content" => self::objectToString($object),
        ])->write();
    }

    public static function warnLog($object, $class = "", $function = "", $type = GENERAL_LOG) {
        if (is_array($object)) {
            foreach ($object as $line) {
                Logger::warnLog($line, $class, $function);
            }
        } else {
            Logger::warnLog($object, $class, $function);
        }

        self::create([
            "Type" => $type,
            "Class" => $class,
            "Function" => $function,
            "Content" => self::objectToString($object),
        ])->write();
    }

    public static function errorLog($object, $class = "", $function = "", $type = GENERAL_LOG) {
        if (is_array($object)) {
            foreach ($object as $line) {
                Logger::errorLog($line, $class, $function);
            }
        } else {
            Logger::errorLog($object, $class, $function);
        }

        self::create([
            "Type" => $type,
            "Class" => $class,
            "Function" => $function,
            "Content" => self::objectToString($object),
        ])->write();
    }

    private static function objectToString($object) {
        if (is_array($object)) {
            $newObject = '';
            foreach ($object as $line) {
                if (!is_array($line)){
                    $newObject .= $line . PHP_EOL;
                }
            }

            return $newObject;
        }

        return $object;
    }
}