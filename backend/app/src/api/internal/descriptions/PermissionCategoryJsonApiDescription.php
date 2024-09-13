<?php

namespace SurfSharekit\api\internal\descriptions;

use DataObjectJsonApiDescription;
use SurfSharekit\models\PermissionDescription;

class PermissionCategoryJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'permission-category';
    public $type_plural = 'permission-categories';

    public $fieldToAttributeMap = [
        "Title" => "title",
        "LabelNL" => "labelNL",
        "LabelEN" => "labelEN",
        "SortOrder" => "sortOrder"
    ];

    public $hasOneToRelationMap = [
    ];

    public $hasManyToRelationsMap = [
        'permissionDescriptions' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'PermissionDescriptions',
            RELATIONSHIP_RELATED_OBJECT_CLASS => PermissionDescription::class,
        ],
    ];
}