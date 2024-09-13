<?php

namespace SurfSharekit\Tasks;

use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use SilverStripe\Assets\File;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLInsert;
use SilverStripe\ORM\Queries\SQLUpdate;
use SurfSharekit\Models\RepoItemFile;

class SyncRepoItemFileEtagsTask extends BuildTask
{
    protected $title = "Sync RepoItemFile Etags";
    protected $description = "Sync RepoItemFile Etags from blob storage";

    private S3Client $s3Client;

    public function __construct() {
        parent::__construct();

        $this->s3Client = Injector::inst()->create(S3Client::class);
    }

    public function run($request) {
        $this->runChunked();
    }

    private function runChunked($offset = 0) {
        $filesWithoutEtag = RepoItemFile::get()->whereAny([
            'ETag = \'\'',
            'Etag IS NULL'
        ])->where('ObjectStoreCheckedAt IS NULL')->limit(1000, $offset);

        if ($filesWithoutEtag->count() == 0) {
            return;
        }

        $this->log("Found " . $filesWithoutEtag->count() . " files");

        foreach ($filesWithoutEtag as $file) {
            /** @var File $file */
            if (empty($file->S3Key) && !empty($file->FileHash)) {
                $uuid = $file->Uuid;
                $hash = substr($file->FileHash, 0, 10);
                $fileName = $file->Name;
                $key = "$uuid/$hash/$fileName";

                $found = false;
                foreach (['protected/file', 'objectstore'] as $folder) {
                    $this->log('check file with key : ' . $key);
                    if ($objectResult = $this->getObject("$folder/$key")) {
                        DB::query('insert ignore into ' . RepoItemFile::config()->get('table_name') . ' (ID) values (' . $file->ID . ')');

                        SQLUpdate::create(RepoItemFile::config()->get('table_name'))
                            ->assign('S3Key', "$folder/$key")
                            ->assign('ETag', $objectResult->get('ETag'))
                            ->assign('ObjectStoreCheckedAt', date('Y-m-d H:i:s'))
                            ->setWhere(["ID = ?" => $file->ID])
                            ->execute();

                        $this->log($file->Uuid . ": Updated file by key");

                        $found = true;
                        break;
                    }
                }

                if ($found === false) {
                    $this->log($file->Uuid . ": Key not found");
                    DB::query('insert ignore into ' . RepoItemFile::config()->get('table_name') . ' (ID) values (' . $file->ID . ')');
                    SQLUpdate::create(RepoItemFile::config()->get('table_name'))
                        ->assign('ObjectStoreCheckedAt', date('Y-m-d H:i:s'))
                        ->setWhere(["ID = ?" => $file->ID])
                        ->execute();
                }
            } elseif (!empty($file->S3Key)) {
                if ($objectResult = $this->getObject($file->S3Key)) {
                    DB::query('insert ignore into ' . RepoItemFile::config()->get('table_name') . ' (ID) values (' . $file->ID . ')');
                    SQLUpdate::create(RepoItemFile::config()->get('table_name'))
                        ->assign('ETag', $objectResult->get('ETag'))
                        ->assign('ObjectStoreCheckedAt', date('Y-m-d H:i:s'))
                        ->setWhere(["ID = ?" => $file->ID])
                        ->execute();
                    $this->log($file->Uuid . ": Updated file by S3key");
                } else {
                    $this->log($file->Uuid . ": S3key not found");
                    DB::query('insert ignore into ' . RepoItemFile::config()->get('table_name') . ' (ID) values (' . $file->ID . ')');
                    SQLUpdate::create(RepoItemFile::config()->get('table_name'))
                        ->assign('ObjectStoreCheckedAt', date('Y-m-d H:i:s'))
                        ->setWhere(["ID = ?" => $file->ID])
                        ->execute();
                }
            } else {
                $this->log($file->Uuid . ": Invalid file");
            }
        }

        $this->runChunked($offset + 1000);
    }

    private function getObject(string $key): ?Result {
        try {
            return $this->s3Client->headObject([
                'Bucket' => Environment::getEnv("AWS_BUCKET_NAME"),
                'Key' => $key
            ]);
        } catch (\Exception $exception) {
            return null;
        }
    }

    private function log(string $message) {
        if (Director::is_cli()) {
            echo $message . PHP_EOL;
        } else {
            echo nl2br($message . PHP_EOL);
        }
    }
}