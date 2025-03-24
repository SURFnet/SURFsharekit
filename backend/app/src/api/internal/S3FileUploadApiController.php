<?php

namespace SurfSharekit\Api;

use Aws\S3\S3Client;
use DataObjectJsonApiBodyEncoder;
use DataObjectJsonApiEncoder;
use Exception;
use Mimey\MimeTypes;
use Ramsey\Uuid\Uuid;
use RepoItemFileJsonApiDescription;
use SilverStripe\Assets\File;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\MimetypeHelper;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;

/**
 * Class S3FileUploadController
 * @package SurfSharekit\Api
 * Used to create a url that can be usd for two minutes to download a file
 */
class S3FileUploadApiController extends LoginProtectedApiController {

    const s3KeyPrefix = "objectstore/";

    private static $url_handlers = [
        'POST startUpload' => 'startUpload',
        'POST closeUpload' => 'closeUpload',
    ];

    private static $allowed_actions = [
        'startUpload',
        'closeUpload'
    ];

    public function startUpload() {
        $request = $this->getRequest();
        $requestBody = json_decode($request->getBody(), true);
        $response = $this->getResponse();

        $response->addHeader("content-type", "application/json");
        $response->setStatusCode(200);

        $result = $this->validateUploadStart($requestBody, $response);
        if (!$result) {
            return $response;
        }

        $mimeType = MimetypeHelper::getMimeType($requestBody['fileName'], null);

        $partCount = $requestBody['partCount'];
        $fileName = $requestBody['fileName'];



        $identifier = Uuid::uuid4();
        $s3Key = $this::s3KeyPrefix . "$identifier/$fileName";

        $urlsUploadId = $this->getPartsAndUploadId($partCount, $s3Key, $mimeType);
        $urlsUploadId['fileName'] = $s3Key;
        $response->setBody(json_encode($urlsUploadId));

        return $response;
    }

    public function closeUpload() {
        $request = $this->getRequest();
        $requestBody = json_decode($request->getBody(), true);
        $response = $this->getResponse();

        $response->addHeader("content-type", "application/json");
        $response->setStatusCode(200);

        $result = $this->validateUploadClose($requestBody, $response);
        if (!$result) {
            return $response;
        }

        $repoItemUuid = $requestBody['repoItemUuid'] ?? null;
        /** @var RepoItemFile|null $existingRepoItemFile */
        $existingRepoItemFile = $this->getExistingRepoItemFile($repoItemUuid);

        // Check if there's a file to replace before overwriting S3key and Link
        $filePathToDelete = $this->getPathOfFileToDelete($existingRepoItemFile);

        try {
            $s3Key = $requestBody['fileName'];
            $uploadId = $requestBody['uploadId'];
            $parts = $requestBody['parts'];

            $result = $this->closeMultiPartUpload($s3Key, $uploadId, $parts);
            $explodedS3Key= explode("/", $s3Key);
            $name = end($explodedS3Key);

            $repoItemFile = $existingRepoItemFile ?: new RepoItemFile();
            $repoItemFile->Title = $name;
            $repoItemFile->Name = $name;
            $repoItemFile->Link = $result['Location'];
            $repoItemFile->S3Key = $result['Key'];
            $repoItemFile->ETag = $result['ETag'];
            $repoItemFile->write();
        } catch (Exception $e) {
            $response->setStatusCode(500);
            Logger::infoLog($e->getMessage());
            return $response;
        }

        // File was successfully uploaded, now delete the file it's supposed to replace
        if ($filePathToDelete) {
            $this->deleteFileToReplace($filePathToDelete);
        }

        $encoder = new DataObjectJsonApiEncoder([RepoItemFile::class => new RepoItemFileJsonApiDescription()]);
        $responseBody = DataObjectJsonApiBodyEncoder::dataObjectToSingleObjectJsonApiBodyArray($repoItemFile, $encoder, (BASE_URL . '/api/v1/files/repoItemFiles'));
        $response->setBody(json_encode($responseBody));
        $response->setStatusCode(200);
        return $response;
    }

