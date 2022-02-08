<?php

use SilverStripe\ORM\DataList;
use SurfSharekit\Api\SearchApiController;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;

class GroupJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'group';
    public $type_plural = 'groups';

    //GET information
    public $fieldToAttributeMap = [
        'Title' => 'title',
        'LastEdited' => 'lastEdited',
        'Permissions' => 'permissions',
        'LoggedInUserPermissions' => 'userPermissions',
        'AmountOfPersons' => 'amountOfPersons',
        'RoleCode' => 'roleCode',
        'CodeMatrix' => 'codeMatrix'
    ];

    //POST and PATCH information
    public $attributeToFieldMap = [
        'permissions' => 'PermissionsFromAPI',
        'title' => 'Title'
    ];

    public $hasOneToRelationMap = [
        'partOf' => [
            RELATIONSHIP_RELATED_OBJECT_CLASS => Institute::class,
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Institute'
        ]
    ];

    public $hasManyToRelationsMap = [
        'persons' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Members',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Person::class,
            RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canRemovePerson'
        ]
    ];

    protected function getSortableAttributesToColumnMap(): array {
        return [
            'title' => 'Title',
            'level' => 'Institute.Level',
            'type' => 'Institute.Type'];
    }

    public function getFilterableAttributesToColumnMap(): array {
        return [
            'title' => '`Group`.Title',
            'institute' => '`Group`.InstituteUUID',
            'search' => null
        ];
    }

    public function getFilterFunction(array $fieldsToSearchIn) {
        if (in_array('search', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix search filter with another filter');
            }

            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=')) {
                    throw new Exception('Only ?filter[search][EQ] supported');
                }

                $searchTagsWithoutPlus = SearchApiController::getSearchTagsFromSearch($filterValue);

                foreach ($searchTagsWithoutPlus as $tag) {
                    $datalist = $datalist->where(["(MATCH(`Group`.Title) AGAINST(? IN BOOLEAN MODE) AND `Group`.Title like ?)" =>  ['+' . $tag . '*','%' . $tag . '%']]);
                }
                return $datalist;
            };
        }

        return parent::getFilterFunction($fieldsToSearchIn);
    }
}