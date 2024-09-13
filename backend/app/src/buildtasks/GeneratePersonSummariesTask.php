<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonSummary;

class GeneratePersonSummariesTask extends BuildTask {
    protected $title = 'Generate person summaries';
    protected $description = 'retrieves all persons and updates/creates summaries for them';

    protected $enabled = true;
    private $count = 5000;
    private $offset = 0;

    function run($request) {
        set_time_limit(0);
        Security::setCurrentUser(Member::get()->filter(['Email' => Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME')])->first());
        $personItemCount = Person::get()->count();
        while($this->offset < $personItemCount) {
            Logger::debugLog("GeneratePersonSummariesTask $this->offset -> " . ($this->offset + $this->count));
            foreach (Person::get()->limit($this->count, $this->offset) as $person) {
                PersonSummary::updateFor($person);
            }
            $this->offset = $this->offset + $this->count;
        }
    }
}