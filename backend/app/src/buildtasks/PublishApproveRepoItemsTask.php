<?php

use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\RepoItem;

class PublishApproveRepoItemsTask extends BuildTask {
    public function run($request): void {
        Logger::debugLog('PublishApproveRepoItemsTask');

        Security::setCurrentUser(Member::get()->filter(['Email' => Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME')])->first());

        Logger::debugLog(Security::getCurrentUser()->getTitle());

        $repoItemsToPublish = RepoItem::get()->filter(['Status' => 'Embargo'])->where('EmbargoDate <= NOW() OR EmbargoDate IS NULL');
        $count = $repoItemsToPublish->count();
        foreach ($repoItemsToPublish as $repoItem) {
            /** @var RepoItem $repoItem */
            $repoItem->SkipValidation = true; // Skip validation for cases where the template has been changed during the time in embargo
            $repoItem->publish();
        }

        echo $count . ' Repository Items published';
    }
}
