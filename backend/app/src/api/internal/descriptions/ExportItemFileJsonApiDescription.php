<?php

namespace SilverStripe\api\internal\descriptions;

class ExportItemFileJsonApiDescription extends \DataObjectJsonApiDescription {
    public $type_singular = 'exportItemFile';
    public $type_plural = 'exportItemFiles';

    public $fieldToAttributeMap = [
        'StreamUrl' => 'url',
    ];
}