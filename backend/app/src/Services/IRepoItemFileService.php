<?php

namespace SilverStripe\Services;

use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;

interface IRepoItemFileService {
    public function getByRepoItemRepoItemFile(string $repoItemRepoItemFileUuid): ?RepoItemFile;
    public function getRepoItemRepoItemFile(RepoItemFile $repoItemFile): ?RepoItem;
    public function getRepoItem(RepoItemFile $repoItemFile): ?RepoItem;
}