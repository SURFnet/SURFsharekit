<?php

namespace SilverStripe\models\tasks;

use SilverStripe\Dev\BuildTask;
use SurfSharekit\helper\InstituteGroupManager;
use SurfSharekit\Models\Institute;

class CreateUploadApiDefaultGroupsTask extends BuildTask {

    public function run($request) {
        set_time_limit(0);

        $rootInstitutes = Institute::get()->filter(["InstituteID" => 0]);

        foreach ($rootInstitutes as $rootInstitute) {
            InstituteGroupManager::createDefaultGroups($rootInstitute);
        }
    }
}