    /**
     * @param RepoItemFile|null $repoItemFile
     * @return string|null
     */
    private function getPathOfFileToDelete(?RepoItemFile $repoItemFile): ?string {
        if (!$repoItemFile) {
            return null;
        }

        if ($repoItemFile->S3Key) {
            return $this->pathPop($repoItemFile->S3Key) . "/";
        } else if ($repoItemFile->FileFilename) {
            return $this->pathPop("protected/$repoItemFile->FileFilename") . "/";
        }

        return null;
    }

    /**
     * @param string $filePath
     * @return void
     * Try to delete the file that has been replaced.
     */
    private function deleteFileToReplace(string $filePath) {
        $retryCount = 0;
        $success = false;
        while ($retryCount !== 3 && $success === false) {
            try {
                /** @var S3Client $s3Client */
                $s3Client = Injector::inst()->create('Aws\\S3\\S3Client');
                $s3Client->deleteMatchingObjects(Environment::getEnv("AWS_BUCKET_NAME"), $filePath);
                $success = true;
            } catch (Exception $e) {
                $retryCount++;
            }
        }
    }

    /**
     * @param string|null $path
     * @return string|null
     * Removes the last segment of a path
     */
    private function pathPop(?string $path): ?string {
        if (!$path) {
            return null;
        }

        $arr = explode('/', $path);
        if ($arr) {
            array_pop($arr);
            return implode('/', $arr);
        }

        return null;
    }

    /**
     * @param $s3Key
     * @return string|null
     * Remove the first segment of a path
     */
    private function pathShift($s3Key) {
        $arr = explode('/', $s3Key);
        if ($arr) {
            array_shift($arr);
            return implode('/', $arr);
        }

        return null;
    }

    /**
     * @param string|null $repoItemUuid
     * @return RepoItemFile|null
     */
    public function getExistingRepoItemFile(?string $repoItemUuid): ?RepoItemFile {
        if (!$repoItemUuid) {
            return null;
        }

        /** @var RepoItem|null $repoItemRepoItemFile */
        $repoItemRepoItemFile = RepoItem::get()->find("Uuid", $repoItemUuid);

        if (!$repoItemRepoItemFile) {
            return null;
        }

        $repoItemMetaFieldValue = $repoItemRepoItemFile->getAllRepoItemMetaFieldValues()->filter(["RepoItemFileUuid:not" => null])->first();
        if (!$repoItemMetaFieldValue) {
            return null;
        }

        /** @var RepoItemFile|null $repoItemFile */
        $repoItemFile = $repoItemMetaFieldValue->RepoItemFile();
        return $repoItemFile;
    }

//    /**
//     * @param string $fileName
//     * @return string
//     */
//    public function getMimeType(string $fileName): ?string {
//        $mimes = new MimeTypes();
//        $fileTypeExtension = pathinfo($fileName, PATHINFO_EXTENSION);
//        $mimeType = $mimes->getMimeType($fileTypeExtension);
//
//        if($mimeType == "application/x-gzip") {
//            // getMimeType() returns the wrong mimetype for zip files, so... here it is corrected
//            return "application/zip";
//        }
//
//        return $mimeType;
//    }

    /**
     * @param $partCount
     * @param $key
     * @param $mimeType
     * @return array
     */
    public function getPartsAndUploadId($partCount, $key, $mimeType) {
        /**
         * Setup s3 client
         */
        /** @var S3Client $s3Client */
        $s3Client = Injector::inst()->create('Aws\\S3\\S3Client');


        $bucketKey = [
            'Bucket' => Environment::getEnv("AWS_BUCKET_NAME"),
            'Key' => $key,
            'ContentType' => $mimeType
        ];

        /**
         * Tell S3 we're going to upload a new file
         */
        $multipartUploadResult = $s3Client->createMultipartUpload($bucketKey);
        $uploadId = $multipartUploadResult['UploadId'];
        $bucketKey['UploadId'] = $uploadId;

        /**
         * Generate endpoints with s3 for frontend to upload file to in parts
         */
        $urls = [];
        for ($i = 0; $i < $partCount; $i++) {
            $cmd = $s3Client->getCommand('UploadPart', [
                    'Bucket' => $bucketKey['Bucket'],
                    'Key' => $bucketKey['Key'],
                    'PartNumber' => $i + 1,
                    'UploadId' => $bucketKey['UploadId'],
                    'Body' => '',
                ]
            );

            $request = $s3Client->createPresignedRequest($cmd, '+24 hour');
            $urls[] = [
                'partNumber' => $i + 1,
                'proxyUrl' => (string)$request->getUri(),
                'url' => (string)(Environment::getEnv('SS_BASE_URL') . '/api/v1/uploadPart/uploadPart')
            ];
        }

        return ['uploadId' => $uploadId,
            'parts' => $urls];
    }

