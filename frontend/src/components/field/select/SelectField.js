import Constants from "../../../sass/theme/_constants.scss";
import Dropdown from "../../dropdown/Dropdown";
import React, {useEffect, useState} from "react";
import styled from "styled-components";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faChevronDown} from "@fortawesome/free-solid-svg-icons";
import {faInfoCircle} from "@fortawesome/free-solid-svg-icons/faInfoCircle";
import i18n from "i18next";
import {useTranslation} from "react-i18next";
import {WarningMessage, WarningMessageContent, WarningTextContainer} from "../FormField";

export function SelectField(props) {
    const [message, setMessage] = useState(null)
    const {t} = useTranslation()

    let classAddition = '';
    classAddition += (props.readonly ? ' disabled readonly' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');

    let borderColor = Constants.inputBorderColor;
    if (props.isValid) {
        borderColor = Constants.textColorValid
    } else if (props.hasError) {
        borderColor = Constants.textColorError;
    }

    const attributeKey = props.attributeKey && props.attributeKey.toLowerCase()

    const hasMessageContainer = () => {
        switch (props.attributeKey) {
            case "AccessRight": return true
            default: return false
        }
    }

    useEffect(() => {
        if (props.formState) {
            if (props.attributeKey === 'AccessRight') {
                setAccessRightMessage(props.formState)
            }
        }
    }, [props.formState])

    useEffect(() => {
        // Hack to display the information popup for access right field
        setTimeout(() => {
            if (props.attributeKey === "AccessRight") {
                props.setValue(props.name, props.defaultValue)
            }
        }, 0)
    }, [props.defaultValue])

    const setAccessRightMessage = (formState) => {
        const accessRightState = Object.values(formState).find(state => state.field.attributeKey === 'AccessRight')
        if (accessRightState && accessRightState.state) {
            const selectedOption = accessRightState.field.options.find(o => o.key === accessRightState.state)
            if (selectedOption) {
                switch (selectedOption.value.toLowerCase()) {
                    case 'closedaccess':
                        setMessage({
                            'nl': 'Bestanden die als \'niet toegankelijk\' staan aangemerkt, worden niet gepubliceerd.',
                            'en': 'Files marked as \'no access\' are not published.'
                        })
                        break;
                    case 'restrictedaccess':
                        setMessage({
                            'nl': 'Bestanden die als \'beperkt toegankelijk\' staan aangemerkt, zijn alleen zichtbaar binnen SURFsharekit.',
                            'en': 'Files marked as \'limited access\' are only visible within SURFsharekit.'
                        })
                        break;
                    case 'openaccess':
                        setMessage({
                            'nl': 'Bestanden die als \'publiek toegankelijk\' staan aangemerkt, worden publiek zichtbaar via het gekozen kanaal',
                            'en': 'Files marked as \'publicly available\' become publicly visible through the chosen channel.'
                        })
                        break;
                    default:
                        setMessage(undefined)
                }
            }
        }
    }

    return <div className={`field-input${classAddition} ${hasMessageContainer() ? "has-message" : ""}`}
                style={{padding: 0, border: 0}}>
        <Dropdown
            disableDefaultSort={!!props.retainOrder}
            readonly={props.readonly}
            placeholder={props.placeholder}
            onChange={props.onChange}
            register={props.register}
            isReplicatable={props.isReplicatable}
            isSearchable={props.isSearchable}
            allowNullValue={props.type !== "rightofusedropdown"}
            defaultValue={props.defaultValue}
            options={props.options}
            setValue={props.setValue}
            isRequired={props.isRequired}
            borderColor={borderColor}
            name={props.name}
            type={props.type}
        />

        { message &&
            <WarningMessage className={"message"}>
                <FontAwesomeIcon icon={faInfoCircle}/>
                <WarningTextContainer>
                    <WarningMessageContent dangerouslySetInnerHTML={{__html:message[i18n.language] }} />
                    <a href={"https://servicedesk.surf.nl/wiki/pages/viewpage.action?pageId=112592400"} target={"_blank"}>{t("repoitem.popup.more_information")}</a>
                </WarningTextContainer>
            </WarningMessage>
        }
    </div>
}

