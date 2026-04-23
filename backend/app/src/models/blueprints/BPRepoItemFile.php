<?php

namespace SilverStripe\models\blueprints;

use Aws\S3\S3Client;
use Exception;
use Ramsey\Uuid\Uuid;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;

class BPRepoItemFile extends Blueprint
{
    private static $table_name = 'SurfSharekit_BPRepoItemFile';
    private static $singular_name = 'RepoItemFile blueprint';
    private static $plural_name = 'RepoItemFile blueprints';

    /**
     * @throws ValidationException
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $data = $this->getValidateData();
        $s3Key = $data['s3Key'];

        $s3Client = Injector::inst()->create('Aws\\S3\\S3Client');
        $s3ClientBlueprint = Injector::inst()->create(S3Client::class, [
            'endpoint' => Environment::getEnv('AWS_ENDPOINT'),
            'version' => 'latest',
            'use_path_style_endpoint' => true,
            'region' => Environment::getEnv('AWS_REGION'),
            'credentials' => [
                'key' => Environment::getEnv('AWS_BLUEPRINT_ACCESS_KEY_ID'),
                'secret' => Environment::getEnv('AWS_BLUEPRINT_SECRET_ACCESS_KEY'),
            ],
        ]);
        $blueprintBucket = Environment::getEnv("AWS_BLUEPRINT_BUCKET_NAME");
        $currentBucket   = Environment::getEnv("AWS_BUCKET_NAME");

        $currentFile = $this->checkFileExists($s3Client, $currentBucket, $s3Key);
        $blueprintFile = $this->checkFileExists($s3ClientBlueprint, $blueprintBucket, $s3Key);

        if (!$currentFile['exists'] && !$blueprintFile['exists']) {
            throw new ValidationException("There's no valid file", 400);
        }

        if ($currentFile['exists'] && !$blueprintFile['exists']) {
            $fileBody = $this->getFileBody($s3Client, $currentBucket, $s3Key);
            $putResult = $s3ClientBlueprint->putObject([
                'Bucket' => $blueprintBucket,
                'Key'    => $s3Key,
                'Body'   => $fileBody,
                'ACL'    => 'private'
            ]);
            $blueprintFile['eTag'] = $putResult['ETag'] ?? $blueprintFile['eTag'];
        } elseif (!$currentFile['exists'] && $blueprintFile['exists']) {
            $fileBody = $this->getFileBody($s3ClientBlueprint, $blueprintBucket, $s3Key);
            $s3Client->putObject([
                'Bucket' => $currentBucket,
                'Key'    => $s3Key,
                'Body'   => $fileBody,
                'ACL'    => 'private'
            ]);
        }

        $link = $s3ClientBlueprint->getObjectUrl($blueprintBucket, $s3Key);

        $newData = [
            'uuid'                 => $data['uuid'] ?? Uuid::uuid4()->toString(),
            'link'                 => $link,
            'eTag'                 => $blueprintFile['eTag'] ?? '',
            'objectStoreCheckedAt' => date('Y-m-d H:i:s'),
            's3Key'                => $s3Key
        ];

        $json['data'] = $newData;
        $this->JSON = json_encode($json, JSON_PRETTY_PRINT);
    }

    /**
     * Validate the JSON content and return the decoded data array.
     *
     * This method checks that the JSON property is not empty, that it can be decoded properly, and that
     * it contains a valid 'data' section with a non-empty 's3Key'.
     *
     * @return array The validated data array from the JSON property.
     * @throws ValidationException If any of the required JSON validations fail.
     */
    private function getValidateData(): array
    {
        if (empty($this->JSON)) {
            throw new ValidationException("JSON cannot be empty", 400);
        }

        $json = json_decode($this->JSON, true);
        if (!$json || !isset($json['data'])) {
            throw new ValidationException("Data cannot be empty", 400);
        }

        $data = $json['data'];
        if (empty($data['s3Key'])) {
            throw new ValidationException("S3 key cannot be empty", 400);
        }

        return $data;
    }

    /**
     * Checks if the file's size is under the given limit and more than 0 bytes.
     *
     * @param array $headResult Result from S3 headObject call.
     * @return bool True if file size is under the limit, false otherwise.
     */
    private function isFileSizeUnderLimitAndMoreThan0Bytes(int $headResultBytes): bool
    {
        $limit = 2 * 1024 * 1024 * 1024; // 2GB
        return isset($headResultBytes) && $headResultBytes < $limit && $headResultBytes > 0;
    }



    /**
     * Check if a file exists in the specified S3 bucket.
     *
     * @param object $s3Client An instance of the AWS S3Client used for S3 operations.
     * @param string $bucket The name of the S3 bucket where the file is stored.
     * @param string $s3Key The key (path) of the file within the S3 bucket.
     * @return array An associative array with the following keys:
     *               - 'exists' (bool): True if the file exists and meets the size criteria; false otherwise.
     *               - 'eTag' (string): The eTag of the file if it exists, or an empty string if it does not.
     */
    private function checkFileExists($s3Client, string $bucket, string $s3Key): array
    {
        $result = ['exists' => false, 'eTag' => ''];
        try {
            $headResult = $s3Client->headObject([
                'Bucket' => $bucket,
                'Key'    => $s3Key
            ]);
            if ($this->isFileSizeUnderLimitAndMoreThan0Bytes($headResult['ContentLength'])) {
                $result['exists'] = true;
                $result['eTag'] = $headResult['ETag'] ?? '';
            }
        } catch (Exception $e) {
            // File does not exist or error occurred;
        }
        return $result;
    }

    /**
     * Retrieve the file body from the specified S3 bucket.
     *
     * @param object $s3Client An instance of the AWS S3Client used for S3 operations.
     * @param string $bucket The name of the S3 bucket from which the file is retrieved.
     * @param string $s3Key The key (path) of the file within the S3 bucket.
     * @return mixed The file body stream as returned by the S3 getObject method.
     */
    private function getFileBody($s3Client, string $bucket, string $s3Key)
    {
        $getResult = $s3Client->getObject([
            'Bucket' => $bucket,
            'Key'    => $s3Key,
            'ResponseContentDisposition' => 'attachment; filename="' . basename($s3Key) . '"'
        ]);
        $fileBody = $getResult['Body'];
        $fileBody->rewind();
        return $fileBody;
    }

}