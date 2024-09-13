<?php

namespace SurfSharekit\api\internal\descriptions;

use DataObjectJsonApiDescription;
use Exception;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;
use SurfSharekit\models\PermissionCategory;

class PermissionDescriptionJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'permission-description';
    public $type_plural = 'permission-descriptions';

    public $fieldToAttributeMap = [
        "Title" => "title",
        "TextNL" => "textNL",
        "TextEN" => "textEN",
        "SortOrder" => "sortOrder"
    ];

    public $hasOneToRelationMap = [
        'permissionCategory' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'PermissionCategory',
            RELATIONSHIP_RELATED_OBJECT_CLASS => PermissionCategory::class,
        ],
    ];

    public $hasManyToRelationsMap = [
    ];

    public function getFilterableAttributesToColumnMap(): array {
        return [
            "group" => null
        ];
    }

    public function getFilterFunction(array $fieldsToSearchIn) {
        if (in_array('group', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix group filter with another filter');
            }
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=')) {
                    throw new Exception('Only ?filter[group][EQ] supported');
                }
                $group = Group::get()->find("Uuid", $filterValue);
                if ($group) {
                    $groupPermissions = $group->getPermissions();
                    $rolePermissions = $group->getPermissionsFromRoles();

                    $permissionCodes = array_unique(array_merge($groupPermissions, $rolePermissions));
                    if ($permissionCodes) {
                        return $datalist->filter(["PermissionCode" => $permissionCodes]);
                    }
                }
                return $datalist->filter('ID', 0);
            };
        }

        return parent::getFilterFunction($fieldsToSearchIn);
    }
}