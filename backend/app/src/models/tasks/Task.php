<?php

namespace SurfSharekit\Models;

use Exception;
use PermissionProviderTrait;
use Ramsey\Uuid\Uuid;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\MemberHelper;

/**
 * Class Task
 * @package SurfSharekit\Models
 * @property string ClaimedBy
 * @property string Type
 * @property string ReasonOfDecline
 * @property string State
 * @property string Action
 * @property string AssociationUuid
 * @property string Data
 * @property Int OwnerID
 * @property Int CompletedByID
 * @property Int PersonID
 * @property Int RepoItemID
 * @property Int ClaimID
 * @method Person Owner
 * @method Person CompletedBy
 * @method Person Person
 * @method RepoItem RepoItem
 * @method Claim Claim
 */
class Task extends DataObject implements PermissionProvider{
    use PermissionProviderTrait;
    private static $singular_name = 'Task';
    private static $plural_name = 'Tasks';

    private static $table_name = 'SurfSharekit_Task';

    private static $db = [
        'ClaimedBy' => 'Varchar(255)',
        'Type' => 'Enum(array("CLAIM", "REVIEW", "FILL", "RECOVER"), null)',
        'ReasonOfDecline' => 'Text',
        'State' => 'Enum(array("INITIAL", "ONGOING", "DONE"), "INITIAL")',
        'Action' => 'Enum(array("APPROVE", "DECLINE"), null)',
        'AssociationUuid' => 'Varchar(255)',
        'Data' => 'Text'
    ];

    private static $has_one = [
        'Owner' => Person::class,
        "CompletedBy" => Person::class,

        // Below are the possible relations
        'Person' => Person::class,
        'RepoItem' => RepoItem::class,
        'Claim' => Claim::class
    ];

    protected function onBeforeWrite() {
        parent::onBeforeWrite();

        if (!$this->isInDB()) {
            $this->generateDataJSON();
        }

        if($this->isInDB() && $this->isChanged('Action')) {
            $currentUserUuid = Security::getCurrentUser()->Uuid;
            if ($this->State != Constants::TASK_STATE_INITIAL) {
                if($this->ClaimedBy && $this->ClaimedBy != $currentUserUuid) {
                    throw new Exception("Someone else has already completed this task");
                } else {
                    throw new Exception("Cannot change the action of task with state '$this->State'");
                }
            }

            $this->makeAttemptToClaimTask($currentUserUuid);
            $this->handleTaskActionIfClaimedSuccessfully($currentUserUuid);
        }
    }

    protected function onAfterWrite() {
        parent::onAfterWrite();

        // Prevent deleting the task in onBeforeWrite. When deleting in onBeforeWrite a new, empty record is created
        if ($this->IsMarkedForDeletion) {
            DB::query("DELETE FROM SurfSharekit_Task WHERE ID = $this->ID");
        }
    }

