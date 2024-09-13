<?php

namespace SurfSharekit\Api;

use Ramsey\Uuid\Uuid;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonSummary;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemSummary;
use SurfSharekit\Models\ScopeCache;
use SurfSharekit\Models\SimpleCacheItem;
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
        $uuid = Uuid::uuid4()->toString();
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

    public static function getInstitutesOfLowerScope(array $instituteUuids = []): DataList {
        if (count($instituteUuids) == 0) {
            $subInstituteFilter = "SELECT Uuid FROM SurfSharekit_Institute";
        } else {
            $whereclause = '';
            foreach ($instituteUuids as $instituteUuid) {
                $whereclause .= "('$instituteUuid' IN (p1.Uuid, p2.Uuid, p3.Uuid, p4.Uuid, p5.Uuid, p6.Uuid, p7.Uuid, p8.Uuid, p9.Uuid))";
                if ($instituteUuid !== end($instituteUuids)) {
                    $whereclause .= ' OR ';
                }
            }

            //map all Institutes to their respective parents and filter all insitutes that don't relate to one of the scoping institutes

            $subInstituteFilter = "
                SELECT      p1.Uuid as Uuid FROM SurfSharekit_Institute p1
                LEFT JOIN   SurfSharekit_Institute p2 on p2.Uuid = p1.InstituteUuid 
                LEFT JOIN   SurfSharekit_Institute p3 on p3.Uuid = p2.InstituteUuid 
                LEFT JOIN   SurfSharekit_Institute p4 on p4.Uuid = p3.InstituteUuid  
                LEFT JOIN   SurfSharekit_Institute p5 on p5.Uuid = p4.InstituteUuid  
                LEFT JOIN   SurfSharekit_Institute p6 on p6.Uuid = p5.InstituteUuid
                LEFT JOIN   SurfSharekit_Institute p7 ON p7.Uuid = p6.InstituteUuid
                LEFT JOIN   SurfSharekit_Institute p8 ON p8.Uuid = p7.InstituteUuid
                LEFT JOIN   SurfSharekit_Institute p9 ON p9.Uuid = p8.InstituteUuid
                WHERE       $whereclause
            ";
        }

        return Institute::get()->where("SurfSharekit_Institute.Uuid IN ( $subInstituteFilter )");
    }

    public static function getInstitutesOfUpperScope($instituteIDs = []) {
        $instituteList = implode(',', $instituteIDs);
        return Institute::get()
            ->leftJoin('SurfSharekit_Institute', 'c1.InstituteID = SurfSharekit_Institute.ID', 'c1')
            ->leftJoin('SurfSharekit_Institute', 'c2.InstituteID = c1.ID', 'c2')
            ->leftJoin('SurfSharekit_Institute', 'c3.InstituteID = c2.ID', 'c3')
            ->leftJoin('SurfSharekit_Institute', 'c4.InstituteID = c3.ID', 'c4')
            ->leftJoin('SurfSharekit_Institute', 'c5.InstituteID = c4.ID', 'c5')
            ->leftJoin('SurfSharekit_Institute', 'c6.InstituteID = c5.ID', 'c6')
            ->leftJoin('SurfSharekit_Institute', 'c7.InstituteID = c6.ID', 'c7')
            ->leftJoin('SurfSharekit_Institute', 'c8.InstituteID = c7.ID', 'c8')
            ->leftJoin('SurfSharekit_Institute', 'c9.InstituteID = c8.ID', 'c9')
            ->whereAny([
                "SurfSharekit_Institute.ID IN (" . $instituteList . ")",
                "c1.ID IN (" . $instituteList . ")",
                "c2.ID IN (" . $instituteList . ")",
                "c3.ID IN (" . $instituteList . ")",
                "c4.ID IN (" . $instituteList . ")",
                "c5.ID IN (" . $instituteList . ")",
                "c6.ID IN (" . $instituteList . ")",
                "c7.ID IN (" . $instituteList . ")",
                "c8.ID IN (" . $instituteList . ")",
                "c9.ID IN (" . $instituteList . ")"
            ]);
    }

    public static function getDataListScopedTo($dataObject, $instituteIDs = []) {
        $scopeFilter = static::getScopeFilter($instituteIDs);

        $scopeFilterIds = implode(',', DB::query($scopeFilter)->column('ID'));

        if (strlen($scopeFilterIds) == 0) {
            $scopeFilterIds = '-1';
        }

        if ($dataObject == Institute::class) {
            $returnList = Institute::get()->where("SurfSharekit_Institute.ID IN ( $scopeFilterIds )");
        } else if ($dataObject == Group::class) {
            $returnList = Group::get()->where("Group.InstituteID IN ( $scopeFilterIds )");
        } else if ($dataObject == Template::class) {
            $returnList = Template::get()->where("SurfSharekit_Template.InstituteID IN ( $scopeFilterIds )");
        } else if ($dataObject == Person::class) {
            $returnList = Person::get()->leftJoin('Group_Members', 'Group_Members.MemberID = `Member`.`ID`')->leftJoin('Group', 'GroupID = `Group`.ID')->where("`Group`.InstituteID IN ( $scopeFilterIds )");
        } else if ($dataObject == RepoItem::class) {
            $returnList = RepoItem::get()->where("SurfSharekit_RepoItem.InstituteID IN ( $scopeFilterIds )");
        } else if ($dataObject == RepoItemSummary::class) {
            $returnList = RepoItemSummary::get()->where("SurfSharekit_RepoItemSummary.InstituteID IN ( $scopeFilterIds )");
        } else if ($dataObject == PersonSummary::class) {
            $returnList = PersonSummary::get()->innerJoin('Group_Members', 'Group_Members.MemberID = SurfSharekit_PersonSummary.PersonID')->innerJoin('Group', 'GroupID = `Group`.ID')->where("`Group`.InstituteID IN ( $scopeFilterIds)");
        } else if ($dataObject == StatsDownload::class) {
            $returnList = StatsDownload::get()->where("SurfSharekit_StatsDownload.InstituteID IN ( $scopeFilterIds )");
        } else if ($dataObject == SimpleCacheItem::class) {
            $returnList = SimpleCacheItem::get()
                ->leftJoin('SurfSharekit_RepoItem', 'repoScope.ID = SurfSharekit_SimpleCacheItem.DataObjectID', 'repoScope')
                ->leftJoin('SurfSharekit_TemplateMetaField', 'templateMetafieldScope.ID = SurfSharekit_SimpleCacheItem.DataObjectID', 'templateMetafieldScope')
                ->leftJoin('SurfSharekit_Template', 'templateScope.ID = templateMetafieldScope.TemplateID', 'templateScope')
                ->whereAny(["SurfSharekit_SimpleCacheItem.DataObjectClass = 'SurfSharekit\\\Models\\\RepoItem' AND repoScope.InstituteID IN ( $scopeFilterIds )",
                    "SurfSharekit_SimpleCacheItem.DataObjectClass = 'SurfSharekit\\\Models\\\TemplateMetaField' AND templateScope.InstituteID IN ( $scopeFilterIds )"]);
        } else {
            $returnList = $dataObject::get();
        }

        return $returnList;
    }
}