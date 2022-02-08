import React from "react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faFileInvoice, faTrashRestore, faUsers} from "@fortawesome/free-solid-svg-icons";
import './trashcanresultsrow.scss'
import {ProfileBanner} from "../components/profilebanner/ProfileBanner";
import Toaster from "../util/toaster/Toaster";
import Api from "../util/api/Api";
import {useHistory} from "react-router-dom";
import RepoItemHelper from "../util/RepoItemHelper";

export function TrashcanResultRow(props) {
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
                    {RepoItemHelper.getLastEditedDate(props.lastEdited)}
                </div>

                {props.permissions.canDelete
                &&
                <div className={"trash-icon-wrapper"} onClick={() => {
                    if (props.permissions.canDelete) {
                        restore(props.type, props.id)
                    }
                }}>
                    <FontAwesomeIcon icon={faTrashRestore}/>
                </div>
                }
            </div>
        </div>
    )

    function restore(type, id) {
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
    }
}