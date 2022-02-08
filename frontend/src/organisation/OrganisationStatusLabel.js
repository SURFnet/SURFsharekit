import React from "react";
import {useTranslation} from "react-i18next";
import "./organisationstatuslabel.scss"

export function OrganisationStatusLabel(props) {
    const {t} = useTranslation();
    if (!props.level) {
        return "";
    }
    function getStatusText() {
        return t('organisation.level.' + props.level.toLowerCase());
    }

    const color = props.partOfConsortium ? "#64C3A5" : "#7344EE"

    return (
        <div className={"organisation-status-label-wrapper"}>
            <div className={"organisation-status-label-container"}>
                <div className={"organisation-status-label-indicator"} style={{backgroundColor: color}}/>
                <div className={"organisation-status-label-text"}>
                    {getStatusText()}
                </div>
            </div>
        </div>
    )
}