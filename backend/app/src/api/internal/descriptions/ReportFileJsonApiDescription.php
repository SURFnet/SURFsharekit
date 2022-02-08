<?php

class ReportFileJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'report';
    public $type_plural = 'reports';

    public $fieldToAttributeMap = [
        'StreamUrl' => 'url'
    ];
}