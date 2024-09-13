import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import React, {useState} from "react";
import "./addpersontogrouppopup.scss"
import '../../components/field/formfield.scss'
import {useTranslation} from "react-i18next";
import SearchAndSelectPersonTable from "../../components/searchandselectpersontable/SearchAndSelectPersonTable";

export function AddPersonToGroupPopupContent(props) {
    const {t} = useTranslation()
    const [selectedPerson, setSelectedPerson] = useState(null);

    let popupContent = ([
        <div key={"add-person-title"} className={"add-person-layer-title"}>
            <h3>{t('group.add_person_popup.title')}</h3>
            <SearchAndSelectPersonTable
                onPersonSelect={(person) => setSelectedPerson(person)}
                selectedPersons={selectedPerson ? [selectedPerson] : []}
                onAddButtonClick={() => {
                    props.onAddButtonClick(selectedPerson)
                }}
                buttonText={t('action.add')}
                defaultParams={{'filter[group][NEQ]': props.groupToAddTo.id}}
                allowOutsideScope={false}
                multiSelect={false}
            />
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