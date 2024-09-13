<?php

namespace SurfSharekit\Tasks;

use DateInterval;
use DateTime;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\RepoItem;

class EmptyTrashCanTask extends BuildTask {
    protected $title = 'Deletes items in trash';
    protected $description = 'This task removes (permanently) removed RepoItems from the trash can. How long items stay in trash before picked up by this task is determined by an environment variable.';

    protected $enabled = true;

    protected $latestChecksum = null;

    function run($request) {
        set_time_limit(0);
        Security::setCurrentUser(Member::get()->filter(['Email' => Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME')])->first());

        while(($repoItems = $this->getRepoItemsToPermanentlyDelete()) && $repoItems->count() > 0) {
            // Generate checksum to prevent infinite loop when there are items that could not be removed for some unknown reason
            echo ("<br> About to permanently delete " . $repoItems->count() . " RepoItems<br>");
            $checksum = crc32(json_encode($repoItems->toNestedArray()));
            echo("</br>" . $checksum . "</br>");
            if ($checksum === $this->latestChecksum) {
                echo("</br> BREAKING </br>");
                break;
            }
            $this->latestChecksum = $checksum;
            $this->permanentlyDeleteRepoItems($repoItems);
        }

        while(($repoItems = $this->getRepoItemsToMarkAsPendingForDestruction()) && $repoItems->count() > 0) {
            // Generate checksum to prevent infinite loop when there are items that could not be removed for some unknown reason
            echo ("<br> About to set " . $repoItems->count() . " RepoItems to pending for destruction <br>");
            $checksum = crc32(json_encode($repoItems->toNestedArray()));
            echo("</br>" . $checksum . "</br>");
            if ($checksum === $this->latestChecksum) {
                echo("</br> BREAKING </br>");
                break;
            }
            $this->latestChecksum = $checksum;
            $this->setRepoItemsToPendingForDestruction($repoItems);
        }
    }

    function getRepoItemsToPermanentlyDelete() {
        $days = Environment::getEnv('MAX_PENDING_FOR_DESTRUCTION_TIME');
        $destructionTimeLimit = (new DateTime())->sub(new DateInterval('P' . $days . 'D'))->format("Y-m-d H:i:s");
        return RepoItem::get()
            ->filter([
                'RepoType' => Constants::MAIN_REPOTYPES,
                'PendingForDestruction' => 1,
                'LastEdited:LessThanOrEqual' => $destructionTimeLimit
            ])->limit(50);
    }

    function getRepoItemsToMarkAsPendingForDestruction() {
        $days = Environment::getEnv('MAX_TRASH_TIME');
        $destructionTimeLimit = (new DateTime())->sub(new DateInterval('P' . $days . 'D'))->format("Y-m-d H:i:s");

        return RepoItem::get()
            ->filter([
                'RepoType' => Constants::MAIN_REPOTYPES,
                'IsRemoved' => 1,
                'LastEdited:LessThanOrEqual' => $destructionTimeLimit
            ])->limit(50);
    }

    function setRepoItemsToPendingForDestruction($repoItems) {
        foreach ($repoItems as $repoItem) {
            $repoItem->SkipValidation = true;
            $repoItem->delete();
            echo "Deleted repoItem with ID: $repoItem->ID from trash and is now pending for destruction</br>";
        }
    }

    function permanentlyDeleteRepoItems($repoItems) {
        $repoItemIds = $repoItems->getIDList();
        $repoItemIdsList = $repoItemIds ? implode(',', $repoItemIds) : 0;
        DB::query("
            DELETE FROM SurfSharekit_RepoItem
            WHERE ID IN ($repoItemIdsList)
       ");
        echo "Permanently deleted ".  count($repoItemIds) . " RepoItems which were pending for destruction</br>";
    }
}