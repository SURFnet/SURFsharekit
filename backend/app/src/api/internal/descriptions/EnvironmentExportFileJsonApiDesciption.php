<?php

namespace SilverStripe\api\internal\descriptions;

use DataObjectJsonApiDescription;

class EnvironmentExportFileJsonApiDesciption extends DataObjectJsonApiDescription {
    public $type_singular = 'environment-export';
    public $type_plural = 'environment-exports';

    public $fieldToAttributeMap = [
        'StreamUrl' => 'url'
    ];
}