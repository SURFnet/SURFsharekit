<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemSummary;

class GenerateRepoItemSummariesTask extends BuildTask {
    protected $title = 'Generate repoitem summaries';
    protected $description = 'retrieves all repoitems and updates/creates summaries for them';

    protected $enabled = true;
    private $count = 5000;
    private $offset = 0;

    function run($request) {
        set_time_limit(0);
        Security::setCurrentUser(Member::get()->filter(['Email' => Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME')])->first());
        $repoItemCount = RepoItem::get()->count();
        while($this->offset < $repoItemCount) {
            Logger::debugLog("GenerateRepoItemSummariesTask $this->offset -> " . ($this->offset + $this->count));
            foreach (RepoItem::get()->limit($this->count, $this->offset) as $repoItem) {
                RepoItemSummary::updateFor($repoItem);
            }
            $this->offset = $this->offset + $this->count;
        }
    }
}