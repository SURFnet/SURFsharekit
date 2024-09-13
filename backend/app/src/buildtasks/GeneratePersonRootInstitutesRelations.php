<?php

namespace SurfSharekit\Tasks;

use Exception;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;

class GeneratePersonRootInstitutesRelations extends BuildTask {

    protected $title = 'Generate RootInstitute relations for all persons';
    protected $description = 'Sets the RootInstitutes relation of all Person objects. This is a new relation as of sprint 4 2022.';

    public function run($request) {
        set_time_limit(0);
        $groups = Group::get();

        /** @var Group $group */
        foreach ($groups as $group) {
            if($group->Members()->count()) {
                echo("Check group " . $group->getTitle() . "\n");
                $institute = Institute::get_by_id($group->InstituteID);
                if ($institute) {
                    $rootInstitute = $institute->getRootInstitute();
                    if ($rootInstitute && $rootInstitute->ID > 0) {
                        $members = $group->Members();
                        foreach ($members as $member) {
                            /** @var Person $member */
                            echo("Update person " . $member->ID . ' with classname ' . $member->getClassName() . " for rootInstitute " . $rootInstitute->ID . "\n");
                            if($member->getClassName() == 'SurfSharekit\Models\Person'){
                                $memberRootInstituteIDs = $member->RootInstitutes()->getIDList();
                                if (!in_array($rootInstitute->ID, $memberRootInstituteIDs)) {
                                    $member->RootInstitutes()->add($rootInstitute);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}