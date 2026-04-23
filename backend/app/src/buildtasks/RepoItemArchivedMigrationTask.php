<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Member;
use SurfSharekit\Extensions\Security;
use SurfSharekit\Models\RepoItem;

class RepoItemArchivedMigrationTask extends BuildTask {

    /**
     * @inheritDoc
     */
    public function run($request) {
        set_time_limit(0);
        Security::setCurrentUser(Member::get()->find("FirstName", "Default Admin"));

        $repoItems = RepoItem::get()->filter(['IsArchived' => 1, 'Status:not' => 'Archived']);
        /** @var RepoItem $repoItem */
        foreach ($repoItems as $repoItem) {
            $repoItem->setStatus("Archived");
            $repoItem->write();
        }
    }
}