<?php
/**
 * Created by PhpStorm.
 * User: jasperbosman
 * Date: 2018-12-18
 * Time: 11:05
 */

namespace SurfSharekit\Models\Helper;

use DateTime;
use DateTimeZone;
use Exception;

class DateHelper
{
    public static function iso8601zFromString($date){
        try {
            $dateObj = new DateTime($date, new DateTimeZone('UTC'));
            return $dateObj->format('Y-m-d\TH:i:s\Z');
        } catch (Exception $e) {
            // do nothing
            Logger::debugLog('Failed to parse datetime, error : ' . $e->getMessage());
        }
        return null;
    }

    public static function iso8601FromDMYString($dateString){
        if(strlen($dateString) == 4){
            return $dateString;
        }
        if(strlen($dateString) == 6 || strlen($dateString) == 7){
            $dateParts = explode('-', $dateString);
            if(count($dateParts) == 2){
                if(strlen($dateParts[0]) == 1) {
                    $dateParts[0] = '0' . $dateParts[0];
                }
                if(strlen($dateParts[1]) == 2) {
                    $dateParts[1] = '20' . $dateParts[1];
                }
                if(strlen($dateParts[0]) == 2 && strlen($dateParts[1]) == 4){
                    return $dateParts[1] . '-' . $dateParts[0];
                }
            }
        }
        if(strlen($dateString) == 8 || strlen($dateString) == 9 || strlen($dateString) == 10){
            $dateParts = explode('-', $dateString);
            if(count($dateParts) == 3){
                if(strlen($dateParts[0]) == 1) {
                    $dateParts[0] = '0' . $dateParts[0];
                }
                if(strlen($dateParts[1]) == 1) {
                    $dateParts[1] = '0' . $dateParts[1];
                }
                if(strlen($dateParts[0]) == 2 && strlen($dateParts[1]) == 2 && strlen($dateParts[2]) == 4){
                    return $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                }
            }
        }
        return null;
    }

    public static function localDatetimeFromUTC($dateStr){
        try {
            $dateObj = new DateTime($dateStr, new DateTimeZone('UTC'));
            $dateObj->setTimezone(new DateTimeZone('Europe/Amsterdam'));
            return $dateObj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            // do nothing
            Logger::debugLog('Failed to parse datetime, error : ' . $e->getMessage());
        }
        return null;
    }

    public static function localExcelDateTimeFromString($dateStr){
        try {
            $dateObj = new DateTime($dateStr, new DateTimeZone('UTC'));
            $dateObj->setTimezone(new DateTimeZone('Europe/Amsterdam'));
            return $dateObj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            // do nothing
            Logger::debugLog('Failed to parse datetime, error : ' . $e->getMessage());
        }
        return null;
    }
}
