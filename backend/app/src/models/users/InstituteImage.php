<?php

namespace SurfSharekit\Models;

use SilverStripe\Assets\Image;
use SilverStripe\Core\Environment;

class InstituteImage extends Image {

    private static $table_name = 'SurfSharekit_InstituteImage';

    public function getLogo(){
        if($this->exists()){
            $resizedImage = $this->ScaleWidth(187);
            return $resizedImage->getAbsoluteURL();
        }
        return null;
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
    }

    public function getStreamURL() {
        return Environment::getEnv('SS_BASE_URL') . '/api/v1/files/instituteImages/' . $this->Uuid;
    }

    public function canEdit($member = null) {
        if ($member == null) {
            return false;
        }
        $instituteUsingImage = Institute::get()->filter('InstituteImageID', $this->ID)->first();
        if (!$instituteUsingImage) {
            return true;
        }
        return parent::canEdit($member) || $instituteUsingImage->ID == 0 || $instituteUsingImage->canEdit($member);
    }

    public function canView($member = null) {
        return true;
    }
}
