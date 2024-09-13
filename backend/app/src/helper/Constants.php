<?php

namespace SurfSharekit\Models\Helper;

use SurfSharekit\constants\RoleConstant;

/**
 * Central place to manage constants
 */
class Constants {

    const MAIN_REPOTYPES = ["PublicationRecord", "LearningObject", "ResearchObject", "Dataset", "Project"];
    const SECONDARY_REPOTYPES = ["RepoItemRepoItemFile", "RepoItemLearningObject", "RepoItemLink", "RepoItemPerson", "RepoItemResearchObject"];
    const ALL_REPOTYPES = ["PublicationRecord", "LearningObject", "ResearchObject", "Dataset", "Project", "RepoItemRepoItemFile", "RepoItemLearningObject", "RepoItemLink", "RepoItemPerson", "RepoItemResearchObject"];

    // Events
    const REPO_ITEM_STATUS_CHANGED_EVENT = "RepoItemStatusChanged";
    const SANITIZATION_PROCESS_END_EVENT = "SanitizationProcessEndEvent";
    const CLAIM_STATUS_CHANGED_EVENT = "ClaimStatusChanged";
    const FILL_REQUEST_CREATED_EVENT = "FillRequestCreated";
    const RECOVER_REQUEST_CREATED_EVENT = "RecoverRequestCreated";
    const RECOVER_REQUEST_APPROVED_EVENT = "RecoverRequestApproved";
    const RECOVER_REQUEST_DECLINED_EVENT = "RecoverRequestDeclined";

    const EVENTS = [
        Constants::REPO_ITEM_STATUS_CHANGED_EVENT,
        Constants::CLAIM_STATUS_CHANGED_EVENT,
        Constants::SANITIZATION_PROCESS_END_EVENT
    ];

    // Tasks
    const TASK_STATE_INITIAL = "INITIAL";
    const TASK_STATE_ONGOING = "ONGOING";
    const TASK_STATE_DONE = "DONE";
    const TASK_STATES = [
        Constants::TASK_STATE_INITIAL,
        Constants::TASK_STATE_ONGOING,
        Constants::TASK_STATE_DONE
    ];

    const TASK_ACTION_APPROVE = "APPROVE";
    const TASK_ACTION_DECLINE = "DECLINE";
    const TASK_ACTIONS = [
        Constants::TASK_ACTION_APPROVE,
        Constants::TASK_ACTION_DECLINE
    ];

    const TASK_TYPE_CLAIM = "CLAIM";
    const TASK_TYPE_REVIEW = "REVIEW";
    const TASK_TYPE_FILL = "FILL";
    const TASK_TYPE_RECOVER = "RECOVER";

    const TASK_TYPES = [
        Constants::TASK_TYPE_CLAIM,
        Constants::TASK_TYPE_REVIEW,
        Constants::TASK_TYPE_FILL,
        Constants::TASK_TYPE_RECOVER
    ];
}