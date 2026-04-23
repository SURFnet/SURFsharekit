import React from 'react'
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faFileInvoice, faTrash} from "@fortawesome/free-solid-svg-icons";
import "./archiveresultrow.scss"
import {useTranslation} from "react-i18next";
import RepoItemHelper from "../util/RepoItemHelper";
import {faUndo} from "@fortawesome/free-solid-svg-icons/faUndo";
import VerificationPopup from "../verification/VerificationPopup";
import {GlobalPageMethods} from "../components/page/Page";
import Toaster from "../util/toaster/Toaster";
import Api from "../util/api/Api";

function ArchiveResultRow(props) {
    const {t} = useTranslation();
    const permissionCanArchive = props.permissions.canArchive


    function undoArchive(id, errorCallback = () => {}) {

        VerificationPopup.show(
            t("verification.undo_archive.title"), "", () => {
                GlobalPageMethods.setFullScreenLoading(true)
                function onValidate(response) {
                }

                function onSuccess() {
                    props.onReload()
                    GlobalPageMethods.setFullScreenLoading(false)
                    showRestoreFinishedPopup(id)
                }

                function onLocalFailure(error) {
                    errorCallback(error)
                }

                function onServerFailure(error) {
                    if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                        props.history.push('/login?redirect=' + window.location.pathname);
                    } else {
                        props.history.push("/forbidden");
                    }
                    errorCallback(error)
                }

                const config = {
                    headers: {
                        "Content-Type": "application/vnd.api+json",
                    }
                }

                const patchData = {
                    "data": {
                        "type": 'repoItem',
                        "id": id,
                        "attributes": {
                            "status": "Draft",
                            "isArchived": false
                        }
                    }
                };

                Api.patch(`repoItems/${id}`, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData);
            }
        )
    }

    function showRestoreFinishedPopup(id) {
        VerificationPopup.show(
            t("verification.unarchived.title"), t("verification.unarchived.message"), () => {
                props.history.push('/publications/' + id)
            })
    }

    return  <div className={"search-result-row"}>
        <a href={'/publications/' + props.id}>
            <div className={'row-content'}>
                <div className={"icon-container"}>
                    <FontAwesomeIcon icon={faFileInvoice}/>
                </div>
                <div className={"row-information"}>
                    <div className={"search-result-title"}>
                        {props.title}
                    </div>
                    <div className={"search-result-subtitle"}>
                        {t('search.' + props.repoType.toLowerCase())}
                    </div>
                </div>

                <div className={"row-date"}>

                    {permissionCanArchive &&
                        <FontAwesomeIcon icon={faUndo} className={"row-icon"} onClick={(e) => {
                            e.preventDefault()
                            if (permissionCanArchive){
                                undoArchive(props.id, (error) => {
                                    Toaster.showServerError(error)
                                })
                            }
                        }}/>
                    }
                    {RepoItemHelper.getLastEditedDate(props.lastEdited)}
                </div>
            </div>
        </a>
    </div>
}

export default ArchiveResultRow