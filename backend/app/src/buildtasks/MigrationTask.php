<?php
namespace SurfSharekit\Tasks;

use DateTime;
use Exception;
use mysqli;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Migration\MigrationHelper;
use SurfSharekit\Migration\MigrationMapper;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\MigrationLog;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;
use UuidExtension;

class MigrationTask extends BuildTask{

    protected $title = 'Migrate sharekit data';
    protected $description = 'This task creates or updates items from (old)ShareKit to WoRKS';

    protected $enabled = true;

    private $limitRepoItemsToMigrate = 100;

    private $migratedAt;
    private $skipExistingRecords;
    private $request;

    private $repoItemView ='wlSubCollectionUniquePublicationRecords';

    function run($request)
    {
        $member = Member::get()->byID(1);
        Security::setCurrentUser($member);

        $this->migratedAt = (new DateTime('now'))->format('Y-m-d H:i:s');
        set_time_limit(0);

        $db = MigrationHelper::OpenMigrationDatabase();

        $this->skipExistingRecords = $request->getVar('skip');
        $this->request = $request;

        $customView = $request->getVar('customView');
        if(!is_null($customView)){
            $this->repoItemView = $customView;
        }

        $processAll = $request->getVar('processAll');
        $processAllItems = $request->getVar('processAllItems');
        if(!is_null($processAll) || ($organizationIdentifier = $request->getVar('organizations'))) {
            if(!is_null($processAll)){
                $processAllItems = $processAll;

                $organizationIdentifier = $processAll;
            }
            Logger::debugLog('Migrate organizations for ' . $organizationIdentifier);
            echo("Migrate organizations\n");
            // migration organizations
            $done = false;
            $offset = 0;
            $limit = $this->limitRepoItemsToMigrate;

            if($organizationIdentifier == '1'){
                $organizationIdentifiers = null;
            }else {
                $organizationIdentifiers = explode(',', $organizationIdentifier);
            }
            while (!$done) {
                $done = $this->createOrUpdateOrganizations($db, $offset, $limit, $organizationIdentifiers);
                echo(date('Y-m-d H:i:s') . ' : ' . $offset . "\n");
                Logger::debugLog(date('Y-m-d H:i:s') . ' : ' . $offset . "\n");
                $offset = $offset + $limit;
            }
        }

        if(!is_null($processAllItems) || ($organizationIdentifier = $request->getVar('persons'))) {
            if(!is_null($processAllItems)){
                $organizationIdentifier = $processAllItems;
            }
            Logger::debugLog('Migrate persons' . $organizationIdentifier);
            echo("Migrate persons\n");
            // migration organizations
            $done = false;
            $offset = 0;
            $limit = $this->limitRepoItemsToMigrate;

            if($organizationIdentifier == '1'){
                $organizationIdentifiers = null;
            }else {
                $organizationIdentifiers = explode(',', $organizationIdentifier);
            }
            Logger::debugLog('Migrate persons' . print_r($organizationIdentifiers, true));
            echo("Migrate persons" . print_r($organizationIdentifiers, true) . "\n");
            while (!$done) {
                $done = $this->createOrUpdatePersons($db, $offset, $limit, $organizationIdentifiers);
                echo(date('Y-m-d H:i:s') . ' : ' . $offset . "\n");
                Logger::debugLog(date('Y-m-d H:i:s') . ' : ' . $offset . "\n");
                $offset = $offset + $limit;
            }
        }

        if(!is_null($processAllItems) || ($organizationIdentifier = $request->getVar('repoitems'))) {
            if(!is_null($processAllItems)){
                $organizationIdentifier = $processAllItems;
            }

            $done = false;
            $offset = 0;
            $limit = $this->limitRepoItemsToMigrate;

            if ($organizationIdentifier == '1') {
                $organizationIdentifiers =
                    DB::query('select distinct r.InstituteUuid from SurfSharekit_RepoItem r
                    INNER JOIN SurfSharekit_MigrationLog l
                    on l.TargetObjectID = r.ID and l.TargetObjectClass = \'SurfSharekit\\\\Models\\\\RepoItem\'
                   and r.InstituteUuid is not null
                    AND r.Status != \'Migrated\'')->column('InstituteUuid');
            }else {
                $organizationIdentifiers = explode(',', $organizationIdentifier);
            }
            Logger::debugLog('Migrate repoitems' . print_r($organizationIdentifiers, true));
            echo("Migrate repoitems" . print_r($organizationIdentifiers, true) . "\n");
            while (!$done) {
                $done = $this->createOrUpdateRepoItems($db, $offset, $limit, $organizationIdentifiers);
                echo(date('Y-m-d H:i:s' . ' : ' . $offset . "\n"));
                $offset = $offset + $limit;
            }
            $this->updateRepoItemSilverstripeIds();
        }

        if(!is_null($processAllItems) || ($organizationIdentifier = $request->getVar('authors'))) {
            if(!is_null($processAllItems)){
                $organizationIdentifier = $processAllItems;
            }
            Logger::debugLog('Migrate authors');
            echo("Migrate authors\n");
            // migration organizations
            $done = false;
            $offset = 0;
            $limit = $this->limitRepoItemsToMigrate;

            if($organizationIdentifier == '1'){
                $organizationIdentifiers = null;
            }else {
                $organizationIdentifiers = explode(',', $organizationIdentifier);
            }
            Logger::debugLog('Migrate authors' . print_r($organizationIdentifiers, true));
            echo("Migrate authors" . print_r($organizationIdentifiers, true) . "\n");
            while (!$done) {
                $done = $this->createOrUpdateAuthors($db, $offset, $limit, $organizationIdentifiers);
                echo(date('Y-m-d H:i:s') . ' : ' . $offset . "\n");
                Logger::debugLog(date('Y-m-d H:i:s') . ' : ' . $offset . "\n");
                $offset = $offset + $limit;
            }
        }

        if(!is_null($processAllItems) || ($organizationIdentifier = $request->getVar('files'))) {
            if(!is_null($processAllItems)){
                $organizationIdentifier = $processAllItems;
            }
            Logger::debugLog('Migrate files');
            echo("Migrate files\n");
            $done = false;
            $offset = 0;
            $limit = $this->limitRepoItemsToMigrate;

            if($organizationIdentifier == '1'){
                $organizationIdentifiers = null;
            }else {
                $organizationIdentifiers = explode(',', $organizationIdentifier);
            }
            Logger::debugLog('Migrate attachments' . print_r($organizationIdentifiers, true));
            echo("Migrate attachments" . print_r($organizationIdentifiers, true) . "\n");
            while (!$done) {
                $done = $this->createOrUpdateAttachments($db, $offset, $limit, $organizationIdentifiers);
                echo(date('Y-m-d H:i:s' . ' : ' . $offset . "\n"));
                $offset = $offset + $limit;
            }
        }

        if(!is_null($processAllItems) || ($organizationIdentifier = $request->getVar('links'))) {
            if(!is_null($processAllItems)){
                $organizationIdentifier = $processAllItems;
            }
            $done = false;
            $offset = 0;
            $limit = $this->limitRepoItemsToMigrate;

            if ($organizationIdentifier == '1') {
                $organizationIdentifiers = null;
            } else {
                $organizationIdentifiers = explode(',', $organizationIdentifier);
            }
            Logger::debugLog('Migrate links' . print_r($organizationIdentifiers, true));
            echo("Migrate links" . print_r($organizationIdentifiers, true) . "\n");
            while (!$done) {
                $done = $this->createOrUpdateLinks($db, $offset, $limit, $organizationIdentifiers);
                echo(date('Y-m-d H:i:s' . ' : ' . $offset . "\n"));
                $offset = $offset + $limit;
            }
        }


        if(!is_null($processAllItems) || ($organizationIdentifier = $request->getVar('disciplines'))) {
            if(!is_null($processAllItems)) {
                $organizationIdentifier = $processAllItems;
            }
            $done = false;
            $offset = 0;
            $limit = $this->limitRepoItemsToMigrate;

            if ($organizationIdentifier == '1') {
                $organizationIdentifiers =
                    DB::query('select distinct r.InstituteUuid from SurfSharekit_RepoItem r
                    INNER JOIN SurfSharekit_MigrationLog l
                    on l.TargetObjectID = r.ID and l.TargetObjectClass = \'SurfSharekit\\\\Models\\\\RepoItem\'
                   and r.InstituteUuid is not null
                    AND r.Status != \'Migrated\'')->column('InstituteUuid');
            } else {
                $organizationIdentifiers = explode(',', $organizationIdentifier);
            }

            Logger::debugLog('Migrate disciplines' . print_r($organizationIdentifiers, true));
            echo("Migrate disciplines" . print_r($organizationIdentifiers, true) . "\n");
            while (!$done) {
                $done = $this->createOrUpdateDisciplines($db, $offset, $limit, $organizationIdentifiers);
                echo(date('Y-m-d H:i:s' . ' : ' . $offset . "\n"));
                $offset = $offset + $limit;
            }
        }

        if(!is_null($processAllItems) || ($organizationIdentifier = $request->getVar('resetrepoitems'))) {
            if(!is_null($processAllItems)){
                $organizationIdentifier = $processAllItems;
            }

            $done = false;
            $offset = 0;
            $limit = $this->limitRepoItemsToMigrate * 10;


            if ($organizationIdentifier == '1') {
                $organizationIdentifiers =
                    DB::query('select distinct r.InstituteUuid from SurfSharekit_RepoItem r
                    INNER JOIN SurfSharekit_MigrationLog l
                    on l.TargetObjectID = r.ID and l.TargetObjectClass = \'SurfSharekit\\\\Models\\\\RepoItem\'
                   and r.InstituteUuid is not null
                    AND r.Status != \'Migrated\'')->column('InstituteUuid');
            }else {
                $organizationIdentifiers = explode(',', $organizationIdentifier);
            }
            Logger::debugLog('Migrate repoitems' . print_r($organizationIdentifiers, true));
            echo("Migrate repoitems" . print_r($organizationIdentifiers, true) . "\n");
            while (!$done) {
                $done = $this->createOrUpdateRepoItems($db, $offset, $limit, $organizationIdentifiers, true);
                echo(date('Y-m-d H:i:s' . ' : ' . $offset . "\n"));
                $offset = $offset + $limit;
            }
        }


        $db->close();
    }

