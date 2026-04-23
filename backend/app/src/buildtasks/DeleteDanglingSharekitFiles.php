<?php

namespace SurfSharekit\Tasks;

use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\Queries\SQLSelect;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;

class DeleteDanglingSharekitFiles extends BuildTask
{
    protected $title = "Delete dangling Sharekit files";
    protected $description = "Deletes dangling files from the database that are either not used in any repo item or not referenced by any metadata value";

    private bool $dryRun = true;

    private S3Client $s3Client;

    public function __construct() {
        parent::__construct();

        $this->s3Client = Injector::inst()->create(S3Client::class);
    }

    public function run($request) {
        if ($request->getVar('dryRun') !== null) {
            $this->dryRun = !!$request->getVar('dryRun');
        }

        $this->print("Dry run: " . ($this->isDryRunEnabled() ? "enabled" : "disabled"));
        $this->print("---");

        // Process dangling repo item files (files with orphaned parent repo items)
        $this->print("Processing dangling repo item files with orphaned parent repo items:");
        $repoItemFiles = $this->getDanglingRepoItemFiles();
    
        foreach ($repoItemFiles as $repoItemFile) {
            // 1. Delete S3 Object if S3Key exists
            if (!empty($repoItemFile['S3Key'])) {
                $this->execute(function() use ($repoItemFile) {
                    $this->deleteS3Object($repoItemFile['S3Key']);
                }, "Deleting S3 object with key: " . $repoItemFile['S3Key']);
        
                // check if S3 Object is deleted (only checked on actual run)
                if (!$this->isDryRunEnabled() && $this->S3ObjectExists($repoItemFile['S3Key'])) {
                    $this->print('Object not deleted... break');
                    continue;
                }
            } else {
                $this->print("No S3Key found for RepoItemFile: " . $repoItemFile['ID']);
            }
    
            // 2. Delete RepoItemFiles
            $this->execute(function () use ($repoItemFile) {
                $this->removeRecord(RepoItemFile::class, [$repoItemFile['ID']]);
            }, "Deleting RepoItemFiles: " . $repoItemFile['ID']);
    
            // 3. Delete RepoItemMetaFieldValues
            $repoItemMetaFieldValues = $this->getRepoItemMetaFieldValues($repoItemFile['RepoItemID']);
            $repoItemMetaFieldValuesIds = array_map(fn ($r) => $r['ID'], $repoItemMetaFieldValues);
            if (!empty($repoItemMetaFieldValuesIds)) {
                $this->execute(function () use ($repoItemMetaFieldValuesIds) {
                    $this->removeRecord(RepoItemMetaFieldValue::class, $repoItemMetaFieldValuesIds);
                }, "Deleting RepoItemMetaFieldValues: " . implode(', ', $repoItemMetaFieldValuesIds));
            } else {
                $this->print("No RepoItemMetaFieldValues to delete for RepoItem: " . $repoItemFile['RepoItemID']);
            }
                
            // 4. Delete RepoItemMetaFields
            $repoItemMetaFields = $this->getRepoItemMetaFields($repoItemFile['RepoItemID']);
            $repoItemMetaFieldIds = array_map(fn ($r) => $r['ID'], $repoItemMetaFields);
            if (!empty($repoItemMetaFieldIds)) {
                $this->execute(function () use ($repoItemMetaFieldIds) {
                    $this->removeRecord(RepoItemMetaField::class, $repoItemMetaFieldIds);
                }, "Deleting RepoItemMetaFields: " . implode(', ', $repoItemMetaFieldIds));
            } else {
                $this->print("No RepoItemMetaFields to delete for RepoItem: " . $repoItemFile['RepoItemID']);
            }
    
            // Double check if everything is deleted until here... if not we continue (only checked on actual run)
            if (
                !$this->isDryRunEnabled() && (
                    $this->recordCount(RepoItemFile::class, [$repoItemFile['ID']]) > 0 ||
                    $this->recordCount(RepoItemMetaFieldValue::class, $repoItemMetaFieldValuesIds) > 0 ||
                    $this->recordCount(RepoItemMetaField::class, $repoItemMetaFieldIds) > 0
                )
            ) {
                $this->print("Deleting failed... break");
                continue;
            }
    
            // 5. Delete RepoItems
            $this->execute(function () use ($repoItemFile) {
                $this->removeRecord(RepoItem::class, [$repoItemFile['RepoItemID']]);
            }, "Deleting RepoItems: " . $repoItemFile['RepoItemID']);
    
            $this->print('---------');
        }
        
        // Process orphaned repo item files (files not referenced by any metadata value)
        $this->print("Processing orphaned repo item files (not referenced by any metadata):");
        $orphanedFiles = $this->getOrphanedRepoItemFiles();
        
        foreach ($orphanedFiles as $orphanedFile) {
            // 1. Delete S3 Object if S3Key exists
            if (!empty($orphanedFile['S3Key'])) {
                $this->execute(function() use ($orphanedFile) {
                    $this->deleteS3Object($orphanedFile['S3Key']);
                }, "Deleting S3 object with key: " . $orphanedFile['S3Key']);
            } else {
                $this->print("No S3Key found for orphaned RepoItemFile: " . $orphanedFile['ID']);
            }
            
            // 2. Delete the orphaned RepoItemFile record
            $this->execute(function () use ($orphanedFile) {
                $this->removeRecord(RepoItemFile::class, [$orphanedFile['ID']]);
            }, "Deleting orphaned RepoItemFile: " . $orphanedFile['ID']);
            
            // 3. Check if File record still exists and delete it if necessary
            if ($this->recordCount('File', [$orphanedFile['ID']]) > 0) {
                $this->execute(function () use ($orphanedFile) {
                    // Using direct table name for File since it might not be a full class reference
                    $this->removeRecordFromTable('File', [$orphanedFile['ID']]);
                }, "Deleting associated File record: " . $orphanedFile['ID']);
            }
        
            $this->print('---------');
        }
    }

