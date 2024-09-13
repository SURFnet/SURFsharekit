<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Services\Discover\DiscoverUploadService;

class RightOfUseDropdownMetaFieldProcessor extends DropdownFieldMetaFieldProcessor
{

    private static $type = "RightOfUseDropdown";
}