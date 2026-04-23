<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

class FillOnboardingDate extends BuildTask {

    /**
     * @inheritDoc
     */
    public function run($request) {
        DB::get_conn()->query("UPDATE SurfSharekit_Person SET OnboardingDate = NOW() WHERE OnboardingDate is NULL AND HasFinishedOnboarding = 1");
    }
}