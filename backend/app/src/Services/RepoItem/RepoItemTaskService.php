<?php

namespace SilverStripe\Services\RepoItem;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SurfSharekit\Models\RepoItem;

class RepoItemTaskService implements IRepoItemTaskService {
    use Injectable;
    use Configurable;

    public function createFillTasksForRepoItem(RepoItem $repoItem): void {
        $repoItem->NeedsToBeFinished = true;
        $repoItem->shouldCreateFillTask = true;
        $repoItem->write(false, false, true);
    }
}