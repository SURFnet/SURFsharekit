import React from "react";
import {HelperFunctions} from "../../util/HelperFunctions";
import {useTranslation} from "react-i18next";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";

function ProfileDetailsPopupContent(props) {
    const {t} = useTranslation();

    const dateFormatOptions = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }

    const createdFormatted = HelperFunctions.getDateFormat(props.details.creator.createdLocal, dateFormatOptions)
    const lastEditedFormatted = HelperFunctions.getDateFormat(props.details.lastEditor.lastEditedLocal, dateFormatOptions)

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

    return <div className={'profile-details'}>
        <div className={"header-container"}>
            <h4>Details</h4>
            <div className={"close-button-container"}
                 onClick={props.onCancel}>
                <FontAwesomeIcon icon={faTimes}/>
            </div>
        </div>


        <div className={"section"}>
            <div className={"section-title"}>{t("profile.details_popup.created")}</div>
            <div className={"date"}>{`${createdFormatted.day}-${createdFormatted.month}-${createdFormatted.year} ${createdFormatted.hour}:${createdFormatted.minute}`}</div>
            <CreatedLastEditedPersonLink person={props.details.creator}/>
        </div>

        <div className={"section"}>
            <div className={"section-title"}>{t("profile.details_popup.lastEdited")}</div>
            <div className={"date"}>{`${lastEditedFormatted.day}-${lastEditedFormatted.month}-${lastEditedFormatted.year} ${lastEditedFormatted.hour}:${lastEditedFormatted.minute}`}</div>
            <CreatedLastEditedPersonLink person={props.details.lastEditor}/>
        </div>
    </div>
}

export default ProfileDetailsPopupContent;