    function createOrUpdateRepoItems(mysqli $db, $offset, $limit, $organizationIdentifiers = null, $reset = false){
        $organizationFilter = '';
        if(!is_null($organizationIdentifiers)) {
            $organizationFilter = ' AND organizationIdentifier in (\'' . implode('\',\'', $organizationIdentifiers) . '\') ';
        }
        $result = $db->query("
 SELECT `collectionIdentifier`,
    `collectionName`,
    `organizationIdentifier`,
    `collectionRecordtype`,
    `status`,
    `migrate`,
    `identifier`,
    `created`,
    `lastModified`,
    `type`,
    `dcterms:title`,
    `dbpo:subtitle`,
    `dcterms:abstract`,
    `dcterms:type`,
    `dcterms:publisher`,
    `dcterms:language`,
    `dcterms:educationLevel`,
    `sharekit:nbc:domain`,
    `dcterms:rights`,
    `dbpo:numberOfPages`,
    `event:place`,
    `dcterms:date`,
    `dcterms:keyword`,
    `bibo:doi`,
    `sharekit:thesis:organization`,
    `sharekit:thesis:organization:url`,
    `sharekit:grade`,
    `sharekit:award`,
    `sharekit:date:approved`,
    `sharekit:registrationNumber`,
    `skos:note`,
    `bibo:edition`,
    `mods:relatedItem:host:title`,
    `mods:relatedItem:host:publisher`,
    `mods:relatedItem:host:place`,
    `mods:relatedItem:host:page:start`,
    `mods:relatedItem:host:page:end`,
    `mods:relatedItem:host:volume`,
    `mods:relatedItem:host:issue`,
    `mods:relatedItem:host:ISSN`,
    `mods:relatedItem:host:ISBN`,
    `bibo:handle`,
    `mods:relatedItem:host:conference`,
    `lom:aggregationlevel`,
    `lom:learningresourcetype`,
    `lom:technicalformat`,
    `lom:educational:intendedEndUserRole`,
    `lom:educational:ageRange`,
    `lom:rights:cost`,
    `lom:classification:purpose_discipline`,
    `sharekit:permission`,
    `mods:semanticType`,
    `personIdentifier`,
    `personFirstname`,
    `personLastName`
FROM `sharekitmigration`.`$this->repoItemView`


WHERE 1=1 $organizationFilter
ORDER BY organizationIdentifier, identifier
 LIMIT $limit OFFSET $offset 

        ");

        if(is_null($result) || $result->num_rows == 0){
            return true;
        }

        while($row = $result->fetch_assoc()){
            $this->createOrUpdateRepoItem($row, $this->repoItemView, $reset);
        }
        //$result->free_result();
        return false;
//        $this->updateRepoItemSilverstripeIds();
    }

    function updateRepoItemSilverstripeIds(){

        $updateQuery = '
UPDATE SurfSharekit_RepoItem c
INNER JOIN
    SurfSharekit_Institute p ON c.InstituteUuid = p.Uuid
SET 
    c.InstituteID = p.ID';
        DB::query($updateQuery);

        $updateQuery = '
UPDATE SurfSharekit_RepoItem c
INNER JOIN
    Member p ON c.OwnerUuid = p.Uuid
SET 
    c.OwnerID = p.ID';
        DB::query($updateQuery);

        $updateQuery = '
UPDATE SurfSharekit_RepoItem
SET 
    CreatedByID = OwnerID
WHERE OwnerID is not null and (CreatedByID is null or CreatedByID = 1)';
        DB::query($updateQuery);

        $updateQuery = '
UPDATE SurfSharekit_RepoItem
SET 
    ModifiedByID = OwnerID
WHERE OwnerID is not null and (ModifiedByID is null or ModifiedByID = 1)';
        DB::query($updateQuery);

        $updateQuery = 'update SurfSharekit_RepoItem  r
INNER JOIN SurfSharekit_MigrationLog l
on l.TargetObjectID = r.ID and l.TargetObjectClass = \'SurfSharekit\\Models\\RepoItem\'
and r.`Status` = \'Migrated\'
SET r.LastEdited = CAST( JSON_UNQUOTE( JSON_EXTRACT(l.Data, \'$.lastModified\')) as DATETIME)';
        DB::query($updateQuery);
    }

    function updateRepoItemSilverstripeIdsForUuid($uuid){

        $updateQuery = '
UPDATE SurfSharekit_RepoItem
SET 
    CreatedByID = OwnerID
WHERE Uuid = \'' . $uuid . '\' and OwnerID is not null and (CreatedByID is null or CreatedByID = 1)';
        DB::query($updateQuery);

        $updateQuery = '
UPDATE SurfSharekit_RepoItem
SET 
    ModifiedByID = OwnerID
WHERE Uuid = \'' . $uuid . '\' and OwnerID is not null and (ModifiedByID is null or ModifiedByID = 1)';
        DB::query($updateQuery);

        $updateQuery = 'update SurfSharekit_RepoItem  r
INNER JOIN SurfSharekit_MigrationLog l
on l.TargetObjectID = r.ID and l.TargetObjectClass = \'SurfSharekit\\\\Models\\\\RepoItem\'
SET r.LastEdited = CAST( JSON_UNQUOTE( JSON_EXTRACT(l.Data, \'$.lastModified\')) as DATETIME)
WHERE r.Uuid = \'' . $uuid . '\'';
        DB::query($updateQuery);
    }

    function createOrUpdateRepoItem($repoItemArray, $source, $reset = false){
        if($repoItemArray['type'] == 'PublicationRecord'){
            if($repoItemArray['dcterms:type'] == 'http://purl.org/eprint/type/Thesis' || $repoItemArray['dcterms:type'] == 'info:eu-repo/semantics/traineeReport'){
                // PublicationRecord
            }else{
                $repoItemArray['type'] = 'ResearchObject';
                $repoItemArray['researchType'] = $repoItemArray['dcterms:type'];
                unset($repoItemArray['dcterms:type']);
            }
        }
        $origStatus = null;
        $repoItem = RepoItem::get()->filter(['Uuid' => $repoItemArray['identifier']])->first();
        if(is_null($repoItem)){
            $repoItem = RepoItem::create();
        //    Logger::debugLog('Is InDB : ' . $repoItem->isInDB() ? 'true':'false');
        //    Logger::debugLog('UUID before = ' . $repoItem->Uuid);
            $repoItem->setField('Uuid', $repoItemArray['identifier']);
            $repoItem->setField('Status', 'Migrated');
            $repoItem->setField('RepoType', $repoItemArray['type']);
            $repoItem->write();
         //   Logger::debugLog('UUID after setfield = ' . $repoItem->Uuid);
        //    Logger::debugLog($repoItem->Uuid);
        }elseif($this->skipExistingRecords){
            return;
        }else{
            // reset status to migrated to prevent validation
            $origStatus = $repoItem->getField('Status');

            // check if title exists
            $titleRepoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldUuid','4d88565e-665c-4db9-b5a6-65aa9c272e05')->first();;
            if(!is_null($titleRepoItemMetaField)){
                $titleRepoItemMetaFieldValueCount = $titleRepoItemMetaField->RepoItemMetaFieldValues()->count();
                if($titleRepoItemMetaFieldValueCount == 0){
                    // title missing, so continue, else skip
                }else{

                    $migrationLogItem = MigrationLog::get()->filter(['Uuid' => $repoItemArray['identifier']])->first();
                    if(is_null($migrationLogItem)) {
                        $migrationLogItem = MigrationLog::create();
                        $migrationLogItem->setField('Uuid', $repoItemArray['identifier']);
                    }
                    $jsonData = json_encode($repoItemArray, 4194304);

                    //echo($jsonData);
                    $migrationLogItem->setField('Data', $jsonData);
                    $migrationLogItem->setField('Source', $source);
                    $migrationLogItem->setField('MigratedAt', $this->migratedAt);
                    $migrationLogItem->TargetObject = $repoItem;

                    try {
                        $migrationLogItem->write();
                    } catch (ValidationException $e) {
                        // skip for now
                    }
                    Logger::debugLog("SKIP UUID : " . $repoItem->Uuid . " : " . $repoItem->Title . "\n");
                    return;
                }
            }
            DB::prepared_query('update SurfSharekit_RepoItem set Status = \'Migrated\' where Uuid = ?', [$repoItem->getField('Uuid')]);
        }
        // Logger::debugLog('UUID after set title = ' . $repoItem->Uuid);
        $repoItem->setField('RepoType', $repoItemArray['type']);
        $repoItem->setField('IsPublic', ($repoItemArray['status'] == 'kennisbank' || $repoItemArray['status'] == 'portaal'));
        $repoItem->setField('IsArchived', ($repoItemArray['status'] == 'archief'));
        $repoItem->Title = $repoItemArray['dcterms:title'];
        $repoItem->OwnerUuid = $repoItemArray['personIdentifier'];
        $institute = Institute::get()->where(['uuid'=>$repoItemArray['organizationIdentifier']])->first();
        if($institute){
            $instituteID = $institute->ID;
        }else{
            $instituteID = 0;
        }
        $repoItem->setField('InstituteID', $instituteID);
        $repoItem->setField('InstituteUuid', $repoItemArray['organizationIdentifier']);
        /** @var RepoItemMetaField $repoItemMetaField */
        $repoItem->setRepoItemMetaFieldValue('270c698d-81ff-4a5d-aa67-7cf76f63bf78', []);
        // set publisher
        $repoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldUuid','270c698d-81ff-4a5d-aa67-7cf76f63bf78')->first();;
        $repoItemMetaFieldValue = RepoItemMetaFieldValue::create();
        $repoItemMetaFieldValue->setField('InstituteID', $instituteID);
        $repoItemMetaFieldValue->setField('InstituteUuid', $repoItemArray['organizationIdentifier']);
        $repoItemMetaFieldValue->setFIeld('RepoItemMetaFieldID', $repoItemMetaField->ID);
        try {
            $repoItemMetaFieldValue->write();
        } catch (ValidationException $e) {
        }
        $created = $repoItemArray['created'];
        if($created){
            $repoItem->setField('Created', $created);
        }

        Logger::debugLog("UUID : " . $repoItem->Uuid . " : " . $repoItem->Title . "\n");
        try {
            $repoItem->write();
        } catch (Exception $e) {
            // skip for now
        }

        $migrationLogItem = MigrationLog::get()->filter(['Uuid' => $repoItemArray['identifier']])->first();
        if(is_null($migrationLogItem)) {
            $migrationLogItem = MigrationLog::create();
            $migrationLogItem->setField('Uuid', $repoItemArray['identifier']);
        }
        $jsonData = json_encode($repoItemArray, 4194304);

        //echo($jsonData);
        $migrationLogItem->setField('Data', $jsonData);
        $migrationLogItem->setField('Source', $source);
        $migrationLogItem->setField('MigratedAt', $this->migratedAt);
        $migrationLogItem->TargetObject = $repoItem;

        try {
            $migrationLogItem->write();
        } catch (ValidationException $e) {
            // skip for now
        }

        $repoType = $repoItemArray['type'];
        $metaFields = MigrationMapper::$Metafields[$repoType];
        if(is_array($metaFields)) {
            foreach ($metaFields as $metaFieldUuid => $key) {
                if (array_key_exists($key, $repoItemArray)) {
                    if(array_key_exists($metaFieldUuid, MigrationMapper::$MetaFieldExplode)){
                        $values = explode(MigrationMapper::$MetaFieldExplode[$metaFieldUuid], $repoItemArray[$key]);
                    }else{
                        $values = [$repoItemArray[$key]];
                    }
                    $mappedValues = [];
                    foreach($values as $value) {
                        $mappedValue = MigrationMapper::mapMetaFields($metaFieldUuid, $value);
                        if(!is_null($mappedValue)){
                            $mappedValues[] = $mappedValue;
                        }
                    }
                    $uniqueMappedValues = array_unique($mappedValues);
                    if(!$reset){
                        Logger::debugLog("$key : " . print_r($uniqueMappedValues, true));
                    }
                    $repoItem->setRepoItemMetaFieldValue($metaFieldUuid, $uniqueMappedValues);

                }
            }
        }
        if(!is_null($origStatus)) {
            DB::prepared_query('update SurfSharekit_RepoItem set Status = ? where Uuid = ?', [$origStatus, $repoItem->getField('Uuid')]);
        }
        if($reset){
            self::updateRepoItemSilverstripeIdsForUuid($repoItem->getField('Uuid'));
        }
    }

    function createOrUpdateOrganizations(mysqli $db, $offset, $limit, $organizationIdentifiers = null){
        if( !mysqli_ping($db) ) {
            $db = MigrationHelper::OpenMigrationDatabase();
        }
        $organizationFilter = '';
        if(!is_null($organizationIdentifiers)) {
            $organizationFilter = ' AND rootIdentifier in (\'' . implode('\',\'', $organizationIdentifiers) . '\') ';
        }
    $query = "
    SELECT `wlMigrateOrganizations`.`rootIdentifier`,
        `wlMigrateOrganizations`.`rootName`,
        `wlMigrateOrganizations`.`identifier`,
        `wlMigrateOrganizations`.`name`,
        `wlMigrateOrganizations`.`metadata_organizationType`,
        `wlMigrateOrganizations`.`shortname`,
        `wlMigrateOrganizations`.`active`,
        `wlMigrateOrganizations`.`score`,
        `wlMigrateOrganizations`.`created`,
        `wlMigrateOrganizations`.`lastModified`,
        `wlMigrateOrganizations`.`parentName`,
        `wlMigrateOrganizations`.`parentIdentifier`,
        `wlMigrateOrganizations`.`parentType`
    FROM `sharekitmigration`.`wlMigrateOrganizations`
    where 1 = 1 $organizationFilter
    LIMIT $limit OFFSET $offset ";
        Logger::debugLog("query = ". $query . "\n");
        $result = $db->query($query);
        if(is_null($result) || $result==false || $result->num_rows == 0){
            return true;
        }
        while($row = $result->fetch_assoc()){
            $this->createOrUpdateOrganization($row, 'wlMigrateOrganizations');
        }

        return false;
    }

    function createOrUpdateOrganization($organizationItemArray, $source){
        //echo('Create or update ' . print_r($organizationItemArray, true) . "<br><br>");
        $institute = Institute::get()->filter(['Uuid' => $organizationItemArray['identifier']])->first();
        if(is_null($institute)){
            $institute = Institute::create();
            $institute->setField('Uuid', $organizationItemArray['identifier']);

            $title = $organizationItemArray['name'];
            if(empty($title)){
                $title = '(no name)';
            }
            $institute->setField('Title', $title);


            $abbreviation = $organizationItemArray['shortname'];
            if(!empty($abbreviation)) {
                $institute->setField('Abbreviation', $abbreviation);
            }

            $level = MigrationMapper::map('InstituteLevel', $organizationItemArray['metadata_organizationType']);
            if(!empty($level)){
                $institute->setField('Level', $level);
            }
        }elseif($this->skipExistingRecords){
            return;
        }

        if(!is_null($organizationItemArray['parentIdentifier'])){
            // this is an child institute, connect to parent
            $institute->InstituteUuid = $organizationItemArray['parentIdentifier'];
            $institute->InstituteID = 0;
        }

        $created = $organizationItemArray['created'];

        $institute->setField('Created', $created);

        Logger::debugLog("UUID : " . $institute->Uuid . " : " . $institute->Title . "\n");
        try {
            $institute->write();
        } catch (ValidationException $e) {
            // skip for now
        }

        $migrationLogItem = MigrationLog::get()->filter(['Uuid' => $organizationItemArray['identifier']])->first();
        if(is_null($migrationLogItem)) {
            $migrationLogItem = MigrationLog::create();
            $migrationLogItem->setField('Uuid', $organizationItemArray['identifier']);
        }

        $jsonData = json_encode($organizationItemArray, 4194304);

        //echo($jsonData);
        $migrationLogItem->setField('Data', $jsonData);
        $migrationLogItem->setField('Source', $source);
        $migrationLogItem->setField('MigratedAt', $this->migratedAt);
        $migrationLogItem->TargetObject = $institute;

        try {
            $migrationLogItem->write();
        } catch (ValidationException $e) {
            // skip for now
        }
    }

    function createOrUpdatePersons(mysqli $db, $offset, $limit, $organizationIdentifiers = null){
        if( !mysqli_ping($db) ) {
            $db = MigrationHelper::OpenMigrationDatabase();
        }
        $organizationFilter = '';
        if(!is_null($organizationIdentifiers)) {
            $organizationFilter = ' AND organizationIdentifier in (\'' . implode('\',\'', $organizationIdentifiers) . '\') ';
        }
        if($this->request->getVar('migratedai')){
            $organizationFilter .= ' AND dai is not null ';
        }
        $result = $db->query("
    SELECT distinct `vwtPersonsWithOrganization`.`lastModified`,
    `vwtPersonsWithOrganization`.`dcterms:created`,
    `vwtPersonsWithOrganization`.`identifier`,
    `vwtPersonsWithOrganization`.`type`,
    `vwtPersonsWithOrganization`.`metadata_foaf:familyName`,
    `vwtPersonsWithOrganization`.`metadata_foaf:givenName`,
    `vwtPersonsWithOrganization`.`metadata_vcard:honorific-suffix`,
    `vwtPersonsWithOrganization`.`metadata_foaf:title`,
    `vwtPersonsWithOrganization`.`metadata_infix`,
    `vwtPersonsWithOrganization`.`email`,
    `vwtPersonsWithOrganization`.`loginIdentifier`,
    `vwtPersonsWithOrganization`.`attributes`,
    `vwtPersonsWithOrganization`.`dai`,
    `vwtPersonsWithOrganization`.`migrationOrganizationIdentifier`,
    `vwtPersonsWithOrganization`.`organizationIdentifier`
    FROM `sharekitmigration`.`vwtPersonsWithOrganization`
    
    WHERE 1=1 $organizationFilter
    LIMIT $limit OFFSET $offset 
        ");

        if(is_null($result) || $result==false || $result->num_rows == 0){
            return true;
        }
        while($row = $result->fetch_assoc()){
            $this->createOrUpdatePerson($row, 'vwtPersons');
        }

        return false;
    }

    function createOrUpdatePerson($personItemArray, $source){
        //echo('Create or update ' . print_r($organizationItemArray, true) . "<br><br>");
        $person = Person::get()->filter(['Uuid' => $personItemArray['identifier']])->first();
        if(is_null($person)){
            $person = Person::create();
            $person->setField('Uuid', $personItemArray['identifier']);
        }
        elseif($this->skipExistingRecords){
            $migrationLogItem = MigrationLog::get()->filter(['Uuid' => $personItemArray['identifier']])->first();
            if(is_null($migrationLogItem)) {
                $migrationLogItem = MigrationLog::create();
                $migrationLogItem->setField('Uuid', $personItemArray['identifier']);
            }

            $jsonData = json_encode($personItemArray, 4194304);

            //echo($jsonData);
            $migrationLogItem->setField('Data', $jsonData);
            $migrationLogItem->setField('Source', $source);
            $migrationLogItem->setField('MigratedAt', $this->migratedAt);
            $migrationLogItem->TargetObject = $person;

            try {
                $migrationLogItem->write();
            } catch (ValidationException $e) {
                // skip for now
            }
            return;
        }

        $created = $personItemArray['dcterms:created'];
        $createdFormatted = MigrationHelper::timestampToMySQLdatetime($created);
        if($createdFormatted){
            $person->setField('Created', $createdFormatted);
        }

        $firstName = $personItemArray['metadata_foaf:givenName'];
        if(!empty(trim($firstName))){
            $person->setField('FirstName', trim($firstName));
        }

        $surname = $personItemArray['metadata_foaf:familyName'];
        if(empty(trim($surname))){
            $surname = '(no name)';
        }
        $person->setField('Surname', trim($surname));
        $migrationNote = null;
        $email = $personItemArray['email'];
        if(!empty(trim($email))){
            $singleEmail = explode(',', $email)[0];
            $checkDuplicatePerson = Person::get()->filter(['Email' => $singleEmail])->first();
            if($checkDuplicatePerson){
                // duplicate found so remove email for now and add note to migrationlog
                $person->SkipEmail = 1;
                $migrationNote = 'Person with same email already exists, email removed for this person [' . $checkDuplicatePerson->Uuid . ']' . "\n";
            }else {
                $person->setField('Email', trim($singleEmail));
            }
        }else{
            $person->SkipEmail = 1;
        }

        $academicTitle = $personItemArray['metadata_vcard:honorific-suffix'];
        if(!empty(trim($academicTitle))){
            $person->setField('AcademicTitle', trim($academicTitle));
        }else{
            $person->setField('AcademicTitle', null);
        }

        $formOfAddress = $personItemArray['metadata_foaf:title'];
        if(!empty(trim($formOfAddress))){
            $person->setField('FormOfAddress', trim($formOfAddress));
        }else{
            $person->setField('FormOfAddress', null);
        }

        $surnamePrefix = $personItemArray['metadata_infix'];
        if(!empty(trim($surnamePrefix))){
            $person->setField('SurnamePrefix', trim($surnamePrefix));
        }else{
            $person->setField('SurnamePrefix', null);
        }

        $dai = $personItemArray['dai'];
        if(!empty(trim($dai))){
            $person->setField('PersistentIdentifier', trim($dai));
        }else{
            $person->setField('PersistentIdentifier', null);
        }
// add user to member group
        $organizationIdentifier = $personItemArray['migrationOrganizationIdentifier'];
        $person->SkipEmail = true; // skip email validation
        if($person->Groups()->count()){
            // person already in group, so write
            try {
                $person->write();
            } catch (ValidationException $e) {
                $migrationNote .= 'Cannot write person : ' . $e->getMessage();
            }
        }
        else {
            // person not in group yet, so add to migrationgroup
            try {
                // setBaseInstitute also writes person
                MigrationHelper::setBaseInstitute($person, $organizationIdentifier);
            } catch (Exception $e) {
                $migrationNote .= 'Cannot add person to default group : ' . $e->getMessage();
            }
        }
        Logger::debugLog("UUID : " . $person->Uuid . " : " . $person->getFullName() . "\n");

        $migrationLogItem = MigrationLog::get()->filter(['Uuid' => $personItemArray['identifier']])->first();
        if(is_null($migrationLogItem)) {
            $migrationLogItem = MigrationLog::create();
            $migrationLogItem->setField('Uuid', $personItemArray['identifier']);
        }

        $jsonData = json_encode($personItemArray, 4194304);

        //echo($jsonData);
        $migrationLogItem->setField('Data', $jsonData);
        $migrationLogItem->setField('Source', $source);
        $migrationLogItem->setField('MigratedAt', $this->migratedAt);
        $migrationLogItem->setField('Log', $migrationNote);
        $migrationLogItem->TargetObject = $person;

        try {
            $migrationLogItem->write();
        } catch (ValidationException $e) {
            // skip for now
        }
    }

    function createOrUpdateAuthors(mysqli $db, $offset, $limit, $organizationIdentifiers = null){
        if( !mysqli_ping($db) ) {
            $db = MigrationHelper::OpenMigrationDatabase();
        }
        $organizationFilter = '';
        if(!is_null($organizationIdentifiers)) {
            $organizationFilter = ' AND organizationIdentifier in (\'' . implode('\',\'', $organizationIdentifiers) . '\') ';
        }
        $result = $db->query("
    SELECT `wlUniquePublicationRecordPersons`.`identifier`,
    `wlUniquePublicationRecordPersons`.`dcterms:title`,
    `wlUniquePublicationRecordPersons`.`linkIdentifier`,
    `wlUniquePublicationRecordPersons`.`predicate`,
    `wlUniquePublicationRecordPersons`.`personIdentifier`,
    `wlUniquePublicationRecordPersons`.`metadata_foaf:familyName`,
    `wlUniquePublicationRecordPersons`.`metadata_foaf:givenName`,
    `wlUniquePublicationRecordPersons`.`metadata_role`,
    `wlUniquePublicationRecordPersons`.`metadata_order`
FROM `sharekitmigration`.`wlUniquePublicationRecordPersons`
where predicate not in ('owner') AND identifier in (
        select identifier from `$this->repoItemView`
            WHERE 1=1 $organizationFilter)
    ORDER BY `wlUniquePublicationRecordPersons`.`metadata_order`
    LIMIT $limit OFFSET $offset 
        ");

        if(is_null($result) || $result==false || $result->num_rows == 0){
            return true;
        }
        while($row = $result->fetch_assoc()){
            $this->createOrUpdateAuthor($row, 'wlUniquePublicationRecordPersons');
        }

        return false;
    }

    function createOrUpdateAuthor($authorItemArray, $source){
        //   echo('Create or update ' . print_r($repoItemArray, true) . "<br><br>");

        $repoItem = RepoItem::get()->filter(['Uuid' => $authorItemArray['identifier']])->first();
        if(is_null($repoItem)){
            Logger::debugLog('Repoitem not found! ' . $authorItemArray['identifier']);
            return;
        }


        $migrationNote = '';
        $person = Person::get()->filter(['Uuid' => $authorItemArray['personIdentifier']])->first();
        if($person){
            // author does exist

            $repoItemPerson = RepoItem::get()->filter(['Uuid' => $authorItemArray['linkIdentifier']])->first();
            if(is_null($repoItemPerson)){

                Logger::debugLog($authorItemArray);
                $repoItemPerson = RepoItem::create();
                //    Logger::debugLog('Is InDB : ' . $repoItem->isInDB() ? 'true':'false');
                //    Logger::debugLog('UUID before = ' . $repoItem->Uuid);
                $repoItemPerson->setField('Uuid', $authorItemArray['linkIdentifier']);
                $repoItemPerson->setField('RepoType', 'RepoItemPerson');
                //   Logger::debugLog('UUID after setfield = ' . $repoItem->Uuid);
                //    Logger::debugLog($repoItem->Uuid);
            }elseif($this->skipExistingRecords){
                return;
            }
            $repoItem->setField('Status', 'Migrated');
            $repoItem->write();
            $repoItemPerson->setField('Status', 'Migrated');
            $repoItemPerson->setField('RepoType', 'RepoItemPerson');
            $repoItemPerson->setField('InstituteID', $repoItem->getField('InstituteID'));
            $repoItemPerson->setField('InstituteUuid', $repoItem->getField('InstituteUuid'));

            // Logger::debugLog('UUID after set title = ' . $repoItem->Uuid);
            $repoItemPerson->Title = $person->getFullName();
            Logger::debugLog("UUID : " . $repoItemPerson->Uuid . " : " . $repoItemPerson->Title . "\n");
            try {
                $repoItemPerson->write();
            } catch (Exception $e) {
                // skip for now
            }


            // Logger::debugLog('UUID after set title = ' . $repoItem->Uuid);
            // TODO, cannot use setRepoItemMetaFieldValue as this removes the pevious author, same for links and attachments!!
            //$repoItem->setRepoItemMetaFieldValue('5cede4de-2e88-4cf5-a1d7-a4f22c3241a6', []);
            /** @var RepoItemMetaField $repoItemMetaField */
            $repoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldUuid','5cede4de-2e88-4cf5-a1d7-a4f22c3241a6')->first();
            if(is_null($repoItemMetaField)){
                $repoItem->setRepoItemMetaFieldValue('5cede4de-2e88-4cf5-a1d7-a4f22c3241a6', []);
                $repoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldUuid','5cede4de-2e88-4cf5-a1d7-a4f22c3241a6')->first();;
            }
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->where(['RepoItemID'=>$repoItemPerson->ID])->first();
            if(is_null($repoItemMetaFieldValue)){
                $repoItemMetaFieldValue = RepoItemMetaFieldValue::create();
                $repoItemMetaFieldValue->setField('RepoItemMetaFieldID', $repoItemMetaField->ID);
            }
            $repoItemMetaFieldValue->setField('RepoItemID', $repoItemPerson->ID);
            $repoItemMetaFieldValue->setField('SortOrder', $authorItemArray['metadata_order']);
            try {
                $repoItemMetaFieldValue->write();
            } catch (ValidationException $e) {
            }
            Logger::debugLog("UUID : " . $repoItem->Uuid . " : " . $repoItem->Title . "\n");
            try {
                $repoItem->write();
            } catch (Exception $e) {
                // skip for now
            }

            // Person selection
            $repoItemPerson->setRepoItemMetaFieldValue('a62da0aa-2202-4fff-9d84-299c367187be', []); // persoonlijke identifier
            $repoItemPerson->setRepoItemMetaFieldValue('720ede7f-52c3-4672-9327-b7e1358d5463', []); // rol niet migreren
            $repoItemPerson->setRepoItemMetaFieldValue('a361a5a9-a80b-4e2c-8145-a60e2fce9acf', []); // persoon
            /** @var RepoItemMetaField $repoItemMetaField */
            $repoItemMetaField = $repoItemPerson->RepoItemMetaFields()->filter('MetaFieldUuid','a361a5a9-a80b-4e2c-8145-a60e2fce9acf')->first();
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->first();
            if(is_null($repoItemMetaFieldValue)){
                $repoItemMetaFieldValue = RepoItemMetaFieldValue::create();
                $repoItemMetaFieldValue->setField('RepoItemMetaFieldID', $repoItemMetaField->ID);
            }
            $repoItemMetaFieldValue->setField('PersonID', $person->ID);
            try {
                $repoItemMetaFieldValue->write();
            } catch (ValidationException $e) {
            }



        }else{
            $migrationNote = 'Author ' . $authorItemArray['personIdentifier'] . ' does not exist!';
        }


        $migrationLogItem = MigrationLog::get()->filter(['Uuid' => $authorItemArray['linkIdentifier']])->first();
        if(is_null($migrationLogItem)) {
            $migrationLogItem = MigrationLog::create();
            $migrationLogItem->setField('Uuid', $authorItemArray['linkIdentifier']);
        }
        $jsonData = json_encode($authorItemArray, 4194304);

        //echo($jsonData);
        $migrationLogItem->setField('Data', $jsonData);
        $migrationLogItem->setField('Source', $source);
        $migrationLogItem->setField('MigratedAt', $this->migratedAt);
        $migrationLogItem->setField('Log', $migrationNote);
        $migrationLogItem->TargetObject = $repoItemPerson;

        try {
            $migrationLogItem->write();
        } catch (ValidationException $e) {
            // skip for now
        }

    }

    function createOrUpdateAttachments(mysqli $db, $offset, $limit, $organizationIdentifiers=null){
        Logger::debugLog('Next batch ' . $offset . ' -> ' . $limit);
        $db = MigrationHelper::OpenMigrationDatabase();
        Logger::debugLog(var_export($db, true));
        $organizationFilter = '';
        if(!is_null($organizationIdentifiers)) {
            $organizationFilter = ' AND organizationIdentifier in (\'' . implode('\',\'', $organizationIdentifiers) . '\') ';
        }

        $result = $db->query("
        SELECT `wlPublicationRecordAttachments`.`identifier`,
        `wlPublicationRecordAttachments`.`dcterms:title`,
        `wlPublicationRecordAttachments`.`dcterms:rights`,
        `wlPublicationRecordAttachments`.`linkIdentifier`,
        `wlPublicationRecordAttachments`.`attachmentIdentifier`,
        `wlPublicationRecordAttachments`.`filename`,
        `wlPublicationRecordAttachments`.`contentType`,
        `wlPublicationRecordAttachments`.`name`,
        `wlPublicationRecordAttachments`.`metadata_order`
        FROM `sharekitmigration`.`wlPublicationRecordAttachments`
        where identifier in (
        select identifier from `$this->repoItemView`
            WHERE 1=1 $organizationFilter)
        order by metadata_order
        LIMIT $limit OFFSET $offset 
        ");

        if(is_null($result) || $result==false || $result->num_rows == 0){
            return true;
        }
        $rows = [];
        while($row = $result->fetch_assoc()){
            $rows[] = $row;
        }
        foreach($rows as $row) {
            Logger::debugLog('Next item -> ' . $row['identifier']);
            $this->createOrUpdateAttachment($row, 'wlPublicationRecordAttachments');
        }

        Logger::debugLog('Free result');
        $result->free_result();
        Logger::debugLog('Close DB');
        $db->close();
        return false;
    }

    function createOrUpdateAttachment($attachmentItemArray, $source){
        //   echo('Create or update ' . print_r($repoItemArray, true) . "<br><br>");

        Logger::debugLog($attachmentItemArray);

        $repoItem = RepoItem::get()->filter(['Uuid' => $attachmentItemArray['identifier']])->first();
        if(!$repoItem){
            Logger::debugLog('Repoitem not found! ' . $attachmentItemArray['identifier']);
            return;
        }

        $migrationNote = '';
        $repoItemFile = RepoItemFile::get()->filter(['Uuid' => $attachmentItemArray['attachmentIdentifier']])->first();
        if(is_null($repoItemFile)) {
            Logger::debugLog('RepoItemFile not found ' . $attachmentItemArray['attachmentIdentifier'] . ', so create');
            $repoItemFile = RepoItemFile::create();
            //    Logger::debugLog('Is InDB : ' . $repoItem->isInDB() ? 'true':'false');
            //    Logger::debugLog('UUID before = ' . $repoItem->Uuid);
            $repoItemFile->setField('Uuid', $attachmentItemArray['attachmentIdentifier']);
            $uuid = $attachmentItemArray['attachmentIdentifier'];
            $folder = substr($uuid, 0, 2);
            $resourceURL = "http://nl-zooma-surf-sharekit-migration-vps.westeurope.cloudapp.azure.com:8080/resources/$folder/$uuid.resource";
            Logger::debugLog('Trying to get resource from ' . $resourceURL);
            $resource = fopen($resourceURL, "r");
            if($resource){
                try{
                    Logger::debugLog('Set from stream to file/' . $uuid);
                    $repoItemFile->setFromStream($resource, 'file/' . $uuid . '/' . FileNameFilter::singleton()->filter($attachmentItemArray['filename']));
                    Logger::debugLog('Finished setting from stream');
                }catch (ValidationException $e){
                    // do nothing
                    Logger::debugLog('Failed to set stream ' . $uuid . ' msg:' .$e->getMessage());
                    $migrationNote = 'Failed to set stream ' . $uuid . ' msg:' .$e->getMessage();
                }catch (Exception $e){
                    // do nothing
                    Logger::debugLog('Failed to set stream ' . $uuid . ' msg:' .$e->getMessage());
                    $migrationNote = 'Failed to set stream ' . $uuid . ' msg:' .$e->getMessage();
                }

                fclose($resource);
            }else{
                Logger::debugLog('Failed to download resource ' . $uuid);
                $migrationNote = 'Failed to download resource ' . $uuid;
            }
            try {
                $repoItemFile->write();
            } catch (ValidationException $e) {
                Logger::debugLog('Failed to write repoitemfile ' . $uuid . ' msg:' .$e->getMessage());
                $migrationNote = 'Failed to write repoitemfile ' . $uuid . ' msg:' .$e->getMessage();
            }
        }

        $repoItemRepoItemFile = RepoItem::get()->filter(['Uuid' => $attachmentItemArray['linkIdentifier']])->first();
        if(is_null($repoItemRepoItemFile)){
            Logger::debugLog('RepoItemRepoItemFile not found ' . $attachmentItemArray['linkIdentifier'] . ', so create');
            $repoItemRepoItemFile = RepoItem::create();
            //    Logger::debugLog('Is InDB : ' . $repoItem->isInDB() ? 'true':'false');
            //    Logger::debugLog('UUID before = ' . $repoItem->Uuid);
            $repoItemRepoItemFile->setField('Uuid', $attachmentItemArray['linkIdentifier']);
            $repoItemRepoItemFile->setField('RepoType', 'RepoItemRepoItemFile');
            //   Logger::debugLog('UUID after setfield = ' . $repoItem->Uuid);
            //    Logger::debugLog($repoItem->Uuid);
        }elseif($this->skipExistingRecords){
            return;
        }
        $repoItemRepoItemFile->setField('Status', 'Migrated');
        $repoItemRepoItemFile->setField('InstituteID', $repoItem->getField('InstituteID'));
        $repoItemRepoItemFile->setField('InstituteUuid', $repoItem->getField('InstituteUuid'));
        // Logger::debugLog('UUID after set title = ' . $repoItem->Uuid);
        $repoItemRepoItemFile->Title = strlen(trim($attachmentItemArray['name'])) > 0?$attachmentItemArray['name']:$attachmentItemArray['filename'];
        Logger::debugLog("UUID : " . $repoItemRepoItemFile->Uuid . " : " . $repoItemRepoItemFile->Title . "\n");
        try {
            $repoItemRepoItemFile->write();
        } catch (Exception $e) {
            // skip for now
            Logger::debugLog('Failed to write repoItemRepoitemfile ' . $attachmentItemArray['linkIdentifier'] . ' msg:' .$e->getMessage());
            $migrationNote = 'Failed to write repoItemRepoitemfile ' . $attachmentItemArray['linkIdentifier'] . ' msg:' .$e->getMessage();

        }


        $repoItem->setField('Status', 'Migrated');
        $repoItem->write();
        // Logger::debugLog('UUID after set title = ' . $repoItem->Uuid);
        // $repoItem->setRepoItemMetaFieldValue('27c41422-eca0-4350-b32b-6ee20135705e', []);
        Logger::debugLog("Set Metafields for Repoitem");
        /** @var RepoItemMetaField $repoItemMetaField */
        $repoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldUuid','27c41422-eca0-4350-b32b-6ee20135705e')->first();
        if(is_null($repoItemMetaField)){
            $repoItem->setRepoItemMetaFieldValue('27c41422-eca0-4350-b32b-6ee20135705e', []);
            $repoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldUuid','27c41422-eca0-4350-b32b-6ee20135705e')->first();;
        }
        $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->where(['RepoItemID'=>$repoItemRepoItemFile->ID])->first();
        if(is_null($repoItemMetaFieldValue)){
            $repoItemMetaFieldValue = RepoItemMetaFieldValue::create();
            $repoItemMetaFieldValue->setField('RepoItemMetaFieldID', $repoItemMetaField->ID);
        }

        $repoItemMetaFieldValue->setField('RepoItemID', $repoItemRepoItemFile->ID);
        $repoItemMetaFieldValue->setField('SortOrder', $attachmentItemArray['metadata_order']);
        try {
            $repoItemMetaFieldValue->write();
        } catch (ValidationException $e) {
        }
        Logger::debugLog("UUID : " . $repoItem->Uuid . " : " . $repoItem->Title . "\n");
        try {
            $repoItem->write();
        } catch (Exception $e) {
            // skip for now
        }
        Logger::debugLog("Set Metafields for Repoitemrepoitemfile");
        $repoItemRepoItemFile->setRepoItemMetaFieldValue('32efdf11-d6ce-482d-a0c7-67b509214f3a', [$attachmentItemArray['filename']]);
        $repoItemRepoItemFile->setRepoItemMetaFieldValue('3b05ec63-b9ce-4d0c-bd3a-91b2229ca21e', ['openaccess']);
        $mappedValue = MigrationMapper::mapMetaFields('d418da1d-ca28-40b2-888f-b21dccbd9f5d',  $attachmentItemArray['dcterms:rights']);
        $repoItemRepoItemFile->setRepoItemMetaFieldValue('d418da1d-ca28-40b2-888f-b21dccbd9f5d', [$mappedValue]);
        $repoItemRepoItemFile->setRepoItemMetaFieldValue('2af0793b-fc97-455c-ae71-494425905868', []);
        /** @var RepoItemMetaField $repoItemMetaField */
        $repoItemMetaField = $repoItemRepoItemFile->RepoItemMetaFields()->filter('MetaFieldUuid','2af0793b-fc97-455c-ae71-494425905868')->first();
        $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->first();
        if(is_null($repoItemMetaFieldValue)){
            $repoItemMetaFieldValue = RepoItemMetaFieldValue::create();
            $repoItemMetaFieldValue->setField('RepoItemMetaFieldID', $repoItemMetaField->ID);
        }
        $repoItemMetaFieldValue->setField('RepoItemFileID', $repoItemFile->ID);
        if(!$repoItemFile->isPublished()){
            try {
                Logger::debugLog("Try to publish file");
                $repoItemFile->publishSingle();
            } catch (Exception $e){
                $migrationNote = 'Failed to publish file ' . $repoItemFile->Uuid;
                Logger::debugLog('Failed to publish file ' . $repoItemFile->Uuid);
            }
        }
        try {
            $repoItemMetaFieldValue->write();
        } catch (ValidationException $e) {
        }

        $migrationLogItem = MigrationLog::get()->filter(['Uuid' => $attachmentItemArray['linkIdentifier']])->first();
        if(is_null($migrationLogItem)) {
            $migrationLogItem = MigrationLog::create();
            $migrationLogItem->setField('Uuid', $attachmentItemArray['linkIdentifier']);
        }
        $jsonData = json_encode($attachmentItemArray, 4194304);

        //echo($jsonData);
        $migrationLogItem->setField('Data', $jsonData);
        $migrationLogItem->setField('Source', $source);
        $migrationLogItem->setField('MigratedAt', $this->migratedAt);
        $migrationLogItem->setField('Log', $migrationNote);
        $migrationLogItem->TargetObject = $repoItemRepoItemFile;

        try {
            $migrationLogItem->write();
        } catch (ValidationException $e) {
            // skip for now
        }
    }

    function createOrUpdateLinks(mysqli $db, $offset, $limit, $organizationIdentifiers=null){
        if( !mysqli_ping($db) ) {
            $db = MigrationHelper::OpenMigrationDatabase();
        }
        $organizationFilter = '';
        if(!is_null($organizationIdentifiers)) {
            $organizationFilter = ' AND organizationIdentifier in (\'' . implode('\',\'', $organizationIdentifiers) . '\') ';
        }

        $result = $db->query("
        SELECT `wlPublicationRecordURLs`.`identifier`,
        `wlPublicationRecordURLs`.`dcterms:title`,
        `wlPublicationRecordURLs`.`dcterms:rights`,
        `wlPublicationRecordURLs`.`linkIdentifier`,
        `wlPublicationRecordURLs`.`urlIdentifier`,
        `wlPublicationRecordURLs`.`name`,
        `wlPublicationRecordURLs`.`sourceURL`,
        `wlPublicationRecordURLs`.`name`,
        `wlPublicationRecordURLs`.`metadata_order`
        FROM `sharekitmigration`.`wlPublicationRecordURLs`
        where identifier in (
        select identifier from `$this->repoItemView`
            WHERE 1=1 $organizationFilter)
        order by `wlPublicationRecordURLs`.`metadata_order`
        LIMIT $limit OFFSET $offset 
        ");

        if(is_null($result) || $result==false || $result->num_rows == 0){
            return true;
        }
        while($row = $result->fetch_assoc()){
            $this->createOrUpdateLink($row, 'wlPublicationRecordURLs');
        }

        return false;
    }

    function createOrUpdateLink($linkItemArray, $source){
        Logger::debugLog($linkItemArray);

        $repoItem = RepoItem::get()->filter(['Uuid' => $linkItemArray['identifier']])->first();
        if(!$repoItem){
            Logger::debugLog('Repoitem not found! ' . $linkItemArray['identifier']);
            return;
        }

        $migrationNote = '';
        $repoItemLink = RepoItem::get()->filter(['Uuid' => $linkItemArray['linkIdentifier']])->first();
        if(is_null($repoItemLink)){
            $repoItemLink = RepoItem::create();
            $repoItemLink->setField('Uuid', $linkItemArray['linkIdentifier']);
            $repoItemLink->setField('RepoType', 'RepoItemLink');
        }elseif($this->skipExistingRecords){
            return;
        }

        $repoItemLink->setField('Status', 'Migrated');
        $repoItemLink->setField('InstituteID', $repoItem->getField('InstituteID'));
        $repoItemLink->setField('InstituteUuid', $repoItem->getField('InstituteUuid'));

        $repoItemLink->Title = trim($linkItemArray['sourceURL']);
        Logger::debugLog("UUID : " . $repoItemLink->Uuid . " : " . $repoItemLink->Title . "\n");
        try {
            $repoItemLink->write();
        } catch (Exception $e) {
            // skip for now
        }

        $repoItem->setField('Status', 'Migrated');
        $repoItem->write();

        /** @var RepoItemMetaField $repoItemMetaField */
        $repoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldUuid','61c42b37-6515-473f-87a3-b8e46763bd79')->first();
        if(is_null($repoItemMetaField)){
            $repoItem->setRepoItemMetaFieldValue('61c42b37-6515-473f-87a3-b8e46763bd79', []);
            $repoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldUuid','61c42b37-6515-473f-87a3-b8e46763bd79')->first();;
        }
        $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->where(['RepoItemID'=>$repoItemLink->ID])->first();;
        if(is_null($repoItemMetaFieldValue)){
            $repoItemMetaFieldValue = RepoItemMetaFieldValue::create();
            $repoItemMetaFieldValue->setField('RepoItemMetaFieldID', $repoItemMetaField->ID);
        }
        $repoItemMetaFieldValue->setField('RepoItemID', $repoItemLink->ID);
        $repoItemMetaFieldValue->setField('SortOrder', $linkItemArray['metadata_order']);
        try {
            $repoItemMetaFieldValue->write();
        } catch (ValidationException $e) {
        }
        Logger::debugLog("UUID : " . $repoItem->Uuid . " : " . $repoItem->Title . "\n");
        try {
            $repoItem->write();
        } catch (Exception $e) {
            // skip for now
        }

        $migrationLogItem = MigrationLog::get()->filter(['Uuid' => $linkItemArray['linkIdentifier']])->first();
        if(is_null($migrationLogItem)) {
            $migrationLogItem = MigrationLog::create();
            $migrationLogItem->setField('Uuid', $linkItemArray['linkIdentifier']);
        }
        $jsonData = json_encode($linkItemArray, 4194304);

        $migrationLogItem->setField('Data', $jsonData);
        $migrationLogItem->setField('Source', $source);
        $migrationLogItem->setField('MigratedAt', $this->migratedAt);
        $migrationLogItem->setField('Log', $migrationNote);
        $migrationLogItem->TargetObject = $repoItemLink;

        try {
            $migrationLogItem->write();
        } catch (ValidationException $e) {
            // skip for now
        }
        $repoItemLink->setRepoItemMetaFieldValue('80e3e484-1a87-4448-afb3-26a3e3698b7a', [$linkItemArray['sourceURL']]);
        $repoItemLink->setRepoItemMetaFieldValue('3b05ec63-b9ce-4d0c-bd3a-91b2229ca21e', ['openaccess']);
        $mappedValue = MigrationMapper::mapMetaFields('d418da1d-ca28-40b2-888f-b21dccbd9f5d',  $linkItemArray['dcterms:rights']);
        $repoItemLink->setRepoItemMetaFieldValue('d418da1d-ca28-40b2-888f-b21dccbd9f5d', [$mappedValue]);
    }

    function createOrUpdateDisciplines(mysqli $db, $offset, $limit, $organizationIdentifiers=null){
        if( !mysqli_ping($db) ) {
            $db = MigrationHelper::OpenMigrationDatabase();
        }
        $organizationFilter = '';
        if(!is_null($organizationIdentifiers)) {
            $organizationFilter = ' AND rootIdentifier in (\'' . implode('\',\'', $organizationIdentifiers) . '\') ';
        }

        $result = $db->query("
        SELECT `vwtPublicationRecordLowestOrganizations`.`identifier`,
            `vwtPublicationRecordLowestOrganizations`.`rootIdentifier`,
            `vwtPublicationRecordLowestOrganizations`.`rootName`,
            `vwtPublicationRecordLowestOrganizations`.`organizationIdentifier`
        FROM `sharekitmigration`.`vwtPublicationRecordLowestOrganizations`
        where  1=1 $organizationFilter
        LIMIT $limit OFFSET $offset 
        ");

        if(is_null($result) || $result==false || $result->num_rows == 0){
            return true;
        }
        while($row = $result->fetch_assoc()){
            $this->createOrUpdateDiscipline($row, 'vwtPublicationRecordLowestOrganizations');
        }

        return false;
    }


    function createOrUpdateDiscipline($itemArray, $source){
        Logger::debugLog($itemArray);

        $repoItem = RepoItem::get()->filter(['Uuid' => $itemArray['identifier']])->first();
        if(!$repoItem){
            Logger::debugLog('Repoitem not found! ' . $itemArray['identifier']);
            return;
        }

        // lectorate or discipline
        if($repoItem->RepoType == 'PublicationRecord'){
            $metaFieldUuid = '75e5f043-8a75-4849-81ca-3e556f5c803c';
        }elseif($repoItem->RepoType == 'ResearchObject'){
            $metaFieldUuid = 'b2a05bb9-d9a6-45db-95e5-2f23ea6e20ba';
        }
        else{
            // leermatriaal, skip for now
            return;
        }

        $origStatus = $repoItem->getField('Status');
        DB::prepared_query('update SurfSharekit_RepoItem set Status = \'Migrated\' where Uuid = ?', [$repoItem->getField('Uuid')]);

        /** @var RepoItemMetaField $repoItemMetaField */
        $repoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldUuid',$metaFieldUuid)->first();
        if(is_null($repoItemMetaField)){
            $repoItem->setRepoItemMetaFieldValue($metaFieldUuid, []);
            $repoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldUuid',$metaFieldUuid)->first();;
        }
        $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->first();;
        if(is_null($repoItemMetaFieldValue)){
            $repoItemMetaFieldValue = RepoItemMetaFieldValue::create();
            $repoItemMetaFieldValue->setField('RepoItemMetaFieldID', $repoItemMetaField->ID);
        }
        $institute = UuidExtension::getByUuid(Institute::class, $itemArray['organizationIdentifier']);
        if(!is_null($institute)) {
            $repoItemMetaFieldValue->setField('InstituteID', $institute->ID);
            $repoItemMetaFieldValue->setField('InstituteUuid', $institute->getField('Uuid'));
            try {
                $repoItemMetaFieldValue->write();
            } catch (ValidationException $e) {
            }
        }
        DB::prepared_query('update SurfSharekit_RepoItem set Status = ? where Uuid = ?', [$origStatus, $repoItem->getField('Uuid')]);
    }

}