<?php

namespace SilverStripe\Services\FileUpload;

use Aws\S3\S3Client;
use Exception;
use SilverStripe\api\Exceptions\UnsupportedMediaTypeException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\helper\PathHelper;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Api\Exceptions\PayloadTooLargeException;
use SurfSharekit\Models\RepoItemFile;

class FileUploadService implements IFileUploadService {
    use Injectable;
    use Configurable;

    function processFileUpload($file, FileUploadStrategy $strategy, $existingFile = null) {
        $uploadedFileExtension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $allowedExtensions = $strategy->setAllowedMimeTypes();
        $maxFileSize = $strategy->getMaxFileSize();

        if(!in_array($uploadedFileExtension, $allowedExtensions)){
            throw new UnsupportedMediaTypeException(ApiErrorConstant::GA_UMT_001);
        }

        $bytesOfFile = $file["size"];
        if ($bytesOfFile > $maxFileSize) {
            throw new PayloadTooLargeException(ApiErrorConstant::GA_PTL_001);
        }

        return $strategy->storeFile($file, $existingFile);
    }
}

