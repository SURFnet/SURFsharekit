<?php

namespace SilverStripe\Services;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;

class RepoItemFileService implements IRepoItemFileService {
    use Injectable;
    use Configurable;

    public function getByRepoItemRepoItemFile(string $repoItemRepoItemFileUuid): ?RepoItemFile {
        if (!$repoItemRepoItemFileUuid) {
            return null;
        }

        /** @var RepoItem|null $repoItemRepoItemFile */
        $repoItemRepoItemFile = RepoItem::get()->find("Uuid", $repoItemRepoItemFileUuid);

        if (!$repoItemRepoItemFile) {
            return null;
        }

        $repoItemMetaFieldValue = $repoItemRepoItemFile->getAllRepoItemMetaFieldValues()->filter(["RepoItemFileUuid:not" => null])->first();
        if (!$repoItemMetaFieldValue) {
            return null;
        }

        /** @var RepoItemFile|null $repoItemFile */
        $repoItemFile = $repoItemMetaFieldValue->RepoItemFile();
        return $repoItemFile;
    }

    public function getRepoItemRepoItemFile(RepoItemFile $repoItemFile): ?RepoItem {
        return RepoItem::get()
            ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID')
            ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
            ->where(["SurfSharekit_RepoItemMetaFieldValue.IsRemoved" => 0, "SurfSharekit_RepoItemMetaFieldValue.RepoItemFileID" => $repoItemFile->ID])->first();
    }

    public function getRepoItem(RepoItemFile $repoItemFile): ?RepoItem {
        $repoItemsConnectedToThisFile = RepoItem::get()
            ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID')
            ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
            ->where(["SurfSharekit_RepoItemMetaFieldValue.IsRemoved" => 0, "SurfSharekit_RepoItemMetaFieldValue.RepoItemFileID" => $repoItemFile->ID])->first();
        if ($repoItemsConnectedToThisFile && $repoItemsConnectedToThisFile->exists()) {
            $topRepoItem = RepoItem::get()
                ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID')
                ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
                ->where(["SurfSharekit_RepoItemMetaFieldValue.IsRemoved" => 0, "SurfSharekit_RepoItemMetaFieldValue.RepoItemID" => $repoItemsConnectedToThisFile->ID])->first();

            if ($topRepoItem && $topRepoItem->exists()) {
                return $topRepoItem;
            }
        }
        return null;
    }
}