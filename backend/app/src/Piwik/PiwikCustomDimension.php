<?php

namespace SurfSharekit\Piwik;

class PiwikCustomDimension
{
    const REPO_ITEM_FILE_ID = 1;
    const REPO_ITEM_ID = 2;
    const REPO_TYPE = 3;
    const ROOT_INSTITUTE_ID = 4;
    const UTM_SOURCE = 5;
    const UTM_CONTENT = 6;
    const REPO_ITEM_LINK_ID = 7;

    public static function getKey($id) {
        return "event_custom_dimension_$id";
    }
}