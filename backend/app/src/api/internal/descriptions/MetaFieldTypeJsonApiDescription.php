<?php

class MetaFieldTypeJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'metaFieldType';
    public $type_plural = 'metaFieldTypes';

    public $fieldToAttributeMap = [
        'Title' => 'title'
    ];

    public $hasOneToRelationMap = [
    ];

    public $hasManyToRelationsMap = [
    ];
}