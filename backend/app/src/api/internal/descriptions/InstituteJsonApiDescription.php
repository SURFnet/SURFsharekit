<?php

use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\InstituteImage;
use SurfSharekit\Models\Template;

class InstituteJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'institute';
    public $type_plural = 'institutes';

    //GET information
    public $fieldToAttributeMap = [
        'Title' => 'title',
        'ConextCode' => 'conextCode',
        'LoggedInUserPermissions' => 'permissions',
        'IsRemoved' => 'isRemoved',
        'Level' => 'level',
        'Abbreviation' => 'abbreviation',
        'Summary' => 'summary',
        'IsUsersConextInstitute' => 'isUsersConextInstitute',
        'Type' => 'type',
        'IsBaseScopeForUser' => 'isBaseScopeForUser',
        'ChildrenInstitutesCount' => 'childrenInstitutesCount',
        'IsHidden' => 'isHidden'
    ];

    public $attributeToFieldMap = [
        'title' => 'Title',
        'conextCode' => 'ConextCode',
        'level' => 'Level',
        'abbreviation' => 'Abbreviation',
        'type' => 'Type',
        'isRemoved' => 'IsRemovedFromApi',
        'isHidden' => 'IsHidden'
    ];

    public $hasOneToRelationMap = [
        'parentInstitute' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Institute',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Institute::class,
            RELATIONSHIP_ADD_PERMISSION_METHOD => 'canCreateSubInstituteViaApi',
            RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canCreateSubInstituteViaApi'
        ],
        'image' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'InstituteImage',
            RELATIONSHIP_RELATED_OBJECT_CLASS => InstituteImage::class
        ]
    ];

    public $hasManyToRelationsMap = [
        'childrenInstitutes' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Institutes',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Institute::class
        ],
        'templates' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Templates',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Template::class
        ],
        'groups' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Groups',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Group::class
        ],
        'consortiumParents' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'ConsortiumParents',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Institute::class,
            RELATIONSHIP_ADD_PERMISSION_METHOD => 'canAddConsortiumParentViaApi',
            RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canRemoveConsortiumParentViaApi'
        ],
        'consortiumChildren' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'ConsortiumChildren',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Institute::class,
            RELATIONSHIP_ADD_PERMISSION_METHOD => 'canAddConsortiumChildViaApi',
            RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canRemoveConsortiumChildViaApi'
        ],
    ];

    public function getFilterableAttributesToColumnMap(): array {
        return [
            'inactive' => '`SurfSharekit_Institute`.`IsRemoved`',
            'isHidden' => '`SurfSharekit_Institute`.`IsHidden`',
            'title' => '`SurfSharekit_Institute`.`Title`',
            'isRemoved' => '`SurfSharekit_Institute`.`IsRemoved`',
            'level' => '`SurfSharekit_Institute`.`Level`',
            'type' => '`SurfSharekit_Institute`.`Type`',
            'parent' => '`SurfSharekit_Institute`.`InstituteUuid`',
            'consortiumParent' => null,
            'distinctTemplates' => null,
            'scope' => null
        ];
    }

    public function applyGeneralFilter(DataList $objectsToDescribe): DataList {
        return $objectsToDescribe->filter(['IsHidden' => false]);
    }

    public function getFilterFunction(array $fieldsToSearchIn) {
        if (in_array('consortiumParent', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix consortiumParent filter with another filter');
            }
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=' || $modifier == '!=')) {
                    throw new Exception('Only ?filter[consortiumParent][EQ] or ...[NEQ] supported');
                }

                return $datalist->innerJoin('SurfSharekit_Institute_ConsortiumChildren', '`SurfSharekit_Institute_ConsortiumChildren`.`ChildID` = `SurfSharekit_Institute`.`ID`')
                    ->leftJoin('SurfSharekit_Institute', 'SurfSharekit_Institute_ConsortiumChildren.`SurfSharekit_InstituteID` = `ConsortiumParent`.`ID`', 'ConsortiumParent')
                    ->where(["`ConsortiumParent`.`Uuid` $modifier ?" => $filterValue]);
            };
        }

        if (in_array('distinctTemplates', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix distinctTemplates filter with another filter');
            }
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=')) {
                    throw new Exception('Only ?filter[distinctTemplates][EQ] supported');
                }
                $member = Security::getCurrentUser();
                return $datalist->filterAny(['Templates.AllowCustomization' => $filterValue, 'ID' => $member->extend('getInstituteIdentifiers')[0]]);
            };
        }
        if (in_array('scope', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix scope filter with another filter');
            }
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=')) {
                    throw new Exception('Only ?filter[scope][EQ] supported');
                }
                $instituteUuids = explode(',', $filterValue);
                $instituteIDs = Institute::get()->filter(['Uuid' => $instituteUuids])->column('ID');
                $subInstituteFilter = InstituteScoper::getScopeFilter($instituteIDs);
                return $datalist->where("SurfSharekit_Institute.ID IN ( $subInstituteFilter )");
            };
        }
        if (in_array('level', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix level filter with another filter');
            }
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=')) {
                    throw new Exception('Only ?filter[level][EQ] supported');
                }
                $instituteLevels = explode(',', $filterValue);
                return $datalist->filterAny("Level", $instituteLevels);
            };
        }
        return parent::getFilterFunction($fieldsToSearchIn);
    }

    protected function getSortableAttributesToColumnMap(): array {
        $relevantInstitutes = InstituteScoper::getAll(Institute::class)->columnUnique('Uuid');
        $relevantInstitutesWithQuotations = [];
        foreach ($relevantInstitutes as $relevantInstitute) {
            $relevantInstitutesWithQuotations[] = "'$relevantInstitute'";
        }
        $relevantRootInstitutes = implode(',', $relevantInstitutesWithQuotations);
        return ['title' => 'Title',
            'relevancy' => "(SurfSharekit_Institute.Uuid NOT IN ($relevantRootInstitutes)), Title"];
    }
}