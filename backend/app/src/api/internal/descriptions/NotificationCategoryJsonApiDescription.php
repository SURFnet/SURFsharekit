<?php

namespace SurfSharekit\api\internal\descriptions;

use DataObjectJsonApiDescription;
use SurfSharekit\models\notifications\Notification;

class NotificationCategoryJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'notification-category';
    public $type_plural = 'notification-categories';

    public $fieldToAttributeMap = [
        "Title" => "title",
        "LabelNL" => "labelNL",
        "LabelEN" => "labelEN",
        "SortOrder" => "sortOrder",
    ];

    public $hasOneToRelationMap = [
    ];

    public $hasManyToRelationsMap = [
        'notifications' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Notifications',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Notification::class,
            RELATIONSHIP_ADD_PERMISSION_METHOD => 'canAddNotification',
            RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canRemoveNotification'
        ],
    ];
}