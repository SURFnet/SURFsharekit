<?php

namespace SurfSharekit\Api;

use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonSummary;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemSummary;
use SurfSharekit\Models\ScopeCache;
use SurfSharekit\Models\StatsDownload;
use SurfSharekit\Models\Template;

/**
 * Class InstituteScoper
 * @package SurfSharekit\Api
 * This class is used to replace the DataObject::get call to scope it using the Insitute connected to the logged in user.
 * This makes sure that the logged in user can only access objects visible to their own Institute level and lower
 */
class InstituteScoper {
    const UNKNOWN = -1;
    const SAME_LEVEL = 1;
    const LOWER_LEVEL = 2;
    const HIGHER_LEVEL = 3;

    const PERMISSION_CODE = "PERMISSION_CODE";
    const PERMISSION_TYPE = "PERMISSION_TYPE";
    const PERMISSION_TYPE_INCLUSIVE = 1;
    const PERMISSION_TYPE_EXCLUSIVE = 2;

    /**
     * @var array $institutesInScope
     * Variable used to score the Insitutes in scope of the logged in user, used to limit database calls
     */
    static $institutesInScope = null;

    /**
     * @param $dataObject
     * @return \SilverStripe\ORM\DataList|null
     * Function used to limit the DataObject:get method to whatever scope the currently logged in user has
     */
    public static function getAll($dataObject) {
        $cachedDataList = ScopeCache::getCachedDataList($dataObject);
        if ($cachedDataList) {
            return $cachedDataList;
        }

        $member = Security::getCurrentUser();

        $memberInstituteIDs = [];
        if (!$member->isDefaultAdmin()) {
            $memberInstituteIDs = $member->extend('getInstituteIdentifiers')[0]; //this gets the institute of the member via the member extension.
        }

        $dataList = static::getDataListScopedTo($dataObject, $memberInstituteIDs);

        $result = PermissionFilter::filterThroughcanViewPermissions($dataList);
        ScopeCache::setCachedDataList($dataList);
        return $result;
    }

    /**
     * @param $leftInstituteID
     * @param $rightInstituteID
     * @param bool $isHigherLevelCheck set to not
     * @return int
     * This function returns what the relation of the rightHandInsitute is to the leftHandInsitute, returns SAME_LEVEL, LOWER_LEVEL or unknown if higher or nonexistent
     */
    public static function getScopeLevel($leftInstituteID, $rightInstituteID, $isHigherLevelCheck = false) {
        if ($leftInstituteID == $rightInstituteID) {
            return InstituteScoper::SAME_LEVEL;
        }

        if (!isset($_SESSION['Institute_ScopeLevel'][$leftInstituteID])) {
            $lowerInstituteIds = self::getDataListScopedTo(Institute::class, [$leftInstituteID])->column('ID');
            $_SESSION['Institute_ScopeLevel'][$leftInstituteID] = $lowerInstituteIds;
            if (in_array($rightInstituteID, $lowerInstituteIds)) {
                return static::LOWER_LEVEL;
            }
        }
        if (in_array($rightInstituteID, $_SESSION['Institute_ScopeLevel'][$leftInstituteID])) {
            return static::LOWER_LEVEL;
        } else if (!$isHigherLevelCheck && static::getScopeLevel($rightInstituteID, $leftInstituteID, true) == static::LOWER_LEVEL) {
            return static::HIGHER_LEVEL;
        }
        return static::UNKNOWN;
    }

    public static function getScopeFilter($instituteIDs) {
        if (count($instituteIDs) == 0) {
            $subInstituteFilter = "SELECT ID FROM SurfSharekit_Institute";
        } else {
            $whereclause = '';
            foreach ($instituteIDs as $instituteID) {
                $whereclause .= "($instituteID IN (p1.ID, p2.ID, p3.ID, p4.ID, p5.ID, p6.ID, p7.ID, p8.ID, p9.ID))";
                if ($instituteID !== end($instituteIDs)) {
                    $whereclause .= ' OR ';
                }
            }

            //map all Institutes to their respective parents and filter all insitutes that don't relate to one of the scoping institutes

            $subInstituteFilter = "
                SELECT      p1.ID as ID FROM SurfSharekit_Institute p1
                LEFT JOIN   SurfSharekit_Institute p2 on p2.ID = p1.InstituteID 
                LEFT JOIN   SurfSharekit_Institute p3 on p3.ID = p2.InstituteID 
                LEFT JOIN   SurfSharekit_Institute p4 on p4.ID = p3.InstituteID  
                LEFT JOIN   SurfSharekit_Institute p5 on p5.ID = p4.InstituteID  
                LEFT JOIN   SurfSharekit_Institute p6 on p6.ID = p5.InstituteID
                LEFT JOIN   SurfSharekit_Institute p7 ON p7.ID = p6.InstituteID
                LEFT JOIN   SurfSharekit_Institute p8 ON p8.ID = p7.InstituteID
                LEFT JOIN   SurfSharekit_Institute p9 ON p9.ID = p8.InstituteID
                WHERE       $whereclause
            ";

        }

        return $subInstituteFilter;
    }

    public static function getDataListScopedTo($dataObject, $instituteIDs = []) {
        $scopeFilter = static::getScopeFilter($instituteIDs);

        if ($dataObject == Institute::class) {
            $returnList = Institute::get()->where("SurfSharekit_Institute.ID IN ( $scopeFilter )");
        } else if ($dataObject == Group::class) {
            $returnList = Group::get()->where("Group.InstituteID IN ( $scopeFilter )");
        } else if ($dataObject == Template::class) {
            $returnList = Template::get()->where("SurfSharekit_Template.InstituteID IN ( $scopeFilter )");
        } else if ($dataObject == Person::class) {
            $returnList = Person::get()->leftJoin('Group_Members', 'Group_Members.MemberID = `Member`.`ID`')->leftJoin('Group', 'GroupID = `Group`.ID')->where("`Group`.InstituteID IN ( $scopeFilter )");
        } else if ($dataObject == RepoItem::class) {
            $returnList = RepoItem::get()->where("SurfSharekit_RepoItem.InstituteID IN ( $scopeFilter )");
        } else if ($dataObject == RepoItemSummary::class) {
            $returnList = RepoItemSummary::get()->where("SurfSharekit_RepoItemSummary.InstituteID IN ( $scopeFilter )");
        } else if ($dataObject == PersonSummary::class) {
            $returnList = PersonSummary::get()->leftJoin('Group_Members', 'Group_Members.MemberID = SurfSharekit_PersonSummary.PersonID')->leftJoin('Group', 'GroupID = `Group`.ID')->where("`Group`.InstituteID IN ( $scopeFilter )");
        } else if ($dataObject == StatsDownload::class) {
            $returnList = StatsDownload::get()->where("SurfSharekit_StatsDownload.InstituteID IN ( $scopeFilter )");
        } else {
            $returnList = $dataObject::get();
        }

        return $returnList;
    }
}