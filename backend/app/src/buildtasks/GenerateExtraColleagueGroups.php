<?php

namespace SilverStripe\buildtasks;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Group;
use SurfSharekit\helper\InstituteGroupManager;
use SurfSharekit\Models\Institute;

class GenerateExtraColleagueGroups extends BuildTask {
    protected $title = "Generate Extra medewerker Groups";
    protected $description = "Generates extra medewerker groups for all institutes for department, lectorate and discipline. Use ?limit=[number] to limit the number of institutes processed.";

    protected $defaultRoleID = 7; // RoleID for medewerker 
    
    public function run($request) {
        $limit = 0;
        if ($request->getVar('limit')) {
            $limit = $request->getVar('limit');
        }
        set_time_limit(0); // disable time limit

        // Get all institutes that are of type department, lectorate or discipline
        // and have no group with the default role being the medewerker role.
        $institutes = Institute::get()->filter([
            'Level' => ["department", "lectorate", "discipline"]
        ])->filterByCallback(function ($institute) {
            $groups = $institute->Groups();
            return !$groups->filter(['DefaultRoleID' => $this->defaultRoleID])->exists();
        })->limit($limit);
       $this->print("Generating extra colleague groups for " . $institutes->count() . " institutes");

       /** @var Institute $institute **/
       foreach ($institutes as $institute) {
           InstituteGroupManager::createDefaultGroups($institute);
           $this->print("Adding default role to group for institute: " . $institute->Title . " (" . $institute->ID . ")");
       }
    }

    private function print($value) {
        if (Director::is_cli()) {
            echo $value . PHP_EOL;
        } else {
            echo $value . "<br />";
        }
    }
}