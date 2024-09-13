import React from "react";
import './checkdetailspopup.scss';
import {useTranslation} from "react-i18next";
import {HelperFunctions} from "../../util/HelperFunctions";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";

export function CheckDetailsPopupContent(props) {
    const {t} = useTranslation();

    const dateFormatOptions = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }
    const createdFormatted = HelperFunctions.getDateFormat(props.repoItem.createdLocal, dateFormatOptions)
    const lastEditedFormatted = HelperFunctions.getDateFormat(props.repoItem.lastEditedLocal, dateFormatOptions)

    function CreatedLastEditedPersonLink(props) {
        let personContent
        if (props.person && props.person.permissions && props.person.permissions.canView === true) {
            personContent = <a href={"/profile/" + props.person.id}>{props.person.name}</a>
        } else {
            personContent = <React.Fragment>{props.person.name}</React.Fragment>
        }
        return <div className={"person"}>
            {t("publication.created_last_edited_prefix")}{personContent}
        </div>
    }

    return <div className={`repo-item-details ${props.repoItem.status === "Declined" ? "rejected" : ""}`}>
        <div className={"header-container"}>
            <h4>{t("publication_flow.details")}</h4>
            <div className={"close-button-container"}
                 onClick={props.onCancel}>
                <FontAwesomeIcon icon={faTimes}/>
            </div>
        </div>


        <div className={"section"}>
            <div className={"section-title"}>{t("publication.created")}</div>
            <div
                className={"date"}>{`${createdFormatted.day}-${createdFormatted.month}-${createdFormatted.year} ${createdFormatted.hour}:${createdFormatted.minute}`}</div>
            <CreatedLastEditedPersonLink person={props.repoItem.creator}/>
        </div>

        <div className={"section"}>
            <div className={"section-title"}>{t("publication.lastEdited")}</div>
            <div
                className={"date"}>{`${lastEditedFormatted.day}-${lastEditedFormatted.month}-${lastEditedFormatted.year} ${lastEditedFormatted.hour}:${lastEditedFormatted.minute}`}</div>
            <CreatedLastEditedPersonLink person={props.repoItem.lastEditor}/>
        </div>

        <div className={"section"}>
            <div className={"section-title"}>{t("publication.organisation")}</div>
            <div className={"date"}>{props.repoItem.relatedTo.title}</div>
        </div>

        {/*<div className={"section"}>*/}
        {/*    {props.repoItem.status === "Declined" &&*/}
        {/*        <div className={"decline-reason-text"}*/}
        {/*             onClick={() => VerificationPopup.show(t("publication.decline_reason_popup.title"), props.repoItem.declineReason, () => {*/}
        {/*             }, true, null, true)}>{t("publication.decline_reason")}</div>*/}
        {/*    }*/}
        {/*</div>*/}
    </div>
}