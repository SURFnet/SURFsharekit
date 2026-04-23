<?php

namespace SurfSharekit\Models;

use SilverStripe\Core\Extension;
use SilverStripe\EnvironmentExport\Exportable;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionRole;

/**
 * Class PermissionRoleDataExportExtension
 * @package SurfSharekit\Models
 * Extension on the SilverStripe Member DataObject to add Relationally permissions
 *
 * @property PermissionRole $owner
 */
class PermissionRoleDataExportExtension extends Extension {
    use Exportable;
    public function updateDataListForExport(DataList &$dataList) {
        $dataList = $dataList->alterDataQuery(function(DataQuery $query, DataList $list) {
            $schema = DataObject::getSchema();
            $idField = $schema->sqlColumnForField(PermissionRole::class, 'ID');
            $query->groupby($idField);
            $query->where(["PermissionRole.Key is not NULL"]);

            //Operations to obtain all GroupCodes
            $query->selectField("group_concat(distinct \"g\".\"Code\")", "GroupCodes");
            $query->leftJoin('Group_Roles', 'PermissionRole.ID = gr.PermissionRoleID', 'gr');
            $query->leftJoin('Group', 'gr.GroupID = g.ID', 'g');
            $query->where(["g.InstituteUuid is null"]);

            //Operations to obtain all RoleCodes
            $query->selectField("group_concat(distinct \"prc\".\"Code\")", "RoleCodes");
            $query->leftJoin('PermissionRoleCode', 'PermissionRole.ID = prc.RoleID', 'prc');
            return $query;
        });
    }

    public function addedFieldsForImport() {
        return [
            "RoleCodes",
            "GroupCodes"
        ];
    }

    public function onAfterWrite() {
        $map = $this->owner->toMap();
        if (array_key_exists('RoleCodes', $map)) {
            $groups = explode(",", $map['RoleCodes']);
            if (count($groups)) {
                $paramGroups = [];
                $params = [];
                foreach ($groups as $role) {
                    $paramGroups[] = "(?, ?)";
                    $params[] = $role;
                    $params[] = $this->owner->ID;
                }
                $valueStatement = implode(",", $paramGroups);
                DB::get_conn()->preparedQuery(
                    "INSERT INTO `PermissionRoleCode` (`Code`, `RoleID`) VALUES $valueStatement",
                    $params
                );
            }
        }

        if (array_key_exists('GroupCodes', $map)) {
            $groups = explode(",", $map['GroupCodes']);
            if (count($groups)) {
                $paramGroups = [];
                $params = [];
                foreach ($groups as $group) {
                    $groupId = Group::get()->find('Code', $group)->ID;
                    $paramGroups[] = "(?, ?)";
                    $params[] = $groupId;
                    $params[] = $this->owner->ID;
                }
                $valueStatement = implode(",", $paramGroups);
                DB::get_conn()->preparedQuery(
                    "INSERT INTO `Group_Roles` (`GroupID`, `PermissionRoleID`) VALUES $valueStatement",
                    $params
                );
            }
        }
    }
}