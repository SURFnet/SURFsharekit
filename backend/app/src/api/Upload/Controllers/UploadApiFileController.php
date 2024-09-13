<?php

namespace SurfSharekit\Api\Upload\Controllers;

use SilverStripe\api\ResponseHelper;
use SilverStripe\api\Upload\Data\FileUploadResponse;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Services\FileUpload\FileUploadService;
use SilverStripe\Services\FileUpload\UploadApiFileUploadStrategy;
use SilverStripe\Services\RepoItemFileService;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Models\RepoItemFile;

class UploadApiFileController extends UploadApiAuthController {
    private static $url_handlers = [
        'POST $Uuid!' => 'replaceFile',
        'POST /' => 'uploadFile',
    ];

    private static $allowed_actions = [
        'uploadFile',
        'replaceFile'
    ];

    public function uploadFile(HTTPRequest $request) {
        // Set configuration for the duration of this script execution
        ini_set('max_input_time', 300);
        ini_set('max_execution_time', 300);

        $fileUploadService = FileUploadService::create();
        $uploadedFile = array_values($_FILES)[0] ?? null;
        if (!$uploadedFile) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_005);
        }
        $file = $fileUploadService->processFileUpload($uploadedFile, UploadApiFileUploadStrategy::create());
        $responseBody = new FileUploadResponse($file->Uuid);
        return ResponseHelper::responseCreated($responseBody->toJson());

    }

    public function replaceFile(HTTPRequest $request) {
        ini_set('max_input_time', 300);
        ini_set('max_execution_time', 300);

        // file id uit url halen
        $fileUuid = $request->param("Uuid");

        $fileUploadService = FileUploadService::create();
        $repoItemFileService = RepoItemFileService::create();

        /** @var RepoItemFile|null $existingRepoItemFile */
        $existingRepoItemFile = RepoItemFile::get()->find("Uuid", $fileUuid);

        if (!$existingRepoItemFile) {
            throw new NotFoundException(ApiErrorConstant::UA_NF_001);
        }

        // Replacing a file can only be done if the RepoItemFile is linked to a RepoItem that has the 'Draft' status
        $parentRepoItem = $repoItemFileService->getRepoItem($existingRepoItemFile);
        if ($parentRepoItem && $parentRepoItem->Status != 'Draft') {
            throw new BadRequestException(ApiErrorConstant::UA_BR_006);
        }

        // Upload new file
        $uploadedFile = array_values($_FILES)[0] ?? null;
        if (!$uploadedFile) {
            throw new BadRequestException(ApiErrorConstant::UA_BR_005);
        }

        $fileUploadService->processFileUpload($uploadedFile, UploadApiFileUploadStrategy::create(), $existingRepoItemFile);
        return ResponseHelper::responseCreated();
    }
}