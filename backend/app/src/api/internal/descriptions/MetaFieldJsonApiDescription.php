<?php

use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\MetaFieldType;

class MetaFieldJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'metaField';
    public $type_plural = 'metaFields';

    public $fieldToAttributeMap = [
        'Label' => 'label',
        'Description' => 'description',
        'InfoText' => 'infoText'
    ];

    public $hasOneToRelationMap = [
        'metaFieldType' => [
            RELATIONSHIP_RELATED_OBJECT_CLASS => MetaFieldType::class,
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'MetaFieldType'
        ]
    ];

    public $hasManyToRelationsMap = [
        'options' => [
            RELATIONSHIP_RELATED_OBJECT_CLASS => MetaFieldOption::class,
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'MetaFieldOptions'
        ]
    ];
}