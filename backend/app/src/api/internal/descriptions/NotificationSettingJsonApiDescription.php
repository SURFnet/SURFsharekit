<?php

namespace SurfSharekit\api\internal\descriptions;

use DataObjectJsonApiDescription;

class NotificationSettingJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'notification-setting';
    public $type_plural = 'notification-settings';

    public $fieldToAttributeMap = [
        "Key" => "key",
        "IsDisabled" => "isDisabled",
        "NotificationType.Key" => "type"
    ];

    public $hasOneToRelationMap = [
    ];

    public $hasManyToRelationsMap = [
    ];
}