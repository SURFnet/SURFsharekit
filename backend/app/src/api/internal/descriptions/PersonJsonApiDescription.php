<?php

use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;
use SurfSharekit\Api\SearchApiController;
use SurfSharekit\Models\PersonImage;

class PersonJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'person';
    public $type_plural = 'persons';

    public $fieldToAttributeMap = [
        'Name' => 'name',
        'SurnamePrefix' => 'surnamePrefix',
        'Surname' => 'surname',
        'FirstName' => 'firstName',
        'Email' => 'email',
        "LinkedInUrl" => "linkedInUrl",
        "TwitterUrl" => "twitterUrl",
        "ResearchGateUrl" => "researchGateUrl",
        'HasLoggedIn' => 'hasLoggedIn',
        'IsRemoved' => 'isRemoved',
        'LoggedInUserPermissions' => 'permissions',
        "City" => "city",
        "Phone" => "phone",
        "FormOfAddress" => 'title',
        "AcademicTitle" => 'academicTitle',
        "Initials" => 'initials',
        "SecondaryEmail" => 'secondaryEmail',
        "PersistentIdentifier" => 'persistentIdentifier',
        "ORCID" => "orcid",
        "ISNI" => "isni",
        "HogeschoolID" => "hogeschoolId",
        "GroupCount" => 'groupCount',
        "Position" => 'position',
        "HasFinishedOnboarding" => 'hasFinishedOnboarding',
        "IsEmailEditable" => 'isEmailEditable',
        "InstituteTitles" => 'institutes',
        "LastEdited" => "lastEdited"
    ];

    public $hasOneToRelationMap = [
        'image' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'PersonImage',
            RELATIONSHIP_RELATED_OBJECT_CLASS => PersonImage::class
        ]
    ];

    public $hasManyToRelationsMap = [
        'groups' =>
            [
                RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Groups',
                RELATIONSHIP_RELATED_OBJECT_CLASS => Group::class,
                RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canRemoveGroup'
            ]
    ];

    //POST information
    public $attributeToFieldMap = [
        'surnamePrefix' => 'SurnamePrefix',
        'surname' => 'Surname',
        'firstName' => 'FirstName',
        'email' => 'Email',
        'isRemoved' => 'IsRemoved',
        'linkedInUrl' => 'LinkedInUrl',
        'twitterUrl' => 'TwitterUrl',
        'researchGateUrl' => 'ResearchGateUrl',
        'city' => 'City',
        'skipEmail' => 'SkipEmail',
        'phone' => 'Phone',
        "title" => 'FormOfAddress',
        "academicTitle" => 'AcademicTitle',
        "initials" => 'Initials',
        "secondaryEmail" => 'SecondaryEmail',
        "persistentIdentifier" => 'PersistentIdentifier',
        "orcid" => 'ORCID',
        "isni" => 'ISNI',
        "hogeschoolId" => 'HogeschoolID',
        "position" => 'Position',
        "institute" => 'BaseInstitute',
        "discipline" => 'BaseDiscipline',
        "hasFinishedOnboarding" => 'HasFinishedOnboarding',
    ];

    public function getFilterableAttributesToColumnMap(): array {
        return [
            'email' => '`Member`.`Email`',
            'name' => "REPLACE(CONCAT(`Member`.`FirstName`,' ',COALESCE(`Member`.`SurnamePrefix`,''),' ', `Member`.`Surname`),'  ',' ')",
            'institute' => null,
            'isRemoved' => '`Member`.IsRemoved',
            'group' => null,
            'search' => null
        ];
    }

    public function getFilterFunction(array $fieldsToSearchIn) {
        if (in_array('group', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix group filter with another filter');
            }
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=' || $modifier == '!=')) {
                    throw new Exception('Only ?filter[group][EQ] or ...[NEQ] supported');
                }

                return $datalist->leftJoin('Group_Members', '`Group_Members`.`MemberID` = `Member`.`ID`')
                    ->leftJoin('Group', '`Group`.`ID` = `Group_Members`.`GroupID`')
                    ->where("`Group`.`Uuid` $modifier '$filterValue'");
            };
        }

        if (in_array('institute', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix institute filter with another filter');
            }
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!in_array($modifier, ['=', 'LIKE'])) {
                    throw new Exception('Only ?filter[institute][EQ] or ?filter[institute][LIKE] supported');
                }
                if ($modifier == '=') {
                    return $datalist->leftJoin('Group_Members', '`Group_Members`.`MemberID` = `Member`.`ID`')
                        ->leftJoin('Group', '`Group`.`ID` = `Group_Members`.`GroupID`')
                        ->innerJoin('SurfSharekit_Institute', "`SurfSharekit_Institute`.`ID` = `Group`.`InstituteID`")
                        ->where(['`SurfSharekit_Institute`.`Uuid`' => $filterValue]);
                } else {
                    return $datalist->leftJoin('Group_Members', '`Group_Members`.`MemberID` = `Member`.`ID`')
                        ->leftJoin('Group', '`Group`.`ID` = `Group_Members`.`GroupID`')
                        ->innerJoin('SurfSharekit_Institute', "`SurfSharekit_Institute`.`ID` = `Group`.`InstituteID`")
                        ->where(["`SurfSharekit_Institute`.`Title` LIKE ?" => $filterValue]);
                }
            };
        }
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
                    $datalist = $datalist->whereAny(
                        ["(MATCH(Member.FirstName, Member.Surname) AGAINST(? IN BOOLEAN MODE) AND (Member.FirstName like ? or Member.Surname like ?))" => ['+' . $tag . '*','%' . $tag . '%','%' . $tag . '%'],
                            "(MATCH(Member.FirstName, Member.SurnamePrefix, Member.Surname) AGAINST(? IN BOOLEAN MODE) AND (Member.FirstName like ? or Member.SurnamePrefix like ? or Member.Surname like ?))" => ['+' . $tag . '*','%' . $tag . '%','%' . $tag . '%','%' . $tag . '%']]);
                }
                return $datalist;
            };
        }

        return parent::getFilterFunction($fieldsToSearchIn);
    }

    protected function getSortableAttributesToColumnMap(): array {
        return ['name' => 'SurName', 'hasLoggedIn' => 'HasLoggedIn'];
    }
}