    private function getDanglingRepoItemFiles() {
        $result = [];

        $statement = DB::prepared_query("
            SELECT SurfSharekit_RepoItemFile.ID, SurfSharekit_RepoItemFile.S3Key, SurfSharekit_RepoItem.ID as RepoItemID FROM SurfSharekit_RepoItemFile
            INNER JOIN SurfSharekit_RepoItemMetaFieldValue ON SurfSharekit_RepoItemMetaFieldValue.RepoItemFileID = SurfSharekit_RepoItemFile.ID
            INNER JOIN SurfSharekit_RepoItemMetaField ON SurfSharekit_RepoItemMetaField.ID = SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID
            INNER JOIN SurfSharekit_RepoItem ON SurfSharekit_RepoItem.ID = SurfSharekit_RepoItemMetaField.RepoItemID
            Where SurfSharekit_RepoItem.ID IN (
                SELECT ID FROM SurfSharekit_RepoItem
                WHERE RepoType = 'RepoItemRepoItemFile' AND 
                      ID NOT IN (SELECT RepoItemID FROM SurfSharekit_RepoItemMetaFieldValue) AND 
                      SurfSharekit_RepoItem.Created < ?
            );
        ", [(new \DateTime())->modify('-1 day')->format('Y-m-d')]);

        foreach ($statement as $next) {
            $result[] = $next;
        }


        return $result;
    }
    
    /**
     * Find RepoItemFile records that are not referenced by any RepoItemMetaFieldValue
     * and are older than 1 day
     * 
     * @return array Array of orphaned RepoItemFile records with ID and S3Key
     */
    private function getOrphanedRepoItemFiles() {
        $result = [];
        
        $statement = DB::prepared_query("
            SELECT SurfSharekit_RepoItemFile.ID, SurfSharekit_RepoItemFile.S3Key 
            FROM SurfSharekit_RepoItemFile
            INNER JOIN File ON File.ID = SurfSharekit_RepoItemFile.ID
            WHERE NOT EXISTS (
                SELECT 1 
                FROM SurfSharekit_RepoItemMetaFieldValue 
                WHERE RepoItemFileID = SurfSharekit_RepoItemFile.ID
            )
            AND File.Created < ?
        ", [(new \DateTime())->modify('-1 day')->format('Y-m-d')]);

        foreach ($statement as $next) {
            $result[] = $next;
        }
        
        return $result;
    }

    private function getRepoItemMetaFieldValues(int $repoItemId) {
        $results = [];

        $statement = DB::prepared_query("
            SELECT SurfSharekit_RepoItemMetaFieldValue.* FROM SurfSharekit_RepoItemMetaFieldValue
            INNER JOIN SurfSharekit_RepoItemMetaField ON SurfSharekit_RepoItemMetaField.ID = SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID
            INNER JOIN SurfSharekit_RepoItem ON SurfSharekit_RepoItem.ID = SurfSharekit_RepoItemMetaField.RepoItemID
            Where SurfSharekit_RepoItem.ID IN (
                SELECT ID FROM SurfSharekit_RepoItem
                WHERE RepoType = 'RepoItemRepoItemFile' AND ID IN (?)
            );
        ", [$repoItemId]);

        foreach ($statement as $next) {
            $result[] = $next;
        }

        return $results;
    }

    private function getRepoItemMetaFields(int $repoItemId) {
        $results = [];

        $statement = DB::prepared_query("
            SELECT SurfSharekit_RepoItemMetaField.* FROM SurfSharekit_RepoItemMetaField
            INNER JOIN SurfSharekit_RepoItem ON SurfSharekit_RepoItem.ID = SurfSharekit_RepoItemMetaField.RepoItemID
            Where SurfSharekit_RepoItem.ID IN (
                SELECT ID FROM SurfSharekit_RepoItem
                WHERE RepoType = 'RepoItemRepoItemFile' AND ID IN (?)
            );
        ", [$repoItemId]);

        foreach ($statement as $next) {
            $result[] = $next;
        }


        return $results;
    }

    private function removeRecord(string $class, array $ids) {
        // Skip if no IDs provided
        if (empty($ids)) {
            $this->print("No IDs provided for " . $class . " deletion, skipping");
            return;
        }
        
        try {
            $tableName = Config::inst()->get($class, 'table_name');
            
            // Check if table name was successfully resolved
            if (empty($tableName)) {
                $this->print("Could not resolve table name for class: " . $class . ", skipping deletion");
                return;
            }
            
            $where = "ID IN (". implode(',', array_fill(0, count($ids), '?')) .")";
            
            $delete = new SQLDelete();
            $delete->setFrom($tableName);
            $delete->setWhere([$where => $ids]);
            $delete->execute();
            
            //TODO: delete versioning information as well
        } catch (\Exception $e) {
            $this->print("Error deleting records from " . $class . ": " . $e->getMessage());
        }
    }

    /**
     * Remove records from a table directly by specifying the table name
     * 
     * @param string $tableName The database table name
     * @param array $ids IDs to delete
     * @return void
     */
    private function removeRecordFromTable(string $tableName, array $ids) {
        // Skip if no IDs provided
        if (empty($ids)) {
            $this->print("No IDs provided for table " . $tableName . " deletion, skipping");
            return;
        }
        
        try {
            $where = "ID IN (". implode(',', array_fill(0, count($ids), '?')) .")";
            
            $delete = new SQLDelete();
            $delete->setFrom($tableName);
            $delete->setWhere([$where => $ids]);
            $delete->execute();
        } catch (\Exception $e) {
            $this->print("Error deleting records from table " . $tableName . ": " . $e->getMessage());
        }
    }
    
    private function recordCount(string $class, array $ids): int {
        if (count($ids) == 0) {
            return 0;
        }
    
        try {
            $tableName = Config::inst()->get($class, 'table_name');
            
            // If we couldn't get the table name from the class, try using the class name as the table name
            if (empty($tableName)) {
                $tableName = $class;
            }
            
            $where = "ID IN (". implode(',', array_fill(0, count($ids), '?')) .")";
    
            $k = (new SQLSelect())
                ->setFrom($tableName)
                ->setWhere([$where => $ids])
                ->execute();
    
            return $k->numRecords();
        } catch (\Exception $e) {
            $this->print("Error counting records from " . $class . ": " . $e->getMessage());
            return 0;
        }
    }

    private function getS3Object(string $key): Result {
        return $this->s3Client->getObject([
            'Bucket' => Environment::getEnv('AWS_BUCKET_NAME'),
            'Key' => $key
        ]);
    }

    private function S3ObjectExists(?string $key): bool {
        if (empty($key)) {
            return false;
        }
        
        try {
            $this->getS3Object($key);

            return true;
        } catch (S3Exception $exception) {
            if ($exception->getAwsErrorCode() === 'NoSuchKey') {
                return false;
            } else {
                return true;
            }
        }
    }

    private function deleteS3Object(?string $key): Result {
        if (empty($key)) {
            throw new \InvalidArgumentException('S3Key cannot be null or empty');
        }
        
        return $this->s3Client->deleteObject([
            'Bucket' => Environment::getEnv('AWS_BUCKET_NAME'),
            'Key' => $key
        ]);
    }

    /**
     * Only execute closure if dryRun is false
     *
     * @param \Closure $closure
     * @param $log
     * @return void
     */
    private function execute(\Closure $closure, $log) {
        if (!$this->isDryRunEnabled()) {
            $closure();
        }

        $this->print($log);
    }

    private function isDryRunEnabled(): bool {
        return $this->dryRun;
    }

    private function print($message) {
        if (Director::is_cli()) {
            echo $message;
        } else {
            echo "<span>$message</span><br>";
        }
    }
}