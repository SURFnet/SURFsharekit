import React, {useCallback, useState} from "react"
import {FontAwesomeIcon} from '@fortawesome/react-fontawesome'
import {faChevronDown, faChevronRight, faEdit, faPlus, faToggleOff, faToggleOn} from '@fortawesome/free-solid-svg-icons'
import {ReactComponent as IconOrganisation} from "../resources/icons/ic-organisation.svg";
import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import {useTranslation} from "react-i18next";
import Toaster from "../util/toaster/Toaster";
import Api from "../util/api/Api";
import {OrganisationStatusLabel} from "./OrganisationStatusLabel";
import {GlobalPageMethods} from "../components/page/Page";
import AddOrganisationLayerPopup from "./addorganisationlayerpopup/AddOrganisationLayerPopup";
import {useHistory} from "react-router-dom";
import VerificationPopup from "../verification/VerificationPopup";

export function ExpandableRow(props) {
    const {t} = useTranslation()
    const [institute, setInstitute] = useState(props.data)
    const [childInstitutes, setChildInstitutes] = useState(null)
    const [isLoadingChildInstitutes, setIsLoadingChildInstitutes] = useState(false)
    const [isExpanded, setIsExpanded] = useState(false)
    const [isRemoved, setIsRemoved] = useState(institute.isRemoved)
    const [state, setState] = useState(false);
    const forceUpdate = useCallback(() => setState(!state), []);
    const hasChildren = institute.childrenInstitutesCount;
    const partOfConsortium = props.partOfConsortium ? true : institute.level === 'consortium'
    const history = useHistory()

    const chevronStyle = {
        "visibility": hasChildren ? "visible" : "hidden"
    }
    const rowStyle = {
        "cursor": hasChildren ? "pointer" : "default"
    }

    function getInactiveClassName() {
        return isRemoved ? " inactive" : "";
    }

    function onClickExpand() {
        if (partOfConsortium && institute.level !== 'consortium') {
            return
        }
        if (!hasChildren) {
            return;
        }
        if (!isExpanded) {
            setIsLoadingChildInstitutes(true)
            getInstituteChildren(institute.id, partOfConsortium, props.showInactive, history, (data) => {
                setIsLoadingChildInstitutes(false)
                //Nullify childInstitutes first to force the changes :-(
                setChildInstitutes(null)
                setChildInstitutes(data)
            }, () => {
                setIsLoadingChildInstitutes(false)
            })
        }
        setIsExpanded(!isExpanded)
    }

    function onClickDeleteInstitute(event) {
        event.stopPropagation()
        if (institute.permissions.canDelete) {
            VerificationPopup.show(t("organisation.delete_confirmation.title"), t("organisation.delete_confirmation.subtitle"), () => {
                doDeleteInstitute()
            })
        }
    }

    function doDeleteInstitute() {
        GlobalPageMethods.setFullScreenLoading(true)
        deleteInstitute(institute.id, history, () => {
            setIsRemoved(true)
            GlobalPageMethods.setFullScreenLoading(false)
        }, () => {
            GlobalPageMethods.setFullScreenLoading(false)
        })
    }

    function onClickEditInstitute(event) {
        event.stopPropagation()
        if (institute.permissions.canEdit) {
            createOrEditInstitute(true);
        }
    }

    function onClickCreateInstitute(event) {
        event.stopPropagation()
        if (institute.permissions.canCreateSubInstitute) {
            createOrEditInstitute(false);
        }
    }

    function createOrEditInstitute(isEditing = false) {
        const onSaveOrganisation = (savedInstitute, callbackIsEditing) => {
            if (callbackIsEditing) {
                // Edited institute
                setInstitute(savedInstitute)
                forceUpdate();
            } else {
                // Created new institute
                getInstituteChildren(institute.id, partOfConsortium, props.showInactive, history, (data) => {
                    setIsLoadingChildInstitutes(false)
                    //Nullify childInstitutes first to force the changes :-(
                    setChildInstitutes(null)
                    setChildInstitutes(data)
                    institute.childrenInstitutes = data
                    setInstitute(institute)
                    forceUpdate()
                }, () => {
                    setIsLoadingChildInstitutes(false)
                })
            }

            Toaster.showToaster({message: t("organisation.save_organisation_success_message")})
        }

        const onCancelPopup = () => {
        }

        AddOrganisationLayerPopup.show(institute, isEditing, onSaveOrganisation, onCancelPopup)
    }

    return (
        <div className={"expandable-row-container"}>
            <div className={"parent-relationship-line"} style={{"display": props.isRootInstitute ? "none" : "block"}}/>
            <div className={"expandable-row " + (partOfConsortium && 'consortium')}
                 onClick={onClickExpand}
                 style={rowStyle}>
                <div className={"child-relationship-line"}
                     style={{"display": props.isRootInstitute ? "none" : "block"}}>
                    <div className={"line-node"}/>
                </div>
                <div className={`status-color-indicator${getInactiveClassName()}`}/>
                <div className={"row-information"}>
                    <FontAwesomeIcon
                        className={"icon-chevron " + ((partOfConsortium && institute.level !== 'consortium') && 'hidden')}
                        icon={isExpanded ? faChevronDown : faChevronRight}
                        style={chevronStyle}/>
                    <div className={"icon-organisation-wrapper"}>
                        <IconOrganisation className={`${getInactiveClassName()}`}/>
                    </div>
                    <div className={"row-text"}>
                        {institute.title}
                    </div>
                </div>

                <div className={"right-row-section"}>
                    <OrganisationStatusLabel level={institute.level} partOfConsortium={partOfConsortium}/>
                    <div className={"row-actions " + (partOfConsortium && 'hidden')}>
                        <FontAwesomeIcon icon={isRemoved ? faToggleOff : faToggleOn}
                                         className={`${institute.permissions.canDelete && !isRemoved ? "" : " disabled"}`}
                                         onClick={isRemoved ? {} : onClickDeleteInstitute}/>
                        <FontAwesomeIcon icon={faEdit} className={`${institute.permissions.canEdit ? "" : " disabled"}`}
                                         onClick={onClickEditInstitute}/>
                        <FontAwesomeIcon icon={faPlus}
                                         className={`${institute.permissions.canCreateSubInstitute ? "" : " disabled"}`}
                                         onClick={onClickCreateInstitute}/>
                    </div>
                </div>
            </div>
            <div className={"child-rows"}>
                {
                    isLoadingChildInstitutes && isExpanded ? (
                        <ExpandableRowLoadingIndicator/>
                    ) : (
                        isExpanded && childInstitutes && childInstitutes.map((childInstitute, i) => {
                            return <ExpandableRow
                                partOfConsortium={partOfConsortium}
                                key={childInstitute.id}
                                data={childInstitute}
                                showInactive={props.showInactive}
                                onClickExpand={props.onClickExpand}
                            />
                        })
                    )
                }
            </div>
        </div>
    )
}

