<?php

namespace SurfSharekit\api\internal\descriptions;

use DataObjectJsonApiDescription;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Security;
use SurfSharekit\models\notifications\NotificationCategory;
use SurfSharekit\models\notifications\NotificationSetting;

class NotificationJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'notification';
    public $type_plural = 'notifications';

    public $fieldToAttributeMap = [
        "Key" => "key",
        "LabelNL" => "labelNL",
        "LabelEN" => "labelEN",
        "SortOrder" => "sortOrder"
    ];

    public $hasOneToRelationMap = [
        'notificationCategory' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'NotificationCategory',
            RELATIONSHIP_RELATED_OBJECT_CLASS => NotificationCategory::class,
        ],
    ];

    public $hasManyToRelationsMap = [
        'notificationSettings' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'NotificationSettings',
            RELATIONSHIP_RELATED_OBJECT_CLASS => NotificationSetting::class,
        ],
    ];

    public function applyGeneralFilter(DataList $objectsToDescribe): DataList {
        $member = Security::getCurrentUser();
        if ($member) {
            $memberRoleIds = $member->allPermissionRoles()->column();
            $queryString = $memberRoleIds ? ('' . implode(',', $memberRoleIds)) : '-1';
            $objectsToDescribe = $objectsToDescribe->innerJoin("SurfSharekit_Notification_PermissionRoles", "SurfSharekit_Notification.ID = npr.SurfSharekit_NotificationID", "npr")
                ->innerJoin("PermissionRole", "pr.ID = npr.PermissionRoleID", "pr")
                ->where([
                    "npr.PermissionRoleID IN ($queryString)"
                ]);
        }
        return $objectsToDescribe->innerJoin('SurfSharekit_NotificationVersion', 'SurfSharekit_Notification.NotificationVersionID = SurfSharekit_NotificationVersion.ID');
    }

    public function getFilterableAttributesToColumnMap(): array {
        return [
            'notificationVersion' => '`SurfSharekit_NotificationVersion`.`VersionCode`',
        ];
    }
}