<?php

use SilverStripe\ORM\DataList;
use SurfSharekit\Api\SearchApiController;
use SurfSharekit\Extensions\Security;
use SurfSharekit\Models\Helper\Logger;

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
        'Person.GroupCount' => 'groupCount',
        'Person.RepoCount' => 'repoCount'
    ];

    public function getFilterableAttributesToColumnMap(): array {
        return [
            'email' => '`Member`.`Email`',
            'name' => "REPLACE(CONCAT(`Member`.`FirstName`,' ',COALESCE(`Member`.`SurnamePrefix`,''),' ', `Member`.`Surname`),'  ',' ')",
            'institute' => null,
            'isRemoved' => '`Member`.IsRemoved',
            'group' => null,
            'search' => null,
            'suggestion' => null
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
                    $datalist = $datalist->where(["(MATCH(SurfSharekit_SearchObject.SearchText) AGAINST (? IN Boolean MODE) OR SurfSharekit_SearchObject.SearchText like ?)" => ['+' . $matchTag,'%' . $tag . '%']]);
                }
                return $datalist;
            };
        }
        if (in_array('suggestion', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix suggestion filter with another filter');
            }

            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=')) {
                    throw new Exception('Only ?filter[suggestion][EQ] supported');
                }

                $currentUser = Security::getCurrentUser();
                $firstName = $currentUser->FirstName;
                $surname = $currentUser->Surname;
                Logger::infoLog("Applying suggestion filter with value: " . $filterValue, __CLASS__, __FUNCTION__);

                $suggestionResults =  $datalist->innerJoin(
                    "Member",
                    "Member.ID = SurfSharekit_PersonSummary.PersonID"
                )->innerJoin(
                    "SurfSharekit_Person",
                    "SurfSharekit_Person.ID = SurfSharekit_PersonSummary.PersonID"
                )->where([
                    "(MATCH (Member.FirstName) AGAINST (? IN NATURAL LANGUAGE MODE) + MATCH (Member.Surname) AGAINST (? IN NATURAL LANGUAGE MODE)) >= ?" =>
                        [$firstName, $surname, 10],
                    "SurfSharekit_Person.HasLoggedIn" => 0,
                    "Member.ID != ?" => $currentUser->ID,
                    "Member.ID NOT IN (SELECT DISTINCT MemberID FROM Group_Members
                                INNER JOIN `Group` ON `Group`.ID = Group_Members.GroupID 
                                INNER JOIN PermissionRole ON PermissionRole.ID = `Group`.DefaultRoleID 
                                WHERE PermissionRole.Key IN ('Supporter', 'Siteadmin'))"
                ])->orderBy(
                    "(MATCH (Member.FirstName) AGAINST ('$firstName' IN NATURAL LANGUAGE MODE) + MATCH (Member.Surname) AGAINST ('$surname' IN NATURAL LANGUAGE MODE)) DESC"
                );
                Logger::infoLog("Suggestion query = " . $suggestionResults->sql(), __CLASS__, __FUNCTION__);
                return $suggestionResults;
            };
        }

        return parent::getFilterFunction($fieldsToSearchIn);
    }

    protected function getSortableAttributesToColumnMap(): array {
        return ['name' => 'SurName', 'hasLoggedIn' => 'HasLoggedIn', 'position' => 'Person.Position', 'firstName' => 'Person.FirstName', 'surname' => 'Person.Surname'];
    }
}