<?php

namespace SilverStripe\processors\Blueprint;

use Aws\S3\S3Client;
use Exception;
use Ramsey\Uuid\Uuid;
use SilverStripe\api\Exceptions\InternalServerErrorException;
use SilverStripe\api\Exceptions\UnsupportedMediaTypeException;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\helper\PathHelper;
use SilverStripe\processors\BlueprintRepoitemFileStrategy;
use SilverStripe\Security\Security;
use SilverStripe\Services\FileUpload\FileUploadStrategy;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;

class BlueprintRepoItemFileConverterProcessor extends BlueprintConverterProcessor
{
    public function getTargetClass(): string
    {
        return RepoItemFile::class;
    }

    /**
     * Convert a blueprint object to a RepoItemFile.
     */
    public function convert($blueprint): ?RepoItemFile
    {
        $json = json_decode($blueprint->JSON, true);
        if (!$json || !isset($json['data'])) return null;

        $data = $json['data'];
        if (empty($data['s3Key'])) return null;

        return $this->convertFileToCurrentBucket($data);
    }

    private function convertFileToCurrentBucket(array $data): ?RepoItemFile
    {
        try {
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

            $s3Key = $data['s3Key'];

            $blueprintBucket = Environment::getEnv("AWS_BLUEPRINT_BUCKET_NAME");
            $currentBucket   = Environment::getEnv("AWS_BUCKET_NAME");

            $getResult = $s3ClientBlueprint->getObject([
                'Bucket' => $blueprintBucket,
                'Key'    => $s3Key,
                'ResponseContentDisposition' => 'attachment; filename="' . basename($s3Key) . '"'
            ]);
            $fileBody = $getResult['Body'];
            $fileBody->rewind();

            $s3currentEnvBucketResponse = $s3Client->putObject([
                'Bucket' => $currentBucket,
                'Key'    => $s3Key,
                'Body'   => $fileBody,
                'ACL'    => 'private'
            ]);

            return $this->getOrCreateNewRepoItemFile($data, $s3currentEnvBucketResponse);
        } catch (Exception $e) {
            return null; // silent error
        }
    }

    /**
     * Creates a new RepoItemFile record.
     */
    private function getOrCreateNewRepoItemFile($data, $currentEnvBucketFileData): RepoItemFile
    {
        $repoItemFile = RepoItemFile::get()->filter(['Uuid' => $data['uuid']])->first();
        if (!$repoItemFile) {
            $repoItemFile = new RepoItemFile();
        }

        $repoItemFile->Uuid = $data['uuid'];
        $repoItemFile->Link = $currentEnvBucketFileData['ObjectURL'];
        $repoItemFile->S3Key = $data['s3Key'];
        $repoItemFile->ETag = $currentEnvBucketFileData['eTag'];
        $repoItemFile->ObjectStoreCheckedAt = $data['objectStoreCheckedAt'];

        $repoItemFile->write();
        $repoItemFile->publishRecursive();
        return $repoItemFile;
    }
}