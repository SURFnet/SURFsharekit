<?php

use SurfSharekit\Models\DefaultMetaFieldOptionPart;
use SurfSharekit\Models\MetaField;

class TemplateMetaFieldJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'templateMetaField';
    public $type_plural = 'templateMetaFields';

    public $fieldToAttributeMap = [
        'SortOrder' => 'sortOrder',
        'IsRequired' => 'isRequired',
        'IsLocked' => 'isLocked',
        'IsEnabled' => 'isEnabled',
        'IsReadOnly' => 'isReadOnly',
        'IsHidden' => 'isHidden',
        'IsCopyable' => 'isCopyable',
        'Label_EN' => 'labelEN',
        'Label_NL' => 'labelNL',
        'Description_EN' => 'descriptionEN',
        'Description_NL' => 'descriptionNL',
        'InfoText_EN' => 'infoTextEN',
        'InfoText_NL' => 'infoTextNL'
    ];

    public $attributeToFieldMap = [
        'isRequired' => 'IsRequired',
        'isLocked' => 'IsLocked',
        'isEnabled' => 'IsEnabled',
        'isReadOnly' => 'IsReadOnly',
        'isHidden' => 'IsHidden',
        'isCopyable' => 'IsCopyable',
        'labelEN' => 'Label_EN',
        'labelNL' => 'Label_NL',
        'descriptionEN' => 'Description_EN',
        'descriptionNL' => 'Description_NL',
        'infoTextEN' => 'InfoText_EN',
        'infoTextNL' => 'InfoText_NL'
    ];

    public $hasOneToRelationMap = [
        'metaField' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'MetaField',
            RELATIONSHIP_RELATED_OBJECT_CLASS => MetaField::class
        ]
    ];

    public $hasManyToRelationsMap = [
        'defaultValues' => [
            RELATIONSHIP_RELATED_OBJECT_CLASS => DefaultMetaFieldOptionPart::class,
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'DefaultMetaFieldOptionParts'
        ]
    ];

}