    /**
     * @param array|null $requestBody
     * @param HTTPResponse $response
     * @return bool
     */
    private function validateUploadStart(?array $requestBody, HTTPResponse $response): bool {
        // Check if body is not empty
        if (!$requestBody) {
            $response->setBody(json_encode(JsonApiController::missingRequestBodyError()));
            return false;
        }

        // Check if partCount and fileName are set
        if (!isset($requestBody['partCount']) || !isset($requestBody['fileName']) || !isset($requestBody['fileSize'])) {
            $response->setBody(json_encode(
                [
                    JsonApi::TAG_ERRORS => [
                        [
                            JsonApi::TAG_ERROR_TITLE => "Missing partCount, fileName or fileSize",
                            JsonApi::TAG_ERROR_CODE => 'FUAPC_01'
                        ]
                    ]
                ]
            ));
            return false;
        }

        // Check if the extension of the provided file(s) is allowed
        $ext = strtolower(pathinfo($requestBody['fileName'], PATHINFO_EXTENSION));
        $allowedExtensions = MimetypeHelper::getWhitelistedExtensions();

        if (!in_array($ext, $allowedExtensions)) {
            $response->setBody(json_encode(
                [
                    JsonApi::TAG_ERRORS => [[
                        JsonApi::TAG_ERROR_TITLE => 'Missing Content-Type',
                        JsonApi::TAG_ERROR_DETAIL => 'Missing a valid Content-Type header for uploaded binary',
                        JsonApi::TAG_ERROR_CODE => 'UFAC_2'
                    ]]
                ]
            ));
            return false;
        }

        $kb = 1024;
        $mb = $kb * 1024;
        $gb = $mb * 1024;
        if ($requestBody['fileSize'] > (10 * $gb)) {
            $response->setBody(json_encode(
                [
                    JsonApi::TAG_ERRORS => [[
                        JsonApi::TAG_ERROR_TITLE => 'File too large',
                        JsonApi::TAG_ERROR_DETAIL => 'File too large, you can upload files up to 10 GB',
                        JsonApi::TAG_ERROR_CODE => 'FUAPC_03'
                    ]]
                ]
            ));
            return false;
        }

        return true;
    }

    /**
     * @param array|null $requestBody
     * @param HTTPResponse $response
     * @return bool
     */
    private function validateUploadClose(?array $requestBody, HTTPResponse $response): bool {
        if (!$requestBody) {
            $response->setBody(json_encode(JsonApiController::missingRequestBodyError()));
            return false;
        }

        if (!isset($requestBody['fileName']) || !isset($requestBody['uploadId']) || !isset($requestBody['parts'])) {
            $response->setBody(json_encode(
                [
                    JsonApi::TAG_ERRORS => [
                        [
                            JsonApi::TAG_ERROR_TITLE => "Missing fileName, uploadId or parts{partNumber, eTag} array",
                            JsonApi::TAG_ERROR_CODE => 'FUAPC_02'
                        ]
                    ]
                ]
            ));
            return false;
        }

        return true;
    }

    private function closeMultiPartUpload($s3Key, $uploadId, $parts) {
        /**
         * Setup s3 client
         */
        /** @var S3Client $s3Client */
        $s3Client = Injector::inst()->create('Aws\\S3\\S3Client');
        $partsS3 = [];
        foreach ($parts as $jsonPart) {
            $partsS3[] = [
                'PartNumber' => $jsonPart['partNumber'],
                'ETag' => $jsonPart['eTag']
            ];
        }
        $bucketKey = [
            'Bucket' => Environment::getEnv("AWS_BUCKET_NAME"),
            'Key' => $s3Key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => $partsS3
            ]
        ];

        /**
         * Tell S3 we're going to close a file that was uploaded
         */
        $result = $s3Client->completeMultipartUpload($bucketKey);
        return $result;
    }
}