<?php

namespace SilverStripe\Services\RepoItem;

use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;

interface IRepoItemService {

    public function addMetaData(RepoItem $repoItem, array $metaData = [], ?string $rootInstituteUuid = null): void;
    public function createRepoItem(string $ownerUuid, string $instituteUuid, string $repoItemType): RepoItem;
    public function findOrCreateRepoItemMetaField(RepoItem $repoItem, MetaField $metaField): RepoItemMetaField;
    public function createRepoItemMetaFieldValue(RepoItemMetaField $repoItemMetaField): RepoItemMetaFieldValue;

    public function findByUuid(string $repoItemUuid): ?RepoItem;

    public function changeRepoItemStatus(RepoItem $repoItem, string $status): void;
}