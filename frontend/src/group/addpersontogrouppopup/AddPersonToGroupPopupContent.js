import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import React from "react";
import "./addpersontogrouppopup.scss"
import '../../components/field/formfield.scss'
import {useTranslation} from "react-i18next";
import SearchAndSelectPersonTable from "../../components/searchandselectpersontable/SearchAndSelectPersonTable";

export function AddPersonToGroupPopupContent(props) {
    const {t} = useTranslation()
    let popupContent = ([
        <div key={"add-person-title"} className={"add-person-layer-title"}>
            <h3>{t('group.add_person_popup.title')}</h3>
            <SearchAndSelectPersonTable setSelectedPerson={(person) => {props.onPersonSelected(person)}}
                               buttonText={t('action.add')}
                               defaultParams={{'filter[group][NEQ]': props.groupToAddTo.id}}
                               allowOutsideScope={false}/>
        </div>
    ])

    return (
        <div className={"add-person-popup-content-wrapper"}>
            <div className={"add-person-popup-content"}>
                <div className={"close-button-container"}
                     onClick={props.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                {popupContent}
            </div>
        </div>
    )
}