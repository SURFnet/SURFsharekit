<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 29-6-2018
 * Time: 13:52
 */

namespace SurfSharekit\Models\Helper;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;

class Logger {

    static function debugLog($object, $class = "", $function = "") {
        $message = Logger::getMessage($object, $class, $function);
        Injector::inst()->get(LoggerInterface::class)->debug($message);
    }

    private static function getMessage($object, $class, $function) {
        if (is_array($object)) {
            $message = print_r($object, true);
        } elseif (is_object($object)) {
            $message = print_r($object, true);
        } else {
            $message = (string)$object;
        }

        if ($class) {
            $message = $message . " CLASS:: " . $class;
        }

        if ($function) {
            $message = $message . " FUNCTION:: " . $function;
        }

        return $message;
    }

    static function infoLog($object, $class = "", $function = "") {
        $message = Logger::getMessage($object, $class, $function);
        Injector::inst()->get(LoggerInterface::class)->info($message);
    }

    static function warnLog($object, $class = "", $function = "") {
        $message = Logger::getMessage($object, $class, $function);
        Injector::inst()->get(LoggerInterface::class)->warning($message);
    }

    static function errorLog($object, $class = "", $function = "") {
        $message = Logger::getMessage($object, $class, $function);
        Injector::inst()->get(LoggerInterface::class)->error($message);
    }
}