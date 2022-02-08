<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Helper\Logger;

class ScopeCache {

    static function isViewableFromCache($dataObject) {
        $member = Security::getCurrentUser();

        if (!isset($GLOBALS['VIEWABLE'][$member->ID][$dataObject->getClassName()])) {
            $GLOBALS['VIEWABLE'][$member->ID][$dataObject->getClassName()] = InstituteScoper::getAll($dataObject->getClassName())->columnUnique('ID');
        }
        return in_array($dataObject->ID, $GLOBALS['VIEWABLE'][$member->ID][$dataObject->getClassName()]);
    }

    static function getPermissionsFromCache($group) {
        if (!isset($GLOBALS['GROUP_PERMISSSIONS'][$group->ID])) {
            $GLOBALS['GROUP_PERMISSSIONS'][$group->ID] = static::getPermissionsWithoutCache($group);
        }
        return $GLOBALS['GROUP_PERMISSSIONS'][$group->ID];
    }

    private static function getPermissionsWithoutCache($group) {
        /**
         * Code copy and edited from @see(Permission::permissions_for_member)
         */
        $allowed = array_unique(DB::query("
				SELECT \"Code\"
				FROM \"Permission\"
				WHERE \"Type\" = " . Permission::GRANT_PERMISSION . " AND \"GroupID\" = $group->ID

				UNION

				SELECT \"Code\"
				FROM \"PermissionRoleCode\" PRC
				INNER JOIN \"PermissionRole\" PR ON PRC.\"RoleID\" = PR.\"ID\"
				INNER JOIN \"Group_Roles\" GR ON GR.\"PermissionRoleID\" = PR.\"ID\"
				WHERE \"GroupID\" = $group->ID
			")->column());

        $denied = array_unique(DB::query("
				SELECT \"Code\"
				FROM \"Permission\"
				WHERE \"Type\" = " . Permission::DENY_PERMISSION . " AND \"GroupID\" = $group->ID
			")->column());
        return array_diff($allowed, $denied);
    }

    static function removeAllCachedPermissions() {
        if (isset($GLOBALS['GROUP_PERMISSSIONS'])) {
            unset($GLOBALS['GROUP_PERMISSSIONS']);
        }
    }

    static function removeCachedPermissions($group) {
        if (isset($GLOBALS['GROUP_PERMISSSIONS'][$group->ID])) {
            unset($GLOBALS['GROUP_PERMISSSIONS'][$group->ID]);
        }
    }

    static function getCachedDataList($dataClass) {
        if (isset($GLOBALS['SCOPED_DATALIST']) && isset($GLOBALS['SCOPED_DATALIST'][$dataClass])) {
            return $GLOBALS['SCOPED_DATALIST'][$dataClass];
        }
        return null;
    }

    static function removeAllCachedDataLists() {
        if (isset($GLOBALS['SCOPED_DATALIST'])) {
            unset($GLOBALS['SCOPED_DATALIST']);
        }
    }

    static function removeCachedDataList($dataClass) {
        if (isset($GLOBALS['SCOPED_DATALIST'][$dataClass])) {
            unset($GLOBALS['SCOPED_DATALIST'][$dataClass]);
        }
    }

    static function setCachedDataList($dataList) {
        $GLOBALS['SCOPED_DATALIST'][$dataList->dataClass()] = $dataList;
    }

    static function removeAllCachedViewables() {
        if (($currentUser = Security::getCurrentUser()) && $currentUser && $currentUser->exists()) {
            if (isset($GLOBALS['VIEWABLE'][$currentUser->ID])) {
                unset($GLOBALS['VIEWABLE'][$currentUser->ID]);
            }
        }
    }

    static function removeCachedViewable($dataClass) {
        if (($currentUser = Security::getCurrentUser()) && $currentUser && $currentUser->exists()) {
            if (isset($GLOBALS['VIEWABLE'][$currentUser->ID][$dataClass])) {
                unset($GLOBALS['VIEWABLE'][$currentUser->ID][$dataClass]);
            }
        }
    }
}