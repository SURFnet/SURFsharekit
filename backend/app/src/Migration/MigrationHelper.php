<?php

namespace SurfSharekit\Migration;

use DateTime;
use League\Flysystem\Exception;
use mysqli;
use Ramsey\Uuid\Uuid;
use SilverStripe\Core\Environment;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Institute;
use UuidExtension;

class MigrationHelper
{
    static public function OpenMigrationDatabase(){
        $host = Environment::getEnv('MIGRATION_DB_HOST');
        $username = Environment::getEnv('MIGRATION_DB_USER');
        $password = Environment::getEnv('MIGRATION_DB_PASSWORD');
        $database = Environment::getEnv('MIGRATION_DB_DATABASE');

        $db = new mysqli($host,$username, $password, $database);

        if(!$db){
            echo('Cannot connect to database!');
            exit();
        }

        if (!$db->set_charset("utf8")) {
            printf("Error loading character set utf8: %s\n", $db->error);
            exit();
        }

        return $db;
    }

    static public function timestampToMySQLdatetime($timestamp){
        try {
            $dateTime = DateTime::createFromFormat('U', (int)$timestamp);
            if($dateTime){
                return $dateTime->format("Y-m-d H:i:s");
            }
        } catch (\Exception $e) {
        }
        return null;
    }

    static function setBaseInstitute($person, $instituteUUID) {
        if (!UUID::isValid($instituteUUID)) {
            throw new Exception('Institute is not a valid institute ID');
        }
        $institute = UuidExtension::getByUuid(Institute::class, $instituteUUID);
        if (!$institute || !$institute->exists()) {
            throw new Exception("Institute $instituteUUID is not an existing Institute");
        }
        if ($institute->InstituteID) {
            throw new Exception("Institute is not a root institute");
        }

        $defaultMemberGroupOfInstitute = $institute->Groups()->filter(['Roles.Title' => Constants::TITLE_OF_MEMBER_ROLE])->first();
        if (!$defaultMemberGroupOfInstitute || !$defaultMemberGroupOfInstitute->exists()) {
            throw new Exception("Institute doesn't have a default member group");
        }
        Logger::debugLog("Add " . $person->Uuid . " to default group : $instituteUUID : " . $defaultMemberGroupOfInstitute->getTitle() . "\n");

        $person->write();
        $person->Groups()->Add($defaultMemberGroupOfInstitute);
    }
}