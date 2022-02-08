<?php

use SurfSharekit\Models\Institute;

class TemplateJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'template';
    public $type_plural = 'templates';

    public $fieldToAttributeMap = [
        'Title' => 'title',
        'RepoType' => 'repoType',
        'Description' => 'description',
        'LastEdited' => 'lastEdited',
        'LoggedInUserPermissions' => 'permissions',
        'InstituteLevel' => 'instituteLevel',
        'InstituteTitle' => 'instituteTitle',
        'AllowCustomization' => 'allowCustomization',
        'SectionsForJsonApi' => 'sections'
    ];

    public $hasOneToRelationMap = [
        'owner' =>
            [
                RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Institute',
                RELATIONSHIP_RELATED_OBJECT_CLASS => Institute::class
            ]
    ];


    //POST information
    public $attributeToFieldMap = [
        'title' => 'Title',
        'description' => 'Description',
        'repoType' => 'RepoType',
        'fields' => 'FieldsFromApi'
    ];

    protected function getSortableAttributesToColumnMap(): array {
        return ['title' => 'Title',
            'description' => 'Description',
            'instituteLevel' => 'Institute.Level',
            'instituteTitle' => 'Institute.Title',
            'repoType' => 'RepoType',
            'lastEdited' => 'LastEdited'];
    }

    public function getFilterableAttributesToColumnMap(): array {
        return ['title' => '`SurfSharekit_Template`.`Title`',
            'status' => '`SurfSharekit_Template`.`Status`',
            'repoType' => '`SurfSharekit_Template`.`RepoType`',
            'isRemoved' => '`SurfSharekit_Template`.`IsRemoved`',
            'allowCustomization' => '`SurfSharekit_Template`.`AllowCustomization`',
            'lastEdited' => '`SurfSharekit_Template`.`LastEdited`'];
    }
}