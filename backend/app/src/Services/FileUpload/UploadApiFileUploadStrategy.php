<?php

namespace SilverStripe\Services\FileUpload;

use Aws\Result;
use Aws\S3\S3Client;
use Exception;
use Ramsey\Uuid\Uuid;
use SilverStripe\api\Exceptions\InternalServerErrorException;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\helper\PathHelper;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Models\Helper\MimetypeHelper;
use SurfSharekit\Models\RepoItemFile;

class UploadApiFileUploadStrategy extends FileUploadStrategy {
    use Injectable;
    use Configurable;

    public function storeFile($file, $existingFile = null) {
        try {
            /** @var S3Client $s3Client */
            $s3Client = Injector::inst()->create('Aws\\S3\\S3Client');
            $bucket = Environment::getEnv("AWS_BUCKET_NAME");

            $identifier = Uuid::uuid4()->toString();
            $repoItemFile = $existingFile ?: RepoItemFile::create(["Uuid" => $identifier]);

            $pathOfFileToDelete = $existingFile ? $this->getPathOfFileToDelete($existingFile) : null;
            $fileName = FileNameFilter::singleton()->filter($file["name"]);
            $s3Key = "objectstore/$identifier/$fileName";

            $s3Result = $s3Client->putObject([
                "Bucket" => $bucket,
                "Key" => $s3Key,
                "SourceFile" => $file['tmp_name']
            ]);

            $repoItemFile->Title = $fileName;
            $repoItemFile->Name = $fileName;
            $repoItemFile->Link = $s3Result['ObjectURL'];
            $repoItemFile->S3Key = $s3Key;
            $repoItemFile->ETag = $s3Result['ETag'];
            $repoItemFile->write();

            if ($existingFile && !$pathOfFileToDelete) {
                throw new NotFoundException(ApiErrorConstant::UA_NF_001);
            }

            if ($pathOfFileToDelete) {
                $this->deleteFile($existingFile);
            }

            return $repoItemFile;
        } catch(Exception $e) {
            throw new InternalServerErrorException(ApiErrorConstant::UA_ISE_001);
        }
    }

    private function deleteFile(string $s3Path): bool {
        $retryCount = 0;
        $success = false;
        while ($retryCount !== 3 && $success === false) {
            try {
                /** @var S3Client $s3Client */
                $s3Client = Injector::inst()->create('Aws\\S3\\S3Client');
                $s3Client->deleteMatchingObjects(Environment::getEnv("AWS_BUCKET_NAME"), $s3Path);
                $success = true;
            } catch (Exception $e) {
                $retryCount++;
            }
        }
        return $success;
    }

    private function getPathOfFileToDelete(RepoItemFile $repoItemFile): ?string {
        if ($repoItemFile->S3Key) {
            return PathHelper::pathPop($repoItemFile->S3Key) . "/";
        } else if ($repoItemFile->FileFilename) {
            return PathHelper::pathPop("protected/$repoItemFile->FileFilename") . "/";
        }

        return null;
    }

    public function setAllowedMimeTypes(): array {
        return MimetypeHelper::getWhitelistedExtensions();
    }

    public function setMaxFileSize(): int {
        $KiB = 1024;
        $MiB = 1024 * $KiB;
        $GiB = 1024 * $MiB;
        return 10 * $GiB;
    }
}