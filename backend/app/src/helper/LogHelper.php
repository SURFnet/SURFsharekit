<?php

use SurfSharekit\Models\Helper\Logger;

class LogHelper {
    static $logs = [];
    static $lastTime = 0;

    public static function logTime($key, $keyCompare = null) {
        LogHelper::$logs[$key] = round(microtime(true) * 1000);
        LogHelper::$logs[$key . "_dif"] = LogHelper::$logs[$key] - ($keyCompare ? LogHelper::$logs[$keyCompare] : LogHelper::$lastTime);
        LogHelper::$lastTime = LogHelper::$logs[$key];
    }

    public static function exit() {
        exit(json_encode(LogHelper::$logs));
    }

    public static function writeToLog() {
        foreach (LogHelper::$logs as $k => $v) {
            if (strpos($k,"end_dif") !== false){
                Logger::infoLog($k . ' = ' . $v);
            }
        }
    }
}
