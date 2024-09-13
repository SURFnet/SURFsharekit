<?php

namespace SurfSharekit\Tasks;

use Exception;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionRole;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonSummary;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemSummary;
use SurfSharekit\Models\SearchObject;
use UuidExtension;

class UpdateInstituteLabelsTask extends BuildTask {

    public $description = "This task generates searchobjects and summaries for all Repoitems and persons when a name has been changed of an institute group.";

    public function run($request) {
        // Set time limit to 0 to prevent time out errors.
        set_time_limit(0);

        $changedInstitutes = Institute::get()->filter(['UpdateInstituteLabels' => true]);

        foreach ($changedInstitutes as $changedInstitute) {
            try {
                /**
                 * Logic beneath is there to make sure searchobjects and summaries are being generated
                 * based on a label change in an institute group.
                 */

                // Get all RepoItems related to the Institute
                $repoItems = RepoItem::get()->filter(['InstituteID' => $changedInstitute->ID]);

                foreach ($repoItems as $repoItem) {
                    // Generate searchobject on institutegroup label change
                    SearchObject::updateForRepoItem($repoItem);

                    // Generate summary for repoItem on institutegroup label change
                    RepoItemSummary::updateFor($repoItem);
                }

                $changedInstituteGroups = Group::get()->filter(["InstituteID" => $changedInstitute->ID]);
                foreach ($changedInstituteGroups as $changedInstituteGroup) {
                    $members = $changedInstituteGroup->Members();
                    foreach ($members as $member) {
                        // Check if the member is an instance of Person
                        if ($member instanceof Person) {
                            // Generate searchobject for person on institutegroup label change
                            SearchObject::updateForPerson($member);

                            // Generate person summary on institutegroup label change
                            PersonSummary::updateFor($member);
                        }
                    }
                }

                $changedInstitute->UpdateInstituteLabels = false;
                $changedInstitute->write();
            } catch (Exception $e) {

                continue;
            }
        }
    }
}