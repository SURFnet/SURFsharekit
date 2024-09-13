import React, {useEffect, useState} from 'react';
import ModalButton, {MODAL_ALIGNMENT, MODAL_VERTICAL_ALIGNMENT} from "../../../styled-components/buttons/ModalButton";
import BasicDropdownList from "../../../styled-components/dropdowns/dropdown-lists/BasicDropdownList";
import BasicDropdownItem from "../../../styled-components/dropdowns/dropdown-items/BasicDropdownItem";
import SURFButton from "../../../styled-components/buttons/SURFButton";
import {flame, greyDarker, greyLight, oceanGreen, spaceCadet, white} from "../../../Mixins";
import {faCheck, faChevronDown, faTimes} from "@fortawesome/free-solid-svg-icons";
import TextInputModal from "../../../styled-components/modals/TextInputModal";
import styled from "styled-components";
import {GlobalPageMethods} from "../../../components/page/Page";
import Toaster from "../../../util/toaster/Toaster";
import Api from "../../../util/api/Api";
import TaskHelper, {TASK_ACTION, TASK_ACTION_BUTTON_TYPE, TASK_TYPE} from "../../../util/TaskHelper";
import {useTranslation} from "react-i18next";
import {useHistory} from "react-router-dom";
import {restore} from "../../../trashcan/TrashcanResultRow";

function ActionCell(props) {
    const {
        task,
        onActionSuccess
    } = props

    const {t} = useTranslation();
    const history = useHistory();
    const [redirect, setRedirect] = useState(false)

    return (
        <Cell>
            <ModalButton
                modalHorizontalAlignment={MODAL_ALIGNMENT.LEFT}
                modalVerticalAlignment={MODAL_VERTICAL_ALIGNMENT.BOTTOM}
                modal={
                    <BasicDropdownList
                        dropdownItems={getDropdownItems()}
                    />
                }
                button={
                    <SURFButton
                        width={"135px"}
                        backgroundColor={white}
                        text={TaskHelper.getTaskActionButtonText(task, TASK_ACTION_BUTTON_TYPE.POSITIVE)}
                        textSize={"12px"}
                        textColor={spaceCadet}
                        border={`1px solid ${spaceCadet}`}
                        iconStart={faCheck}
                        iconStartSize={"14px"}
                        iconStartColor={oceanGreen}
                        iconEnd={faChevronDown}
                        iconEndSize={"6px"}
                        padding={"0 10px"}
                    />
                }
            />

            <ModalButton
                modalHorizontalAlignment={MODAL_ALIGNMENT.RIGHT}
                modalVerticalAlignment={MODAL_VERTICAL_ALIGNMENT.BOTTOM}
                modal={
                    task.type === TASK_TYPE.FILL ?
                        <BasicDropdownList
                            dropdownItems={getDropdownItems("delete")}
                        />
                        : task.type === TASK_TYPE.RECOVER ?
                        <BasicDropdownList
                            dropdownItems={getDropdownItems("recover")}
                        />
                        :
                        <TextInputModal
                            onSendButtonClick={(text) => patchTask(task, TASK_ACTION.DECLINE, text)}
                        />
                }
                button={
                    <SURFButton
                        width={"135px"}
                        backgroundColor={white}
                        text={TaskHelper.getTaskActionButtonText(task, TASK_ACTION_BUTTON_TYPE.NEGATIVE)}
                        textSize={"12px"}
                        textColor={spaceCadet}
                        border={`1px solid ${spaceCadet}`}
                        iconStart={faTimes}
                        iconStartSize={"14px"}
                        iconStartColor={flame}
                        iconEnd={faChevronDown}
                        iconEndSize={"6px"}
                        padding={"0 10px"}
                    />
                }
            />
        </Cell>
    )

    function getRepoItemID(task) {
        if (task) {
            return task.repoItem.id;
        }
    }

    function getRepoItemType(task) {
        if (task) {
            return task.repoItem.type;
        }
    }

    function getDropdownItems(action) {
        switch (task.type) {
            case TASK_TYPE.CLAIM: {
                return [
                    new BasicDropdownItem(
                        t("dashboard.tasks.dropdown_titles.add_person"),
                        t("dashboard.tasks.tooltips.accept_claim"),
                        () => {
                            patchTask(task, TASK_ACTION.APPROVE)
                        }
                    )
                ]
            }
            case TASK_TYPE.REVIEW: {
                return [
                    new BasicDropdownItem(
                    t("dashboard.tasks.dropdown_titles.approve_publication"),
                        null,
                        () => {
                            patchTask(task, TASK_ACTION.APPROVE)
                        }
                    )
                ]
            }
            case TASK_TYPE.RECOVER: {
                if (action) {
                    return [
                        new BasicDropdownItem(
                            t("dashboard.tasks.dropdown_titles.recover_publication"),
                            null,
                            () => {
                                patchTask(task, TASK_ACTION.DECLINE, null, true);
                            }
                        )
                    ]
                } else {
                    return [
                        new BasicDropdownItem(
                            t("dashboard.tasks.dropdown_titles.delete_publication"),
                            null,
                            () => {
                                patchTask(task, TASK_ACTION.APPROVE)
                            }
                        )
                    ]
                }

            }
            case TASK_TYPE.FILL: {
                if (action) {
                    return [
                        new BasicDropdownItem(
                            t("dashboard.tasks.dropdown_titles.delete_publication"),
                            null,
                            () => {
                                patchTask(task, TASK_ACTION.DECLINE,"Deleted");
                            }
                        )
                    ]
                } else {
                    return [
                        new BasicDropdownItem(
                            t("dashboard.tasks.dropdown_titles.finish_publication"),
                            null,
                            () => {
                                patchTask(task, TASK_ACTION.APPROVE, null, true);
                            }
                        )
                    ]
                }
            }
        }
    }

    function patchTask(task, action, reasonOfDecline = null, redirect = false) {
        GlobalPageMethods.setFullScreenLoading(true)

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)

            if (task.type !== TASK_TYPE.FILL) {
                Toaster.showToaster({message: t("publication.request_save_publication_success")})
            }

            if (redirect) {
                history.push(`./publications/${getRepoItemID(task)}`)
            }

            onActionSuccess(task.id)
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            if(error.response.status === 404 || error.response.status === 410) {
                Toaster.showToaster({type: "info", message: t("dashboard.tasks.not_found")})
                onActionSuccess(task.id)
            } else {
                Toaster.showServerError(error)
            }
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError();
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const patchData = {
            "data": {
                "type": "task",
                "id": task.id,
                "attributes": {
                    "action": action,
                    "reasonOfDecline": reasonOfDecline
                }
            }
        };

        const url = "tasks/" + task.id
        Api.patch(url, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData)
    }
}

const Cell = styled.div`
    display: flex;
    flex-direction: row;
    gap: 15px;
    border-left: 1px solid ${greyLight};
    padding-left: 25px;
`;

export default ActionCell;