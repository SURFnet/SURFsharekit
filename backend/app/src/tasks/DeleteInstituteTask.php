<?php

namespace SurfSharekit\Tasks;

use Exception;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\Institute;

//vendor/bin/sake dev/tasks/SurfSharekit-Tasks-DeleteInstituteTask instituteId={idOfInstituteToDelete} madeBackup=yes areYouSure=yes
class DeleteInstituteTask extends BuildTask {
    protected $title = 'Totally remove an institute from the database';
    protected $description = 'Removes all related templates, persons and repoitems as well';

    protected $enabled = true;

    function run($request) {
        set_time_limit(0);

        $institute = Institute::get()->byID($request->getVar('instituteId'));
        if ($institute && $institute->exists()) {
            echo "<b>Removing all content in scope of: $institute->Title</b><br/>";
        } else {
            exit("Institute does not exist, is 'instituteId' set to an ID?");
        }

        $areYouSure = $request->getVar('madeBackup');
        if ($areYouSure != 'yes') {
            exit("'madeBackup' is not set to 'yes', did you make a backup?");
        }

        $areYouSure = $request->getVar('areYouSure');
        if ($areYouSure != 'yes') {
            exit("'areYouSure' is not set to 'yes', are you sure to delete this institute and all data relating to it?");
        }

        $instituteID = $institute->ID;


        try {
            DB::get_conn()->transactionStart();

            static::deleteDefaultMetaFieldOptionParts($instituteID);
            echo "Deleted " . DB::affected_rows() . " DefaultMetaFieldOptionParts<br/>";
            static::deleteTemplateMetaFields($instituteID);
            echo "Deleted " . DB::affected_rows() . " TemplateMetaFields<br/>";
            static::deleteTemplates($instituteID);
            echo "Deleted " . DB::affected_rows() . " Templates<br/>";

            echo "<br/>";

            static::deleteRepoItemFiles($instituteID);
            echo "Deleted " . DB::affected_rows() . " RepoItemFiles<br/>";
            static::deleteRepoItemMetaFieldValues($instituteID);
            echo "Deleted " . DB::affected_rows() . " RepoItemMetaFieldValues<br/>";
            static::deleteRepoItemMetaFields($instituteID);
            echo "Deleted " . DB::affected_rows() . " RepoItemMetaFields<br/>";
            static::deleteCacheRecordNodes($instituteID);
            echo "Deleted " . DB::affected_rows() . " Cache_RecordNodes<br/>";
            static::deleteRepoItems($instituteID);
            echo "Deleted " . DB::affected_rows() . " RepoItems<br/>";

            echo "<br/>";
            static::deletePersonImages($instituteID);
            echo "Deleted " . DB::affected_rows() . " PersonImages<br/>";
            static::deletePersons($instituteID);
            echo "Deleted " . DB::affected_rows() . " Persons<br/>";
            static::deleteMembers($instituteID);
            echo "Deleted " . DB::affected_rows() . " Members<br/>";
            static::deleteGroupMembers($instituteID);
            echo "Deleted " . DB::affected_rows() . " GroupMembers<br/>";
            static::deletePermissions($instituteID);
            echo "Deleted " . DB::affected_rows() . " Permissions<br/>";
            static::deleteGroupRoles($instituteID);
            echo "Deleted " . DB::affected_rows() . " GroupRoles<br/>";
            static::deleteGroups($instituteID);
            echo "Deleted " . DB::affected_rows() . " Groups<br/>";
            static::deleteInstituteImages($instituteID);
            echo "Deleted " . DB::affected_rows() . " InstituteImages<br/>";
            static::deleteChannelInstitutes($instituteID);
            echo "Deleted " . DB::affected_rows() . " ChannelInstitutes<br/>";
            static::deleteConsortiumChildren($instituteID);
            echo "Deleted " . DB::affected_rows() . " ConsortiumChildren<br/>";
            static::deleteInstitutes($instituteID);
            echo "Deleted " . DB::affected_rows() . " Institutes<br/>";

            DB::get_conn()->transactionEnd();
        } catch (Exception $e) {
            echo 'Something went wrong: ' . $e->getMessage();
            DB::get_conn()->transactionRollback();
        }
    }

