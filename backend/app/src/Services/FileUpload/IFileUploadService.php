<?php

namespace SilverStripe\Services\FileUpload;

interface IFileUploadService {

    public function processFileUpload($file, FileUploadStrategy $strategy);
}