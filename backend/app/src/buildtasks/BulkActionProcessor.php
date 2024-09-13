<?php

namespace SurfSharekit\Tasks;

use Exception;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use SurfSharekit\Models\BulkAction;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\NotificationEventCreator;
use SurfSharekit\Models\RepoItem;

class BulkActionProcessor extends BuildTask {
    protected $title = "Process BulkActions";
    protected $description = "Processes all BulkAction objects with status 'WAITING'";

    protected $enabled = true;

    private $totalCount = 0;
    private $successCount = 0;
    private $failCount = 0;

    public function run($request) {
        set_time_limit(0);
        Logger::infoLog("[TASK_START] - Task started", 'BulkActionProcessor', 'run');

        $bulkActions = BulkAction::get()->filter(['ProcessStatus' => 'WAITING']);
        $this->totalCount = count($bulkActions);

        foreach ($bulkActions as $bulkAction) {
            Logger::infoLog("[BULK_ACTION_START] - Started processing BulkAction ($bulkAction->Uuid)", 'BulkActionProcessor', 'run');
            try {
                Security::setCurrentUser($bulkAction->createdBy()); // Impersonate the person that created

                $this->setBulkActionProcessStatus($bulkAction, 'ONGOING');
                $this->processBulkAction($bulkAction);
                $this->setBulkActionProcessStatus($bulkAction, 'COMPLETED');

                Logger::infoLog("[BULK_ACTION_END] - Successfully processed BulkAction ($bulkAction->Uuid), continue...", 'BulkActionProcessor', 'run');
            } catch (Exception $e) {
                $this->setBulkActionProcessStatus($bulkAction, 'FAILED');
                Logger::infoLog("[BULK_ACTION_FAILED] - Failed processing BulkAction ($bulkAction->Uuid)", 'BulkActionProcessor', 'run');
                Logger::infoLog($e->getMessage(), 'BulkActionProcessor', 'run');
                Logger::infoLog("continue...", 'BulkActionProcessor', 'run');
            }
            NotificationEventCreator::getInstance()->create(Constants::SANITIZATION_PROCESS_END_EVENT, $bulkAction);
        }
        Logger::infoLog("[TASK_END] - Task completed", 'BulkActionProcessor', 'run');

        Logger::infoLog("BulkAction Total: $this->totalCount", 'BulkActionProcessor', 'run');
        Logger::infoLog("BulkAction Success: $this->successCount", 'BulkActionProcessor', 'run');
        Logger::infoLog("BulkAction Failed: $this->failCount", 'BulkActionProcessor', 'run');
    }

    private function processBulkAction(BulkAction $bulkAction) {
        $repoItems = $bulkAction->getRepoItemsToPerformActionOn();

        foreach ($repoItems as $repoItem) {
            try {
                DB::get_conn()->transactionStart();
                $this->performActionOnRepoItem($bulkAction->Action, $repoItem);
                DB::get_conn()->transactionEnd();
                $bulkAction->SuccessCount = $bulkAction->SuccessCount + 1;
                $bulkAction->write();
                Logger::infoLog("[ACTION_SUCCESS] - Successfully performed '$bulkAction->Action' action on RepoItem ($repoItem->Uuid)", 'BulkActionProcessor', 'processBulkAction');
            } catch (Exception $e) {
                DB::get_conn()->transactionRollback();
                $bulkAction->FailCount = $bulkAction->FailCount + 1;
                $bulkAction->write();
                Logger::infoLog("[ACTION_FAILED] - Failed performing '$bulkAction->Action' action on RepoItem ($repoItem->Uuid)", 'BulkActionProcessor', 'processBulkAction');
            }
        }
    }

    private function performActionOnRepoItem(string $action, RepoItem $repoItem) {
        switch ($action) {
            case "DELETE":
                Logger::infoLog("[PROCESSING] - Performing a 'DELETE' action on RepoItem ($repoItem->Uuid)", 'BulkActionProcessor', 'performActionOnRepoItem');
                $repoItem->Status = 'Draft';
                $repoItem->IsRemoved = true;
                $repoItem->SkipValidation = true;
                $repoItem->write();
                break;
            case "ARCHIVE":
                Logger::infoLog("[PROCESSING] - Performing 'ARCHIVE' action on RepoItem ($repoItem->Uuid)", 'BulkActionProcessor', 'performActionOnRepoItem');
                $repoItem->IsArchived = true;
                $repoItem->IsPublic = false;
                $repoItem->IsArchivedUpdated = true;
                $repoItem->SkipValidation = true;
                $repoItem->write();
                break;
            case "DEPUBLISH":
                Logger::infoLog("[PROCESSING] - Performing 'DEPUBLISH' action on RepoItem ($repoItem->Uuid)", 'BulkActionProcessor', 'performActionOnRepoItem');
                $repoItem->Status = 'Draft';
                $repoItem->SkipValidation = true;
                $repoItem->write();
                break;
            default:
                Logger::infoLog("[ERROR] - Unkown Action type '$action', continue...", 'BulkActionProcessor', 'performActionOnRepoItem');
                break;
        }
    }

    private function setBulkActionProcessStatus(BulkAction $bulkAction, string $newStatus) {
        switch ($newStatus) {
            case "COMPLETED":
                $bulkAction->ProcessStatus = 'COMPLETED';
                $bulkAction->write();
                $this->successCount++;
                break;
            case "FAILED":
                $bulkAction->ProcessStatus = 'FAILED';
                $bulkAction->write();
                $this->failCount++;
                break;
            case "ONGOING":
                $bulkAction->ProcessStatus = 'ONGOING';
                $bulkAction->write();
                break;
        }
    }
}