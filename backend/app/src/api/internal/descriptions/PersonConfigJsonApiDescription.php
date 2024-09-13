<?php

class PersonConfigJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'personConfig';
    public $type_plural = 'personConfigs';

    public $fieldToAttributeMap = [
        "EmailNotificationsEnabled" => "emailNotificationsEnabled",
        "EnabledNotifications" => "enabledNotifications",
        "NotificationVersion" => "notificationVersion",
    ];

    public $attributeToFieldMap = [
        "emailNotificationsEnabled" => "EmailNotificationsEnabled",
        "enabledNotifications" => "EnabledNotifications",
        "notificationVersion" => "NotificationVersion",
    ];

    public $hasOneToRelationMap = [];

    public $hasManyToRelationsMap = [];
}