<?php

namespace SurfSharekit\Models;

use SilverStripe\Core\Extension;
use SilverStripe\EnvironmentExport\Exportable;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SurfSharekit\constants\RoleConstant;

/**
 * Class MemberPermisionExtension
 * @package SurfSharekit\Models
 * Extension on the SilverStripe Member DataObject to add Relationally permissions
 *
 * @property Member $owner
 */
class MemberDataExportExtension extends Extension {
    use Exportable;
    public function updateDataListForExport(DataList &$dataList) {
        $dataList = $dataList->alterDataQuery(function(DataQuery $query, DataList $list) {
            $schema = DataObject::getSchema();
            $query->selectField("IF(code = 'administrators', 'administrators', group_concat(\"r\".\"Key\"))", "Roles");
            $query->groupby($schema->sqlColumnForField(Member::class, 'ID'));
            return $query
                ->innerJoin('Group_Members', 'Member.ID = gm.MemberID', 'gm')
                ->innerJoin('Group', 'gm.GroupID = g.ID', 'g')
                ->leftJoin('Group_Roles', 'g.ID = gr.GroupID', 'gr')
                ->leftJoin('PermissionRole', 'gr.PermissionRoleID = r.ID', 'r')
                ->where(["Member.ClassName" => Member::class])
                ->whereAny([
                    "r.Key = '" . RoleConstant::WORKSADMIN . "'",
                    "r.Key = '" . RoleConstant::APIUSER . "'",
                    "code = 'administrators'"
                ]);
        });
    }

    public function includedFieldsForImport() {
        return [
            "FirstName",
            "Surname",
            "Email",
            "Roles"
        ];
    }

    public function onAfterWrite() {
        $map = $this->owner->toMap();
        if (array_key_exists('Roles', $map)) {
            $roles = explode(",", $map['Roles']);
            if (count($roles)) {
                $placeholders = [];
                foreach ($roles as $role) {
                    $placeholders[] = "?";
                }


                $select = SQLSelect::create(
                    "g.ID as GroupID",
                    "PermissionRole r",
                    ["r.Key IN (" . implode(",", $placeholders) . ")" => $roles, "g.ID is not null"],
                )
                    ->addLeftJoin("Group_Roles", "gr.PermissionRoleID = r.ID", "gr")
                    ->addLeftJoin("Group", "g.ID = gr.GroupID", "g");
                $groupIds = $select->execute()->column('GroupID');
                if (in_array("administrators", $roles)) {
                    $groupIds[] = Group::get()->filter("Code", "administrators")->first()->ID;
                }

                if (count($groupIds)) {
                    $sqlGroups = [];
                    $memberId = $this->owner->ID;
                    foreach ($groupIds as $groupId) {
                        $sqlGroups[] = "($groupId, $memberId)";
                    }
                    $valueStatement = implode(",", $sqlGroups);
                    DB::get_conn()->query("INSERT INTO `Group_Members` (`GroupID`, `MemberID`) VALUES $valueStatement");
                }
            }
        }
    }
}