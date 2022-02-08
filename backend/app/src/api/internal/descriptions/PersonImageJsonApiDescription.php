<?php

class PersonImageJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'personImage';
    public $type_plural = 'personImages';

    public $fieldToAttributeMap = [
        'StreamUrl' => 'url'
    ];
//
//    public $hasOneToRelationMap = [
//        'uploader' => [
//            RELATIONSHIP_RELATED_OBJECT_CLASS => Member::class,
//            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Member'
//        ]
//    ];
}