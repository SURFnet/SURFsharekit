<?php

namespace SurfSharekit\buildtasks;

use Aws\Api\DateTimeResult;
use Aws\Result;
use Aws\S3\S3Client;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SurfSharekit\Models\RepoItemFile;

class DeleteDanglingS3Objects extends BuildTask
{
    protected $title = "Delete dangling S3 Objects";
    protected $description = "Deletes dangling S3 Objects (files) from S3 ObjectStore that do not exist in Database";

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

        $this->handleNextObjects();
    }

    private function handleNextObjects(string $nextContinuationToken = null) {
        $result = $this->getObjects($nextContinuationToken);

        $perPage = $result['MaxKeys'];
        $count = $result['KeyCount'];
        $objects = $this->filterObjects($result['Contents']);
        $nextContinuationToken = $result['NextContinuationToken'] ?? null;

        $keys = $this->filterKeys(array_map(fn($object) => $object['Key'], $objects));

        $missingKeys = $this->getMissingKeys($keys);

        $this->deleteS3Objects($missingKeys);

        // continue pagination
        if ($count >= $perPage) {
            $this->handleNextObjects($nextContinuationToken);
        }
    }

    private function filterObjects(array $objects): array {
        $checkDate = (new \DateTime())->modify('-1 month');
        $filterObjects = [];

        foreach ($objects as $object) {
            /** @var DateTimeResult $lastModified */
            $lastModified = $object['LastModified'];

            if ($lastModified < $checkDate) {
                $filterObjects[] = $object;
            }
        }

        return $filterObjects;
    }

    private function filterKeys(array $keys): array {
        $filteredKeys = [];

        foreach ($keys as $key) {
            // check if key is build like: objectstore/<uuid4>/*
            if (preg_match('/objectstore\/[a-f0-9]{8}-?[a-f0-9]{4}-?4[a-f0-9]{3}-?[89ab][a-f0-9]{3}-?[a-f0-9]{12}\/.*/', $key)) {
                $filteredKeys[] = $key;
            }
        }

        return $filteredKeys;
    }

    /**
     * Returns keys that are not found in RepoItemFile table
     * @return array
     */
    private function getMissingKeys(array $keys): array {
        $foundKeys = RepoItemFile::get()
            ->filter('S3Key', $keys)
            ->column('S3Key');

        return array_diff($keys, $foundKeys);
    }

    private function deleteS3Objects(array $keys) {
        foreach ($keys as $key) {
            $this->execute(function () use ($key) {
                $this->deleteS3Object($key);
            }, "Deleting S3 Object: $key");
        }
    }

    private function deleteS3Object(string $key): Result {
        return $this->s3Client->deleteObject([
            'Bucket' => Environment::getEnv('AWS_BUCKET_NAME'),
            'Key' => $key
        ]);
    }

    private function getObjects(string $nextContinuationToken = null): array {
        $result =  $this->s3Client->listObjectsV2([
            'Bucket' => Environment::getEnv("AWS_BUCKET_NAME"),
            'Prefix' => 'objectstore/',
            'MaxKeys' => 100,
            'ContinuationToken' => $nextContinuationToken
        ]);

        return $result->toArray();
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