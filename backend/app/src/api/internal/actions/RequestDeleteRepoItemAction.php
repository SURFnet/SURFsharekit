<?php

namespace SurfSharekit\Api;

use SilverStripe\Security\Security;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\TaskCreator;
use Zooma\SilverStripe\Models\ApiObject;

class RequestDeleteRepoItemAction extends ApiObject
{
    function execute() {
        if (empty($this->Reason)) {
            throw new \Exception("Reason required");
        }

        /** @var RepoItem $repoItem */
        if (null === $repoItem = RepoItem::get()->find('Uuid', $this->RepoItemID)) {
            throw new \Exception("RepoItem not found");
        }

        // repo item should be published
        if ($repoItem->Status !== "Published" && $repoItem->Status !== "Archived") {
            throw new \Exception("RepoItem is not Published or Archived");
        }

        // Only continue if user has no permission to delete repo item
        if ($repoItem->canDelete(Security::getCurrentUser())) {
            throw new \Exception("User is allowed to delete RepoItem");
        }

        // Check if user is author of repo item
        if ($repoItem->Owner()->Uuid !== Security::getCurrentUser()->Uuid) {
            throw new \Exception("User is not allowed to create delete request");
        }

        $this->doCreateDeleteRequest($repoItem, $this->Reason);
    }

    private function doCreateDeleteRequest(RepoItem $repoItem, string $reason) {
        $repoItem->DeleteReason = $reason;
        $repoItem->ignoreSetStatusCheck = true;
        $repoItem->DeletionHasBeenDeclined = false;
        $repoItem->Status = "Draft";
        $repoItem->IsRemoved = true;
        $repoItem->write();

        unset($repoItem->ignoreSetStatusCheck);

        if ($repoItem->IsRemoved){
            TaskCreator::getInstance()->createRecoverTasks($repoItem);
        }
    }

    public function canCreate($member = null, $context = []) {
        return true;
    }
}