import React from 'react';
import {flame, greyDarker, greyLight, majorelle, oceanGreen, openSans, spaceCadet, white} from "../../../Mixins";
import {faTrash, faCheck, faChevronDown, faTimes, faUser} from "@fortawesome/free-solid-svg-icons";
import styled from "styled-components";
import {GlobalPageMethods} from "../../../components/page/Page";
import Toaster from "../../../util/toaster/Toaster";
import Api from "../../../util/api/Api";
import TaskHelper, {TASK_ACTION, TASK_TYPE} from "../../../util/TaskHelper";
import {useTranslation} from "react-i18next";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import SURFButton from "../../../styled-components/buttons/SURFButton";
import {Link, useHistory} from "react-router-dom";
import {Tooltip} from "../../../components/field/FormField";

function ActionCell(props) {
    const {
        task,
        onDeleteSuccess
    } = props

    const {t} = useTranslation();
    const history = useHistory();

    return (
        <Cell>
            <SURFButton
                width={"175px"}
                backgroundColor={white}
                text={getButtonText()}
                textSize={"12px"}
                textColor={greyDarker}
                border={`1px solid ${greyDarker}`}
                iconStart={task.action === TASK_ACTION.APPROVE ? faCheck : faTimes}
                iconStartSize={"14px"}
                iconStartColor={task.action === TASK_ACTION.APPROVE ? oceanGreen : flame}
                iconEnd={faChevronDown}
                iconEndSize={"10px"}
                padding={"0 10px"}
                iconEndColor={greyDarker}
            />

            <IconContainer>

                {task.completedBy ? (
                    <Tooltip
                        element={(
                            <IconWrapper>
                                <FontAwesomeIcon
                                    icon={faUser}
                                />
                            </IconWrapper>
                        )}
                        contentElement={
                            <CreatedByPopUpContent>
                                <span>{t("dashboard.tasks.tooltips.completed_by")}</span><Link to={`profile/${task.completedBy.id}`}>{task.completedBy.name}</Link>
                            </CreatedByPopUpContent>
                        }>
                    </Tooltip>
                ) : null}

                <IconWrapper style={{display: "flex", flexDirection: "row", WebkitAlignItems: "center", cursor: "pointer"}}>
                    <FontAwesomeIcon
                        icon={faTrash}
                        className={`icon-trash`}
                        onClick={(e) => {
                            e.stopPropagation()
                            removeTask()
                        }}
                    />
                </IconWrapper>
            </IconContainer>

        </Cell>
    )


    function getButtonText() {
        switch (task.type) {
            case TASK_TYPE.CLAIM: {
                if (task.action === TASK_ACTION.APPROVE) {
                    return t("dashboard.tasks.actions.accept")
                } else if (task.action ===  TASK_ACTION.DECLINE) {
                    return t("dashboard.tasks.actions.reject")
                } else {
                    return ""
                }
            }
            case TASK_TYPE.REVIEW: {
                if (task.action === TASK_ACTION.APPROVE) {
                    return t("dashboard.tasks.actions.approve")
                } else if(task.action ===  TASK_ACTION.DECLINE) {
                    return t("dashboard.tasks.actions.decline")
                } else {
                    return ""
                }
            }
            case TASK_TYPE.FILL: {
                if (task.action === TASK_ACTION.APPROVE) {
                    return t("dashboard.tasks.actions.finish")
                } else if(task.action ===  TASK_ACTION.DECLINE) {
                    return t("dashboard.tasks.actions.delete")
                } else {
                    return ""
                }
            }
            case TASK_TYPE.RECOVER: {
                if (task.action === TASK_ACTION.APPROVE) {
                    return t("dashboard.tasks.actions.approve")
                } else if(task.action ===  TASK_ACTION.DECLINE) {
                    return t("dashboard.tasks.actions.recover")
                } else {
                    return ""
                }
            }
        }
    }

    function removeTask() {
        GlobalPageMethods.setFullScreenLoading(true)
        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            },
            data: {
                data: [
                    {
                        type: 'task',
                        id: task.id
                    }
                ]
            }
        }
        Api.delete('tasks/' + task.id, onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showToaster({message: t("publication.request_save_publication_success")})
            onDeleteSuccess(task.id)
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError();
        }
    }
}

const Cell = styled.div`
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    border-left: 1px solid ${greyLight};
    padding-left: 25px;
`;

const IconWrapper = styled.div`
    cursor: pointer;
    display: flex; 
    flex-direction: row;
    -webkit-align-items: center;
    padding: 5px;
`;

const IconContainer = styled.div`
    display: flex;
    flex-direction: row;
    gap: 8px;
    margin-left: 12px;
`;

const CreatedByPopUpContent = styled.div`
    ${openSans};
    a {
        color: ${majorelle};
        text-decoration: underline;
    }
`;

export default ActionCell;