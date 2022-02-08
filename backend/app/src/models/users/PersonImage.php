<?php

namespace SurfSharekit\Models;

use SilverStripe\Assets\Image;
use SilverStripe\Core\Environment;
use SilverStripe\Versioned\Versioned;

class PersonImage extends Image {
    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $table_name = 'SurfSharekit_PersonImage';

    public function getStreamURL() {
        return Environment::getEnv('SS_BASE_URL') . '/api/v1/files/personImages/' . $this->Uuid;
    }

    public function canView($member = null) {
        return true;
    }

    public function canEdit($member = null) {
        if ($member == null) {
            return false;
        }
        $userOfPersonImage = Person::get()->filter('PersonImageID', $this->ID)->first();
        if (!$userOfPersonImage) {
            return true;
        }
        return parent::canEdit($member) || $userOfPersonImage->ID == 0 || $userOfPersonImage->canEdit($member);
    }
}
