<?php

use SurfSharekit\Models\MetaFieldOption;

class DefaultMetaFieldOptionPartJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'defaultMetaFieldOption';
    public $type_plural = 'defaultMetaFieldOptions';

    public $fieldToAttributeMap = [
        'Title' => 'title',
        'Value' => 'value',
        'RepoItemUuid' => 'repoItemId',
        'RepoItemFileUuid' => 'repoItemFileId',
        'PersonUuid' => 'personId',
        'InstituteUuid' => 'instituteId'
    ];

    public $hasOneToRelationMap = [
        'option' => [
            RELATIONSHIP_RELATED_OBJECT_CLASS => MetaFieldOption::class,
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'MetaFieldOption'
        ]
    ];

    public $hasManyToRelationsMap = [
    ];
}