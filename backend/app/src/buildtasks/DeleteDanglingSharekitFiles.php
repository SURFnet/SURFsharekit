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
    protected $description = "Deletes dangling files from the database that are not used in any repo item";

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

        $repoItemFiles = $this->getDanglingRepoItemFiles();

        foreach ($repoItemFiles as $repoItemFile) {
            // 1. Delete S3 Object
            $this->execute(function() use ($repoItemFile) {
                $this->deleteS3Object($repoItemFile['S3Key']);
            }, "Deleting S3 object with key: " . $repoItemFile['S3Key']);

            // check if S3 Object is deleted (only checked on actual run)
            if (!$this->isDryRunEnabled() && $this->S3ObjectExists($repoItemFile['S3Key'])) {
                $this->print('Object not deleted... break');
                continue;
            }

            // 2. Delete RepoItemFiles
            $this->execute(function () use ($repoItemFile) {
                $this->removeRecord(RepoItemFile::class, [$repoItemFile['ID']]);
            }, "Deleting RepoItemFiles: " . $repoItemFile['ID']);

            // 3. Delete RepoItemMetaFieldValues
            $repoItemMetaFieldValues = $this->getRepoItemMetaFieldValues($repoItemFile['RepoItemID']);
            $repoItemMetaFieldValuesIds = array_map(fn ($r) => $r['ID'], $repoItemMetaFieldValues);
            $this->execute(function () use ($repoItemMetaFieldValuesIds) {
                $this->removeRecord(RepoItemMetaFieldValue::class, $repoItemMetaFieldValuesIds);
            }, "Deleting RepoItemMetaFieldValues: " . implode(', ', $repoItemMetaFieldValuesIds));

            // 4. Delete RepoItemMetaFields
            $repoItemMetaFields = $this->getRepoItemMetaFields($repoItemFile['RepoItemID']);
            $repoItemMetaFieldIds = array_map(fn ($r) => $r['ID'], $repoItemMetaFields);
            $this->execute(function () use ($repoItemMetaFieldIds) {
                $this->removeRecord(RepoItemMetaField::class, $repoItemMetaFieldIds);
            }, "Deleting RepoItemMetaFields: " . implode(', ', $repoItemMetaFieldIds));

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

        while ($next = $statement->nextRecord()) {
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

        while ($next = $statement->nextRecord()) {
            $results[] = $next;
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

        while ($next = $statement->nextRecord()) {
            $results[] = $next;
        }

        return $results;
    }

    private function removeRecord(string $class, array $ids) {
        $tableName = Config::inst()->get($class, 'table_name');

        $where = "ID IN (". implode(',', array_fill(0, count($ids), '?')) .")";

        (new SQLDelete())
            ->setFrom($tableName)
            ->setWhere([$where => $ids])
            ->execute();
    }

    private function recordCount(string $class, array $ids): int {
        if (count($ids) == 0) {
            return 0;
        }

        $tableName = Config::inst()->get($class, 'table_name');
        $where = "ID IN (". implode(',', array_fill(0, count($ids), '?')) .")";

        $k = (new SQLSelect())
            ->setFrom($tableName)
            ->setWhere([$where => $ids])
            ->execute();

        return $k->numRecords();
    }

    private function getS3Object(string $key): Result {
        return $this->s3Client->getObject([
            'Bucket' => Environment::getEnv('AWS_BUCKET_NAME'),
            'Key' => $key
        ]);
    }

    private function S3ObjectExists(string $key): bool {
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

    private function deleteS3Object(string $key): Result {
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