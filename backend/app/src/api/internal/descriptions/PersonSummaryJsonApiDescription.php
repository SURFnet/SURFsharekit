<?php

use SilverStripe\ORM\DataList;
use SurfSharekit\Api\SearchApiController;

class PersonSummaryJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'personSummary';
    public $type_plural = 'personSummaries';

    public $fieldToAttributeMap = [
        'Summary.id' => 'id',
        'Summary.name' => "name",
        'Summary.surnamePrefix' => "surnamePrefix",
        'Summary.surname' => 'surname',
        'Summary.firstName' => 'firstName',
        'Summary.persistentIdentifier' => 'persistentIdentifier',
        'Summary.hogeschoolId' => 'hogeschoolId',
        'Summary.orcid' => 'orcid',
        'Summary.isni' => 'isni',
        'Summary.hasLoggedIn' => 'hasLoggedIn',
        'Summary.position' => "position",
        'Summary.groupTitles' => "groupTitles",
        'Summary.groupTitlesWithoutMembers' => "groupTitlesWithoutMembers",
        'Summary.groupLabelsNL' => "groupLabelsNL",
        'Summary.groupLabelsEN' => "groupLabelsEN",
        'LoggedInUserPermissions' => 'permissions',
        'Person.RootInstitutesSummary' => "rootInstitutesSummary",
        'Person.InstituteTitles' => 'institutes',
        'Person.GroupCount' => 'groupCount'
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

    public function applyGeneralFilter(DataList $objectsToDescribe): \SilverStripe\ORM\DataList {
        return $objectsToDescribe->innerJoin('SurfSharekit_Person', 'SurfSharekit_Person.ID = SurfSharekit_PersonSummary.PersonID')
            ->innerJoin('Member', 'Member.ID = SurfSharekit_PersonSummary.PersonID');
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

                return $datalist->leftJoin('Group_Members', '`Group_Members`.`MemberID` = SurfSharekit_PersonSummary.PersonID')
                    ->leftJoin('Group', '`Group`.`ID` = `Group_Members`.`GroupID`')
                    ->where(["`Group`.`Uuid` $modifier ?" => $filterValue]);
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

                $datalist = $datalist->innerJoin('SurfSharekit_SearchObject', "SurfSharekit_SearchObject.PersonID = SurfSharekit_PersonSummary.PersonID");

                $searchTagsWithoutPlus = SearchApiController::getSearchTagsFromSearch($filterValue);
                foreach ($searchTagsWithoutPlus as $tag) {
                    if(stripos($tag, '-') !== false){
                        $matchTag =  '"' . $tag . '"';
                    }else{
                        $matchTag =  $tag . '*';
                    }
                    $datalist = $datalist->where(["(MATCH(SurfSharekit_SearchObject.SearchText) AGAINST (? IN Boolean MODE) AND SurfSharekit_SearchObject.SearchText like ?)" => ['+' . $matchTag,'%' . $tag . '%']]);
                }
                return $datalist;
            };
        }

        return parent::getFilterFunction($fieldsToSearchIn);
    }

    protected function getSortableAttributesToColumnMap(): array {
        return ['name' => 'SurName', 'hasLoggedIn' => 'HasLoggedIn', 'position' => 'Person.Position', 'firstName' => 'Person.FirstName', 'surname' => 'Person.Surname'];
    }
}