export function ExpandableRowLoadingIndicator() {

    const {t} = useTranslation()

    return (
        <div className={"expandable-row-loading-indicator"}>
            <LoadingIndicator/>
            <div className={"loading-subtitle"}>{t("loading_indicator.loading_text")}</div>
        </div>
    )
}

export function getInstituteChildren(instituteParentId, useConsortiumFilter, showInactive, history, successCallback, errorCallback = () => {
}) {
    function onValidate(response) {
    }

    function onSuccess(response) {
        successCallback(response.data)
    }

    function onLocalFailure(error) {
        errorCallback()
        Toaster.showDefaultRequestError();
        console.log(error);
    }

    function onServerFailure(error) {
        console.log(error);
        Toaster.showServerError(error)
        if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
            history.push('/login?redirect=' + window.location.pathname);
        }
        errorCallback()
    }

    const config = {
        params: {
            'filter[scope]': 'off'
        }
    };

    if (useConsortiumFilter) {
        config.params['filter[consortiumParent]'] = instituteParentId
    } else {
        config.params['filter[parent]'] = instituteParentId
    }

    if (!showInactive) {
        config.params['filter[inactive]'] = '0';
    }

    //MB limit fields to improve performance
    config.params['fields[institutes]'] = 'title,permissions,isRemoved,level,abbreviation,summary,type,childrenInstitutesCount';
    config.params['sort'] = 'title';

    Api.jsonApiGet('institutes', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
}

export function deleteInstitute(instituteId, history, successCallback, errorCallback = () => {
}) {

    function onValidate(response) {
    }

    function onSuccess(response) {
        console.log("Success deleting institute, response = ", response)
        successCallback(response.data);
    }

    function onLocalFailure(error) {
        Toaster.showDefaultRequestError()
        console.log(error);
        errorCallback()
    }

    function onServerFailure(error) {
        console.log(error);
        Toaster.showServerError(error)
        if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
            history.push('/login?redirect=' + window.location.pathname);
        }
        errorCallback()
    }

    const config = {
        headers: {
            "Content-Type": "application/vnd.api+json",
        }
    }

    const patchData = {
        "data": {
            "type": "institute",
            "id": instituteId,
            "attributes": {
                "isRemoved": true
            }
        }
    };

    Api.patch(`institutes/${instituteId}`, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData);
}

