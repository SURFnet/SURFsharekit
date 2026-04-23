<?php

namespace SilverStripe\EnvironmentExport\DataObjects;

use SilverStripe\Assets\File;
use SilverStripe\Core\Environment;

class EnvironmentExportFile extends File {
    private static $table_name = 'SurfSharekit_EnvironmentExportFile';

    private static $allowed_extensions = [
        'json'
    ];

    private static $belongs_to = [
        'EnvironmentImportRequest' => EnvironmentImportRequest::class
    ];

    public function getStreamURL() {
        return Environment::getEnv('SS_BASE_URL') . '/api/v1/files/environment-exports/' . $this->Uuid;
    }

    public function canView($member = null) {
        return true;
    }
}