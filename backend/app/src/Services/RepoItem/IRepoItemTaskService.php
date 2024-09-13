<?php

namespace SilverStripe\Services\RepoItem;

use SurfSharekit\Models\RepoItem;

interface IRepoItemTaskService {

    public function createFillTasksForRepoItem(RepoItem $repoItem): void;
}