    private function makeAttemptToClaimTask(?String $currentUserUuid) {
        if($currentUserUuid) {
            try {
                DB::get_conn()->transactionStart();
                DB::query("
                    UPDATE `SurfSharekit_Task`
                    SET ClaimedBy = CASE
                        WHEN ClaimedBy IS NULL THEN '$currentUserUuid'
                        ELSE ClaimedBy
                    END
                    WHERE AssociationUuid = '$this->AssociationUuid'
                ");
                DB::get_conn()->transactionEnd();
            } catch (Exception $e) {
                DB::get_conn()->transactionRollback();
                throw $e;
            }
        } else {
            throw new Exception("Cannot perform action on task, user's uuid unknown");
        }
    }

    private function handleTaskActionIfClaimedSuccessfully(String $currentUserUuid) {
        $claimedByUuid = DB::query("
                SELECT ClaimedBy
                FROM `SurfSharekit_Task`
                WHERE ID = '$this->ID'
        ")->value();

        if ($claimedByUuid == $currentUserUuid) {
            $this->handleTaskAction();
        } else {
            throw new Exception("Task was claimed by another user");
        }
    }

    private function handleTaskAction() {
        try {
            DB::get_conn()->transactionStart();
            switch ($this->Type) {
                case Constants::TASK_TYPE_FILL: {
                    FillTypeTaskActionHandler::run($this);
                    break;
                }
                case Constants::TASK_TYPE_REVIEW: {
                    ReviewTypeTaskActionHandler::run($this);
                    break;
                }
                case Constants::TASK_TYPE_CLAIM: {
                    ClaimTypeTaskActionHandler::run($this);
                    break;
                }
                case Constants::TASK_TYPE_RECOVER: {
                    RecoverTypeTaskActionHandler::run($this);
                    break;
                }
            }
            DB::get_conn()->transactionEnd();
        } catch (Exception $e) {
            if($e instanceof TaskNotProcessableException) {
                DB::get_conn()->transactionEnd();
            } else {
                DB::get_conn()->transactionRollback();
            }
        }
    }

    public static function generateAssociationUuid() : String {
        $associationUuid = null;
        $exceptionCount = 0;
        do {
            try {
                $associationUuid = Uuid::uuid4()->toString();
            } catch (Exception $e) {
                $exceptionCount++;
                Logger::errorLog("Failed generating a task AssociationUuid, retrying...");
            }
        } while (!$associationUuid || $exceptionCount == 3);
        if ($associationUuid) {
            return $associationUuid;
        }
        throw new Exception("Could not generate task AssociationUuid value after 3 retries");
    }

    public function canCreate($member = null, $context = []) {
        return false;
    }

    public function canView($member = null) {
        if ($member) {
            return $member->ID == $this->OwnerID;
        }
        return parent::canView($member);
    }

    public function canEdit($member = null) {
        if ($member) {
            return $member->ID == $this->OwnerID;
        }
        return parent::canEdit($member);
    }

    public function canDelete($member = null) {
        if ($member) {
            return $member->ID == $this->OwnerID;
        }
        return parent::canDelete($member);
    }

    public function validate() {
        $result = parent::validate();

        if($this->isInDB()) {
            switch ($this->Type) {
                case Constants::TASK_TYPE_REVIEW:
                case Constants::TASK_TYPE_FILL:
                case Constants::TASK_TYPE_RECOVER:
                case Constants::TASK_TYPE_CLAIM: {
                    if($this->Action != Constants::TASK_ACTION_APPROVE && $this->Action != Constants::TASK_ACTION_DECLINE) {
                        $result->addFieldError("Action", "Action '$this->Action' is not compatible with task type '$this->Type'");
                    }
                    break;
                }
            }
        }

        return $result;
    }

    public function getInstitute(): ?Institute {
        switch ($this->Type) {
            case Constants::TASK_TYPE_RECOVER:
            case Constants::TASK_TYPE_REVIEW:
            case Constants::TASK_TYPE_FILL:
                return $this->RepoItem()->Institute();
            case Constants::TASK_TYPE_CLAIM:
                return $this->Claim()->Institute();
            default: return null;
        }
    }

    public function getInstituteTitle(): ?string {
        if (null !== $institute = $this->getInstitute()) {
            return $institute->Title;
        }

        return null;
    }

    public function getMaterial(): ?string {
        switch ($this->Type) {
            case Constants::TASK_TYPE_RECOVER:
            case Constants::TASK_TYPE_REVIEW:
            case Constants::TASK_TYPE_FILL:
                return $this->RepoItem->RepoType;
            default: return null;
        }
    }

    public function generateDataJSON() {
        $data = [];
        switch ($this->Type) {
            case Constants::TASK_TYPE_REVIEW: {
                $repoItemInstitute = $this->RepoItem()->Institute();
                $data["repoItem"] = [
                    "id" => $this->RepoItem()->Uuid,
                    'title' => $this->RepoItem()->Title,
                    "instituteTitle" => $repoItemInstitute->Title,
                    "type" => $this->RepoItem()->RepoType,
                    "author" => [
                        "id" => $this->RepoItem()->Owner()->Uuid,
                        "fullName" => MemberHelper::getMemberFullName($this->RepoItem()->Owner())
                    ]
                ];
                $this->Data = json_encode($data);
                break;
            }
            case Constants::TASK_TYPE_RECOVER: {
                $data["deleteRepoItem"] = [
                    "id" => $this->RepoItem()->Uuid,
                    'title' => $this->RepoItem()->Title,
                    "type" => $this->RepoItem()->RepoType
                ];
                $this->Data = json_encode($data);
                break;
            }
            case Constants::TASK_TYPE_FILL: {
                $repoItemInstitute = $this->RepoItem()->Institute();
                $data["fillRepoItem"] = [
                    "id" => $this->RepoItem()->Uuid,
                    'title' => $this->RepoItem()->Title,
                    "instituteTitle" => $repoItemInstitute->Title,
                    "type" => $this->RepoItem()->RepoType
                ];
                $this->Data = json_encode($data);
                break;
            }
            case Constants::TASK_TYPE_CLAIM: {
                $data = [];
                $claimingInstitute = $this->Claim()->Institute();
                if($claimingInstitute) {
                    $data["claim"] = [
                        "id" => $this->Claim()->Uuid,
                        "instituteTitle" => $claimingInstitute->Title,
                        "claimedPerson" => [
                            "id" => $this->Person()->Uuid,
                            "fullName" => MemberHelper::getMemberFullName($this->Person())
                        ]
                    ];
                }
                $this->Data = json_encode($data);
                break;
            }
        }
    }
}