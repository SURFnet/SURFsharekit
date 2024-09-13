<?php
namespace SurfSharekit\Tasks;

use SilverStripe\Dev\BuildTask;
use SurfSharekit\Models\Institute;

class GenerateDefaultGroupsTask extends BuildTask{

    protected $title = 'Generate Default Groups Task';
    protected $description = 'This task (re)generates default groups for all institutes from top to bottom';

    protected $enabled = true;


    function run($request) {
        set_time_limit(0);

        $rootInstitutes = Institute::get()->filter(['InstituteId'=> 0]);
        /** @var Institute $rootInstitute */
        foreach($rootInstitutes as $rootInstitute){
            $this->forceWriteSubInstitutes($rootInstitute);
        }
    }

    private function forceWriteSubInstitutes(Institute $institute){
        $institute->write(false, false, true);
        foreach($institute->Institutes() as $subInstitute){
            $this->forceWriteSubInstitutes($subInstitute);
        }
    }

}