import React from "react";
import i18n from "i18next";
import {HelperFunctions} from "./HelperFunctions";
import {ReactComponent as IconCalendarDays} from "../resources/icons/ic-calendar-days.svg";
import styled from "styled-components";

export const TASK_TYPE = {
    CLAIM : "CLAIM",
    REVIEW : "REVIEW",
    FILL: "FILL",
    RECOVER: "RECOVER"
}

export const TASK_ACTION = {
    APPROVE : "APPROVE",
    DECLINE: "DECLINE",
    DELETE: "DELETE",
    FINISH: "FINISH"
}

export const TASK_ACTION_BUTTON_TYPE = {
    POSITIVE : "POSITIVE",
    NEGATIVE: "NEGATIVE"
}

class TaskHelper {

    static getRepoTypeTitle(type){
        switch (type) {
            case 'PublicationRecord': {
                return i18n.t("repoitem.type.publicationrecord")
            }
            case 'LearningObject': {
                return i18n.t("repoitem.type.learningobject")
            }
            case 'ResearchObject': {
                return i18n.t("repoitem.type.researchobject")
            }
            case 'Dataset': {
                return i18n.t("repoitem.type.dataset")
            }
            default:
                return i18n.t("repoitem.type.publication")
        }
    }

    static getTaskTitle(task) {
        const taskData = JSON.parse(task.data)
        switch (task.type) {
            case TASK_TYPE.CLAIM: {
                const claim = taskData.claim
                const nameOfPersonToClaim = claim.claimedPerson.fullName
                const nameOfInstituteMakingClaim = claim.instituteTitle

                return i18n.t("dashboard.tasks.task_title.claim", {
                    institute: nameOfInstituteMakingClaim,
                    personName: `<a href='profile/${taskData.claim.claimedPerson.id}'>${nameOfPersonToClaim}</a>`
                })
            }
            case TASK_TYPE.REVIEW: {
                const repoItem = taskData.repoItem
                const person = taskData.repoItem.author
                return i18n.t("dashboard.tasks.task_title.review",{
                    objectTitle: `<a href='publications/${repoItem.id}'>${HelperFunctions.truncate(repoItem.title, 50)}</a>`,
                    personName: `<a href='profile/${person.id}'>${person.fullName}</a>`
                })
            }
            case TASK_TYPE.FILL: {
                const repoItem = taskData.fillRepoItem
                return i18n.t("dashboard.tasks.task_title.fill",{
                    objectTitle: `<a href='publications/${repoItem.id}'>${HelperFunctions.truncate(repoItem.title, 50)}</a>`
                })
            }
            case TASK_TYPE.RECOVER: {
                const repoItem = taskData.deleteRepoItem
                return i18n.t("dashboard.tasks.task_title.recover", {
                    objectTitle: `<a href='publications/${repoItem.id}'>${HelperFunctions.truncate(repoItem.title, 50)}</a>`
                })
            }
        }
    }


    static getTaskActionButtonText(task, actionType) {
        switch (task.type) {
            case TASK_TYPE.CLAIM: {
                if (actionType === TASK_ACTION_BUTTON_TYPE.POSITIVE) {
                    return i18n.t("dashboard.tasks.actions.accept")
                } else if (actionType === TASK_ACTION_BUTTON_TYPE.NEGATIVE) {
                    return i18n.t("dashboard.tasks.actions.reject")
                } else {
                    return ""
                }
            }
            case TASK_TYPE.REVIEW: {
                if (actionType === TASK_ACTION_BUTTON_TYPE.POSITIVE) {
                    return i18n.t("dashboard.tasks.actions.approve")
                } else if (actionType === TASK_ACTION_BUTTON_TYPE.NEGATIVE) {
                    return i18n.t("dashboard.tasks.actions.decline")
                } else {
                    return ""
                }
            }
            case TASK_TYPE.FILL: {
                if (actionType === TASK_ACTION_BUTTON_TYPE.POSITIVE) {
                    return i18n.t("dashboard.tasks.actions.finish")
                } else if (actionType === TASK_ACTION_BUTTON_TYPE.NEGATIVE) {
                    return i18n.t("dashboard.tasks.actions.delete")
                } else {
                    return ""
                }
            }
            case TASK_TYPE.RECOVER: {
                if (actionType === TASK_ACTION_BUTTON_TYPE.POSITIVE) {
                    return i18n.t("dashboard.tasks.actions.approve")
                } else if (actionType === TASK_ACTION_BUTTON_TYPE.NEGATIVE) {
                    return i18n.t("dashboard.tasks.actions.recover")
                } else {
                    return ""
                }
            }
        }
    }

    static getTypeTitle(type) {
        switch (type) {
            case TASK_TYPE.CLAIM: {
                return i18n.t("dashboard.tasks.type.claim")
            }
            case TASK_TYPE.REVIEW: {
                return i18n.t("dashboard.tasks.type.review")
            }
            case TASK_TYPE.FILL: {
                return i18n.t("dashboard.tasks.type.fill")
            }
            case TASK_TYPE.RECOVER: {
                return i18n.t("dashboard.tasks.type.recover")
            }
        }
    }

    static getRepoTypeTitle(type){
        switch (type) {
            case 'PublicationRecord': {
                return i18n.t("repoitem.type.publicationrecord")
            }
            case 'LearningObject': {
                return i18n.t("repoitem.type.learningobject")
            }
            case 'ResearchObject': {
                return i18n.t("repoitem.type.researchobject")
            }
            case 'Dataset': {
                return i18n.t("repoitem.type.dataset")
            }
        }
    }

    static getDate(date) {
        const dateFormat = HelperFunctions.getDateFormat(date, {
            year: 'numeric',
            month: 'short',
            day: '2-digit'
        })

        return <ReactTaskTableDateColumn>
            <IconCalendarDays />
            <div>{dateFormat.day} {dateFormat.month} {dateFormat.year}</div>
        </ReactTaskTableDateColumn>
    }
}

const ReactTaskTableDateColumn = styled.div `
    display: flex;
    flex-direction: row;
    gap: 20px;
`;

export default TaskHelper;