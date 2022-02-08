<?php

class RepoItemFileJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'repoItemFile';
    public $type_plural = 'repoItemFiles';

    public $fieldToAttributeMap = [
        'StreamUrl' => 'url',
        'Title' => 'title',
        'LoggedInUserPermissions' => 'permissions'
    ];
//
//    public $hasOneToRelationMap = [
//        'uploader' => [
//            RELATIONSHIP_RELATED_OBJECT_CLASS => Member::class,
//            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Member'
//        ]
//    ];
}