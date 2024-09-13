<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SurfSharekit\Models\RepoItemMetaField;

class DoiMetaFieldProcessor extends TextMetaFieldProcessor
{
    private static $type = "doi";
}