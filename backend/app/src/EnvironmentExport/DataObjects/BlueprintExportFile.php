<?php

namespace SilverStripe\EnvironmentExport\DataObjects;

use SilverStripe\Assets\File;
use SilverStripe\Core\Environment;

class BlueprintExportFile extends File {
    private static $table_name = 'SurfSharekit_BlueprintExportFile';

    private static $allowed_extensions = [
        'json'
    ];

    private static $belongs_to = [
        'BlueprintImportRequest' => BlueprintImportRequest::class
    ];

    public function getStreamURL() {
        return Environment::getEnv('SS_BASE_URL') . '/api/v1/files/blueprint-exports/' . $this->Uuid;
    }

    public function canView($member = null) {
        return true;
    }
}