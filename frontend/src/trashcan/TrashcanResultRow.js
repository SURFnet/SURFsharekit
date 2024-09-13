import React from "react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faEraser, faFileInvoice, faTrashRestore, faUsers} from "@fortawesome/free-solid-svg-icons";
import './trashcanresultsrow.scss'
import {ProfileBanner} from "../components/profilebanner/ProfileBanner";
import Toaster from "../util/toaster/Toaster";
import Api from "../util/api/Api";
import {useHistory} from "react-router-dom";
import RepoItemHelper from "../util/RepoItemHelper";
import VerificationPopup from "../verification/VerificationPopup";
import {useTranslation} from "react-i18next";
import {GlobalPageMethods} from "../components/page/Page";

export function TrashcanResultRow(props) {
    const {t} = useTranslation();
    const history = useHistory();

    function SearchResultIcon() {
        let iconContent;

        if (props.type.toLowerCase() === 'person') {
            iconContent = <ProfileBanner imageUrl={undefined}/>
        } else {
            let icon;
            switch (props.type.toLowerCase()) {
                case 'repoitem':
                    icon = faFileInvoice
                    break;
                case 'group':
                    icon = faUsers
                    break;
            }
            iconContent = <FontAwesomeIcon icon={icon}/>
        }

        return (
            <div className={"icon-container"}>
                {iconContent}
            </div>
        )
    }

    return (
        <div className={"search-result-row"}>
            <a href={'/publications/' + props.id}
               onClick={(e) => props.type === "repoItem" ? false : e.preventDefault()}
               className={props.type === "repoItem" ? "" : "no-pointer"}>
                <div className={"row-content"}>
                    <SearchResultIcon/>
                    <div className={"row-information"}>
                        <div className={"search-result-title"}>
                            {props.title}
                        </div>
                        <div className={"search-result-subtitle"}>
                            {props.subtitle}
                        </div>
                    </div>

                    <div className={"row-date"}>
                        {props.permissions.canDelete &&
                        <>
                            {props.type === 'repoItem' &&
                            <FontAwesomeIcon className={"row-icon"} icon={faEraser} onClick={(e) => {
                                e.preventDefault()
                                VerificationPopup.show(
                                    t('verification.repoItem.delete_from_trash.title'),
                                    t('verification.repoItem.delete_from_trash.subtitle'),
                                    () => {
                                        if (props.permissions.canDelete) {
                                            destroy(props.type, props.id)
                                        }
                                    })
                            }}/>}

                            <FontAwesomeIcon icon={faTrashRestore} className={"row-icon"} onClick={(e) => {
                                e.preventDefault()
                                if (props.permissions.canDelete) {
                                    restore(props.type, props.id)
                                }
                            }}/>
                        </>
                        }
                        {RepoItemHelper.getLastEditedDate(props.lastEdited)}
                    </div>
                </div>
            </a>
        </div>
    )

    function verifyRestore(type, id) {
        VerificationPopup.show(t("verification.restore.title"), "", () => {
            restore(type, id)
        })
    }

    function showRestoreFinishedPopup(type, id) {
        VerificationPopup.show(t("verification.restored.title"), t("verification.restored.message"), () => {
            switch (type) {
                case 'repoItem':
                    history.push('/publications/' + id)
                    break;
                case 'group':
                    history.push('/groups/' + id)
                    break;
                case 'person':
                    history.push('/profile/' + id)
                    break;
                default:
                    return;
            }
        })
    }

    function restore(type, id) {
        VerificationPopup.show(t("verification.restore.title"), "", () => {
            let endPoint = '';
            switch (type) {
                case 'repoItem':
                    endPoint = 'repoItems';
                    break;
                case 'group':
                    endPoint = 'groups';
                    break;
                case 'person':
                    endPoint = 'persons';
                    break;
                default:
                    return;
            }

            function onValidate(response) {
            }

            function onSuccess(response) {
                props.onReload()
                GlobalPageMethods.setFullScreenLoading(false)
                showRestoreFinishedPopup(type, id)
            }

            function onLocalFailure(error) {
                Toaster.showDefaultRequestError()
                console.log(error);
            }

            function onServerFailure(error) {
                console.log(error);
                if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                    history.push('/login?redirect=' + window.location.pathname);
                } else {
                    history.push("/forbidden");
                }
            }

            const config = {
                headers: {
                    "Content-Type": "application/vnd.api+json",
                }
            }

            const patchData = {
                "data": {
                    "type": type,
                    "id": id,
                    "attributes": {
                        "isRemoved": false
                    }
                }
            };

            Api.patch(`${endPoint}/${id}`, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData);
        })
    }

    function destroy(type, id) {
        function onSuccess(response) {
            props.onReload()
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
            console.log(error);
        }

        function onServerFailure(error) {
            console.log(error);
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            } else {
                history.push("/forbidden");
            }
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            },
            data: {
                data: [
                    {
                        type: type,
                        id: id
                    }
                ]
            }
        }

        Api.delete(`repoItems/${id}`, () => {
        }, onSuccess, onLocalFailure, onServerFailure, config);
    }
}