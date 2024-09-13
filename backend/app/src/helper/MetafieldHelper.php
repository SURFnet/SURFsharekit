<?php

namespace SurfSharekit\Models\Helper;

class MetafieldHelper {

    /**
     * This function returns the camelCase version of a metafield string
     */

    public static function toCamelCase($string)
    {
        $string = preg_replace('/[^a-zA-Z\s]/', '', $string);
        $words = explode(' ', strtolower(trim($string)));
        $camelCaseString = $words[0];
        for ($i = 1; $i < count($words); $i++) {
            $camelCaseString .= ucfirst($words[$i]);
        }
        return $camelCaseString;
    }

}
