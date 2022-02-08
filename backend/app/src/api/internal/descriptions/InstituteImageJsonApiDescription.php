<?php

class InstituteImageJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'instituteImage';
    public $type_plural = 'instituteImages';

    public $fieldToAttributeMap = [
        'StreamUrl' => 'url'
    ];
}