<?php

namespace SilverStripe\Services\FileUpload;

abstract class FileUploadStrategy {

    private static int $max_file_size = 0;
    private static array $allowed_extensions = [];

    public function __construct() {
        self::$max_file_size = $this->setMaxFileSize();
        self::$allowed_extensions = $this->setAllowedMimeTypes();
    }

    abstract public function storeFile($file, $existingFile = null);
    abstract public function setAllowedMimeTypes(): array;
    abstract public function setMaxFileSize(): int;

    public static function getMaxFileSize(): int {
        return self::$max_file_size;
    }

    public static function getAllowedExtensions(): array {
        return self::$allowed_extensions;
    }
}