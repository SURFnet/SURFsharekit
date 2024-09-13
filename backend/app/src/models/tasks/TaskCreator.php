<?php

namespace SurfSharekit\Models;

use Exception;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\NotificationEventCreator;

class TaskCreator {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new TaskCreator();
        }

        return self::$instance;
    }

    public function createReviewTasks(RepoItem $repoItem) {
        $personsToCreateTaskFor = $repoItem->getSupportersAndSiteAdminsWhoCanPublish();

        $associationUuid = Task::generateAssociationUuid();
        try {
            DB::get_conn()->transactionStart();
            foreach ($personsToCreateTaskFor as $person) {
                $task = new Task();
                $task->Type = Constants::TASK_TYPE_REVIEW;
                $task->OwnerID = $person->ID;
                $task->RepoItemID = $repoItem->ID;
                $task->AssociationUuid = $associationUuid;
                $task->write();
            }
            DB::get_conn()->transactionEnd();
        } catch (Exception $e) {
            DB::get_conn()->transactionRollback();
            throw $e;
        }
    }

    public function createRecoverTasks(RepoItem $repoItem) {
        $personsToCreateTaskFor = $repoItem->getSupportersAndSiteAdminsWhoCanPublish();

        $associationUuid = Task::generateAssociationUuid();
        try {
            DB::get_conn()->transactionStart();
            foreach ($personsToCreateTaskFor as $person) {
                $task = new Task();
                $task->Type = Constants::TASK_TYPE_RECOVER;
                $task->OwnerID = $person->ID;
                $task->RepoItemID = $repoItem->ID;
                $task->AssociationUuid = $associationUuid;
                $task->write();
            }
            DB::get_conn()->transactionEnd();
            NotificationEventCreator::getInstance()->create(Constants::RECOVER_REQUEST_CREATED_EVENT, $repoItem);
        } catch (Exception $e) {
            DB::get_conn()->transactionRollback();
            throw $e;
        }
    }

    public function createFillTasks(RepoItem $repoItem){
        $personsToCreateTaskFor = $repoItem->getSupportersAndSiteAdminsWhoCanPublish();

        $associationUuid = Task::generateAssociationUuid();
        try {
            DB::get_conn()->transactionStart();
            foreach ($personsToCreateTaskFor as $person) {
                $task = new Task();
                $task->Type = Constants::TASK_TYPE_FILL;
                $task->OwnerID = $person->ID;
                $task->RepoItemID = $repoItem->ID;
                $task->AssociationUuid = $associationUuid;
                $task->write();
            }
            DB::get_conn()->transactionEnd();
            NotificationEventCreator::getInstance()->create(Constants::FILL_REQUEST_CREATED_EVENT, $repoItem);
        } catch (Exception $e) {
            DB::get_conn()->transactionRollback();
            throw $e;
        }
    }

    public function createClaimTask(Claim $claim) {
        $personsToCreateTaskFor = $claim->getPersonsToEditClaim();

        $associationUuid = Task::generateAssociationUuid();
        $moreThanOnePersonToCreateTaskFor = $personsToCreateTaskFor->count() > 1;
        try {
            DB::get_conn()->transactionStart();
            foreach ($personsToCreateTaskFor as $person) {
                $task = new Task();
                $task->Type = Constants::TASK_TYPE_CLAIM;
                $task->OwnerID = $person->ID;
                $task->ClaimID = $claim->ID;
                $task->PersonID = $claim->ObjectID;
                if ($moreThanOnePersonToCreateTaskFor) {
                    // Only set AssociationUuid if there's more than 1 task to be generated
                    $task->AssociationUuid = $associationUuid;
                }
                $task->write();
            }
            DB::get_conn()->transactionEnd();
        } catch (Exception $e) {
            DB::get_conn()->transactionRollback();
            throw $e;
        }
    }

}