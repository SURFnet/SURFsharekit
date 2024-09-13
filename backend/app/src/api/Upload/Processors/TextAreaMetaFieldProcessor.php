<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SurfSharekit\Models\RepoItemMetaField;

class TextAreaMetaFieldProcessor extends TextMetaFieldProcessor
{
    private static $type = "TextArea";
}