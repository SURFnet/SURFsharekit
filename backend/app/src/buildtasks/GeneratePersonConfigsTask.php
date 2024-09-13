<?php

namespace SurfSharekit\Tasks;

use Exception;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonConfig;

class GeneratePersonConfigsTask extends BuildTask {
    protected $title = 'Generate personConfigs';
    protected $description = 'Generates PersonConfig objects for Persons (max 5000 per run) who do not have a PersonConfig yet';

    protected $enabled = true;
    private $count = 50000;

    private $successCount = 0;
    private $failedCount = 0;

    function run($request) {
        set_time_limit(0);

        $personsWithoutConfig = Person::get()->filter(['PersonConfigID' => 0])->limit($this->count);
        foreach ($personsWithoutConfig as $person) {
            try {
                $newConfig = new PersonConfig();
                $newConfig->write();
                $person->PersonConfigID = $newConfig->ID;
                $person->write();
                $this->successCount++;
                echo ("Successfully generated a PersonConfig (ID $newConfig->ID) for person (ID $person->ID)");
                echo ("<br>");
            } catch (Exception $e) {
                $this->failedCount++;
                echo ("Failed generating a PersonConfig (ID $newConfig->ID) for person (ID $person->ID). Reason: " .  $e->getMessage());
                echo ("<br>");
            }
        }
        echo ("<br>");
        echo ("Finished generating");
        echo ("<br>"); echo ("<hr>"); echo ("<br>");
        echo ("Total: " . ($this->failedCount + $this->successCount));echo ("<br>");
        echo ("Success: $this->successCount");echo ("<br>");
        echo ("Failed: $this->failedCount");echo ("<br>");
    }
}