    private static function getRelevantInstitutes($instituteID) {
        return "(SELECT p1.ID
                 FROM SurfSharekit_Institute p1
                 LEFT JOIN SurfSharekit_Institute p2 on p2.ID = p1.InstituteID
                 LEFT JOIN SurfSharekit_Institute p3 on p3.ID = p2.InstituteID
                 LEFT JOIN SurfSharekit_Institute p4 on p4.ID = p3.InstituteID
                 LEFT JOIN SurfSharekit_Institute p5 on p5.ID = p4.InstituteID
                 LEFT JOIN SurfSharekit_Institute p6 on p6.ID = p5.InstituteID
                 LEFT JOIN SurfSharekit_Institute p7 ON p7.ID = p6.InstituteID
                 LEFT JOIN SurfSharekit_Institute p8 ON p8.ID = p7.InstituteID
                 LEFT JOIN SurfSharekit_Institute p9 ON p9.ID = p8.InstituteID
                 WHERE $instituteID IN (p1.ID, p2.ID, p3.ID, p4.ID, p5.ID, p6.ID, p7.ID,p8.ID, p9.ID))";
    }

    /**
     * Templates
     */
    private static function deleteDefaultMetaFieldOptionParts($instituteID) {
        DB::query("DELETE FROM SurfSharekit_DefaultMetaFieldOptionPart 
                        WHERE ID IN (
                             SELECT SurfSharekit_DefaultMetaFieldOptionPart.ID FROM SurfSharekit_Template
                             INNER JOIN SurfSharekit_TemplateMetaField ON SurfSharekit_TemplateMetaField.TemplateID = SurfSharekit_Template.ID
                             INNER JOIN SurfSharekit_DefaultMetaFieldOptionPart ON SurfSharekit_DefaultMetaFieldOptionPart.TemplateMetaFieldID = SurfSharekit_TemplateMetaField.ID
                                 WHERE SurfSharekit_Template.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteTemplateMetaFields($instituteID) {
        DB::query("DELETE FROM SurfSharekit_TemplateMetaField
                        WHERE ID IN (
                             SELECT SurfSharekit_TemplateMetaField.ID FROM SurfSharekit_Template
                             INNER JOIN SurfSharekit_TemplateMetaField ON SurfSharekit_TemplateMetaField.TemplateID = SurfSharekit_Template.ID
                                 WHERE SurfSharekit_Template.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteTemplates($instituteID) {
        DB::query("DELETE FROM SurfSharekit_Template 
                        WHERE ID IN (
                             SELECT SurfSharekit_Template.ID FROM SurfSharekit_Template
                                 WHERE SurfSharekit_Template.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    /**
     * RepoItems
     */

    private static function deleteRepoItemFiles($instituteID) {
        DB::query("DELETE FROM `File`
                        WHERE ID IN (
                            SELECT `File`.ID FROM SurfSharekit_RepoItem 
                                 INNER JOIN SurfSharekit_RepoItemMetaField ON SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID
                                 INNER JOIN SurfSharekit_RepoItemMetaFieldValue ON SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID 
                                 INNER JOIN `File` ON `File`.ID = SurfSharekit_RepoItemMetaFieldValue.RepoItemFileID
                                    WHERE SurfSharekit_RepoItem.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteRepoItemMetaFieldValues($instituteID) {
        DB::query("DELETE FROM SurfSharekit_RepoItemMetaFieldValue
                        WHERE ID IN (
                            SELECT SurfSharekit_RepoItemMetaFieldValue.ID FROM SurfSharekit_RepoItem 
                                 INNER JOIN SurfSharekit_RepoItemMetaField ON SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID
                                 INNER JOIN SurfSharekit_RepoItemMetaFieldValue ON SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID 
                                    WHERE SurfSharekit_RepoItem.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteRepoItemMetaFields($instituteID) {
        DB::query("DELETE FROM SurfSharekit_RepoItemMetaField
                        WHERE ID IN (
                            SELECT SurfSharekit_RepoItemMetaField.ID FROM SurfSharekit_RepoItem 
                                 INNER JOIN SurfSharekit_RepoItemMetaField ON SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID
                                    WHERE SurfSharekit_RepoItem.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteCacheRecordNodes($instituteID) {
        DB::query("DELETE FROM SurfSharekit_Cache_RecordNode
                        WHERE ID IN (
                            SELECT SurfSharekit_Cache_RecordNode.ID FROM SurfSharekit_RepoItem 
                                 INNER JOIN SurfSharekit_Cache_RecordNode ON SurfSharekit_Cache_RecordNode.RepoItemID = SurfSharekit_RepoItem.ID
                                    WHERE SurfSharekit_RepoItem.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteRepoItems($instituteID) {
        DB::query("DELETE FROM SurfSharekit_RepoItemMetaField
                        WHERE ID IN (
                            SELECT SurfSharekit_RepoItem.ID FROM SurfSharekit_RepoItem 
                                WHERE SurfSharekit_RepoItem.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    /**
     * Institutes
     */

    private static function deletePersonImages(int $instituteID) {
        DB::query("DELETE FROM `File`
                        WHERE ID IN (
                             SELECT `File`.ID FROM `Group`
                                 INNER JOIN Group_Members ON Group_Members.GroupID = `Group`.ID
                                 INNER JOIN SurfSharekit_Person ON SurfSharekit_Person.ID = Group_Members.MemberID
                                 INNER JOIN `File` ON `File`.ID = SurfSharekit_Person.PersonImageID
                                    WHERE `Group`.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deletePersons(int $instituteID) {
        DB::query("DELETE FROM SurfSharekit_Person
                        WHERE ID IN (
                             SELECT SurfSharekit_Person.ID FROM `Group`
                                 INNER JOIN Group_Members ON Group_Members.GroupID = `Group`.ID
                                 INNER JOIN SurfSharekit_Person ON SurfSharekit_Person.ID = Group_Members.MemberID
                                    WHERE `Group`.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteMembers(int $instituteID) {
        DB::query("DELETE FROM Member
                        WHERE ID IN (
                             SELECT Member.ID FROM `Group`
                                 INNER JOIN Group_Members ON Group_Members.GroupID = `Group`.ID
                                 INNER JOIN Member ON Member.ID = Group_Members.MemberID
                                    WHERE `Group`.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteGroupMembers(int $instituteID) {
        DB::query("DELETE FROM Group_Members
                        WHERE ID IN (
                             SELECT Group_Members.ID FROM `Group`
                                 INNER JOIN Group_Members ON Group_Members.GroupID = `Group`.ID
                                    WHERE `Group`.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deletePermissions(int $instituteID) {
        DB::query("DELETE FROM Permission
                        WHERE ID IN (
                             SELECT Permission.ID FROM `Group`
                                 INNER JOIN Permission ON `Group`.ID = Permission.GroupID
                                    WHERE `Group`.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteGroupRoles(int $instituteID) {
        DB::query("DELETE FROM Group_Roles
                        WHERE ID IN (
                             SELECT Group_Roles.ID FROM `Group`
                                 INNER JOIN Group_Roles ON `Group`.ID = Group_Roles.GroupID
                                    WHERE `Group`.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteGroups(int $instituteID) {
        DB::query("DELETE FROM `Group`
                        WHERE ID IN (
                             SELECT `Group`.ID FROM `Group`
                                    WHERE `Group`.InstituteID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteInstituteImages(int $instituteID) {
        DB::query("DELETE FROM `File`
                        WHERE ID IN (
                             SELECT `File`.ID FROM SurfSharekit_Institute
                                 INNER JOIN `File` ON `File`.ID = SurfSharekit_Institute.InstituteImageID
                                 WHERE SurfSharekit_Institute.ID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteChannelInstitutes(int $instituteID) {
        DB::query("DELETE FROM SurfSharekit_Channel_Institutes
                        WHERE ID IN (
                             SELECT SurfSharekit_Channel_Institutes.ID FROM SurfSharekit_Institute
                                 INNER JOIN SurfSharekit_Channel_Institutes ON SurfSharekit_Channel_Institutes.SurfSharekit_InstituteID = SurfSharekit_Institute.ID
                                 WHERE SurfSharekit_Institute.ID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteConsortiumChildren(int $instituteID) {
        DB::query("DELETE FROM SurfSharekit_Institute_ConsortiumChildren
                        WHERE ID IN (
                             SELECT SurfSharekit_Institute_ConsortiumChildren.ID FROM SurfSharekit_Institute
                                 INNER JOIN SurfSharekit_Institute_ConsortiumChildren ON SurfSharekit_Institute_ConsortiumChildren.SurfSharekit_InstituteID = SurfSharekit_Institute.ID
                                 WHERE SurfSharekit_Institute.ID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }

    private static function deleteInstitutes(int $instituteID) {
        DB::query("DELETE FROM SurfSharekit_Institute
                        WHERE ID IN (
                             SELECT SurfSharekit_Institute.ID FROM SurfSharekit_Institute
                                 WHERE SurfSharekit_Institute.ID IN " . static::getRelevantInstitutes($instituteID) . ')');
    }
}