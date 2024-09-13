<?php

namespace SurfSharekit\Api;

use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\InstituteReport;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemSummary;
use SurfSharekit\Models\Template;

class PermissionFilter {
    const NO_CODE = "NO_CODE";

    public static function filterThroughCanViewPermissions(DataList $dataList) {
        $member = Security::getCurrentUser();

        if ($member->isDefaultAdmin()) {
            return $dataList;
        }

        if (in_array($dataList->dataClass(), [Institute::class, Template::class, RepoItem::class, Group::class, InstituteReport::class, RepoItemSummary::class])) {
            $dataObj = new $dataList->dataClass();
            $clauses = null;
            if ($dataObj->hasMethod('getPermissionCases')) {
                $clauses = $dataObj->getPermissionCases();
            }
            if ($clauses) {
               $dataList = self::filterThroughClauses($dataList, $clauses);
            }
        }
        return $dataList;
    }

    public static function filterThroughClauses(DataList $dataList, $clauses) {
        $dataList = static::leftJoinOnUserPermissions($dataList, $clauses);

        return static::filterOnClauses($dataList, $clauses);
    }

    /**
     * @param DataList $dataList
     * @param $clauses
     * @return DataList
     * This method left joins on permissions, and filters on permissions in clauses if set
     */
    public static function leftJoinOnUserPermissions(DataList $dataList, $clauses) {
        $member = Security::getCurrentUser();

        $classParts = explode('\\', $dataList->dataClass());
        $tableName = end($classParts);
        if ($classParts[0] == 'SurfSharekit') {
            $tableName = 'SurfSharekit_' . $tableName;
        }
        if ($tableName == 'SurfSharekit_InstituteReport') {
            $tableName = 'SurfSharekit_Institute';
        }

        $limitCodesPermissionRoleCodes = '';
        $limitCodesPermissions = '';
        if ($clauses) {
            $permissionCodes = array_keys($clauses);
            if (count($permissionCodes)) {
                $permissionWhereIn = "'" . implode("','", $permissionCodes) . "'";
                $limitCodesPermissions = " AND per.`Code` in ($permissionWhereIn) ";
                $limitCodesPermissionRoleCodes = " AND prc.`Code` in ($permissionWhereIn) ";
            }
        }

        $collectionOfItemInstituteIds = "$tableName.InstituteID";

        $relevantPermissionClauseForUser = "";
        $memberInstituteIDs = $member->extend('getInstituteIdentifiers')[0]; //this gets the institute of the member via the member extension.
        foreach ($memberInstituteIDs as $instituteID) {
            if ($relevantPermissionClauseForUser != "") {
                $relevantPermissionClauseForUser .= ' OR ';
            }
            $relevantPermissionClauseForUser .= " $instituteID IN (p1.ID, p2.ID, p3.ID, p4.ID, p5.ID, p6.ID, p7.ID, p8.ID) ";
        }
        $relevantPermissionClauseForItems = "c.ID IN ($collectionOfItemInstituteIds)";
        $institutePermissionSubQuery = "SELECT DISTINCT 
				cg.InstituteID, 
                cg.`Code` 
			FROM (
				SELECT DISTINCT 
					g.InstituteID, 
                    per.`Code` 
                FROM
					`Group_Members` AS `gm` 
				INNER JOIN 	`Group` AS `g` 					ON g.ID = gm.GroupID 
				INNER JOIN 	`Permission` AS `per` 			ON per.GroupID = gm.GroupID $limitCodesPermissions
				WHERE  gm.MemberID = $member->ID 
				UNION
				SELECT DISTINCT 
					g.instituteid, 
                    prc.code 
				FROM
					`Group_Members` AS `gm` 
				 INNER JOIN `Group` AS `g` 					ON g.ID = gm.GroupID 
				 INNER JOIN `Group_Roles` AS `gr` 			ON gr.GroupID = gm.GroupID 
				 INNER JOIN `PermissionRole` AS `pr` 		ON pr.ID = gr.PermissionRoleID
				 INNER JOIN `PermissionRoleCode` AS `prc` 	ON prc.RoleID = pr.ID $limitCodesPermissionRoleCodes
				WHERE  gm.MemberID = $member->ID 
			) cg ";

        return $dataList->leftJoin("(SELECT DISTINCT 
			p1.ID, 
            ifnull(g1.code, ifnull(g2.code, ifnull(g3.code, ifnull(g4.code,ifnull(g5.code,ifnull(g6.code,ifnull(g7.code,ifnull(g8.code,null)))))))) as code 
		
		FROM SurfSharekit_Institute p1
		LEFT JOIN ($institutePermissionSubQuery) g1 ON g1.InstituteID = p1.ID 
		LEFT JOIN SurfSharekit_Institute p2 ON p2.ID = p1.InstituteID 
        LEFT JOIN ($institutePermissionSubQuery) g2 ON g2.InstituteID = p2.ID 
		LEFT JOIN SurfSharekit_Institute p3 ON p3.ID = p2.InstituteID 
        LEFT JOIN ($institutePermissionSubQuery) g3 ON g3.InstituteID = p3.ID 
		LEFT JOIN SurfSharekit_Institute p4 ON p4.ID = p3.InstituteID 
        LEFT JOIN ($institutePermissionSubQuery) g4 ON g4.InstituteID = p4.ID 
		LEFT JOIN SurfSharekit_Institute p5 ON p5.ID = p4.InstituteID 
        LEFT JOIN ($institutePermissionSubQuery) g5 ON g5.InstituteID = p5.ID 
		LEFT JOIN SurfSharekit_Institute p6 ON p6.ID = p5.InstituteID 
        LEFT JOIN ($institutePermissionSubQuery) g6 ON g6.InstituteID = p6.ID 
		LEFT JOIN SurfSharekit_Institute p7 ON p7.ID = p6.InstituteID 
        LEFT JOIN ($institutePermissionSubQuery) g7 ON g7.InstituteID = p7.ID 
		LEFT JOIN SurfSharekit_Institute p8 ON p8.ID = p7.InstituteID 
        LEFT JOIN ($institutePermissionSubQuery) g8 ON g8.InstituteID = p8.ID
		
		WHERE  ($relevantPermissionClauseForUser)
		
        AND (g2.`Code` is null 
        OR (ifnull(g2.code, ifnull(g3.code, ifnull(g4.code,ifnull(g5.code,ifnull(g6.code,ifnull(g7.code,ifnull(g8.code,null)))))))        
        NOT IN ('PERSON_VIEW_SAMELEVEL','REPOITEM_VIEW_SAMELEVEL', 'GROUP_VIEW_SAMELEVEL', 'REPOITEM_VIEW_SAMELEVEL', 'INSTITUTE_VIEW_SAMELEVEL')))) c
		", $relevantPermissionClauseForItems);
    }

    /***
     * @param DataList $dataList
     * @param $clauses
     * @return DataList
     * This method adds the clause per permissions to check whether or not the permission is applicable
     */
    public static function filterOnClauses(DataList $dataList, $clauses) {
        if ($clauses) {
            $whereAnyList = [];
            foreach ($clauses as $permission => $case) {
                if ($permission == PermissionFilter::NO_CODE) {
                    $whereAnyList[] = $case;
                } else {
                    $whereAnyList[] = "$case AND c.Code = '$permission'";
                }
            }
            $dataList = $dataList->whereAny($whereAnyList);
        }
        return $dataList;
    }
}
