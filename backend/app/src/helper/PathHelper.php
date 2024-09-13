<?php

namespace SilverStripe\helper;

class PathHelper {


    /**
     * @param string|null $path
     * @return string|null
     * Removes the last segment of a path
     */
    public static function pathPop(?string $path): ?string {
        if (!$path) {
            return null;
        }

        $arr = explode('/', $path);
        if ($arr) {
            array_pop($arr);
            return implode('/', $arr);
        }

        return null;
    }

    /**
     * @param $path
     * @return string|null
     * Remove the first segment of a path
     */
    public static function pathShift($path) {
        $arr = explode('/', $path);
        if ($arr) {
            array_shift($arr);
            return implode('/', $arr);
        }

        return null;
    }
}