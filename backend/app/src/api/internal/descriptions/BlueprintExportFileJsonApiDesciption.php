<?php

namespace SilverStripe\api\internal\descriptions;

use DataObjectJsonApiDescription;

class BlueprintExportFileJsonApiDesciption extends DataObjectJsonApiDescription {
    public $type_singular = 'blueprint-export';
    public $type_plural = 'blueprint-exports';

    public $fieldToAttributeMap = [
        'StreamUrl' => 'url'
    ];
}