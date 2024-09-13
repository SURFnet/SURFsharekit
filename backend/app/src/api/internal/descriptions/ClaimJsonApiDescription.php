<?php

use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;
use SurfSharekit\Api\SearchApiController;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonConfig;
use SurfSharekit\Models\PersonImage;

class ClaimJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'claim';
    public $type_plural = 'claims';

    public $fieldToAttributeMap = [
        "LastEdited" => "lastEdited",
        'Status' => 'status',
        'InstituteUuid' => 'instituteId',
        'ObjectUuid' => 'personId'
    ];

    public $hasOneToRelationMap = [
        'creator' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'CreatedBy',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Person::class,
        ],
        'institute' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Institute',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Institute::class,
        ]
    ];

    public $attributeToFieldMap = [
        "status" => 'Status',
        'instituteId' => 'InstituteFromApi',
        'personId' => 'PersonFromApi'
    ];
}