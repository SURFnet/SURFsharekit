<?php

namespace SurfSharekit\Models;

use SilverStripe\Assets\File;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Security;

class ReportFile extends File {
    private static $table_name = 'SurfSharekit_ReportFile';

    private static $has_one = [
        'Person' => Person::class
    ];

    protected function onBeforeWrite() {
        if (!$this->isInDB() && $this->PersonID == 0) {
            $this->PersonID = Security::getCurrentUser()->ID;
        }
        parent::onBeforeWrite();
    }

    public function getStreamURL() {
        return Environment::getEnv('SS_BASE_URL') . '/api/v1/files/reports/' . $this->Uuid;
    }
    
    public function canView($member = null) {
        return true;
    }
}
