<?php

namespace SurfSharekit\Api;

use SilverStripe\Security\Security;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\TaskCreator;
use Zooma\SilverStripe\Models\ApiObject;

class RequestDeleteRepoItemAction extends ApiObject
{
    function execute() {
        /** @var RepoItem $repoItem */
        if (null === $repoItem = RepoItem::get()->find('Uuid', $this->RepoItemID)) {
            throw new \Exception("Repo item not found");
        }

        // repo item should be published
        if ($repoItem->Status !== "Published") {
            throw new \Exception("Repo item is not published");
        }

        // Only continue if user has no permission to delete repo item
        if ($repoItem->canDelete(Security::getCurrentUser())) {
            throw new \Exception("User is allowed to delete repo item");
        }

        // Check if user is author of repo item
        if ($repoItem->Owner()->Uuid !== Security::getCurrentUser()->Uuid) {
            throw new \Exception("User is not allowed to create delete request");
        }

        $this->doCreateDeleteRequest($repoItem);
    }

    private function doCreateDeleteRequest(RepoItem $repoItem) {
        $repoItem->ignoreSetStatusCheck = true;
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