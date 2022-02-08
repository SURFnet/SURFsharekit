import Constants from "../../../sass/theme/_constants.scss";
import Dropdown from "../../dropdown/Dropdown";
import React, {useEffect, useState} from "react";
import Api from "../../../util/api/Api";
import {useTranslation} from "react-i18next";
import {useHistory} from "react-router-dom";

export function LectorateField(props) {
    let classAddition = '';
    classAddition += (props.readonly ? ' disabled readonly' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');
    const [lectorates, setLectorates] = useState(props.defaultValue ? [props.defaultValue] : [])
    const [showError, setShowError] = useState(false)
    const history = useHistory();

    useEffect(() => {
        if (!props.readonly) {
            getLectorates()
        }
    }, [])

    let borderColor = Constants.inputBorderColor;
    if (props.isValid) {
        borderColor = Constants.textColorValid
    } else if (props.hasError) {
        borderColor = Constants.textColorError;
    }

    const {t} = useTranslation()

    const errorElement = <div className={'loading-failed'} onClick={getLectorates}>
        {t('error_message.load_options_failed')}
    </div>

    return <div className={"field-input" + classAddition} style={{padding: 0, border: 0}}>
        <Dropdown
            readonly={props.readonly}
            placeholder={props.placeholder}
            onChange={props.onChange}
            register={props.register}
            allowNullValue={true}
            defaultValue={props.defaultValue ? props.defaultValue.id : undefined}
            disableDefaultSort={false} // Set to true to disable sorting alphabetically
            options={lectorates.map(dis => {
                return {
                    value: dis.id,
                    labelNL: dis.summary.title,
                    labelEN: dis.summary.title
                }
            })}
            setValue={(name, value, options) => {
                const selectedLectorate = lectorates.find(dis => dis.id === value)
                const selectedLectorateValue = selectedLectorate ? JSON.stringify({
                    id: selectedLectorate.id,
                    summary: selectedLectorate.summary
                }) : null
                props.setValue(name, selectedLectorateValue, options)
            }}
            isRequired={props.isRequired}
            borderColor={borderColor}
            name={props.name}
        />
        {showError && errorElement}
    </div>


    function getLectorates() {
        setShowError(false)

        function onValidate(response) {
        }

        function onSuccess(response) {
            setLectorates([...response.data])
        }

        function onLocalFailure(error) {
            setShowError(true)
        }

        function onServerFailure(error) {
            setShowError(true)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        const config = {
            params: {
                'filter[level]': 'lectorate',
                'filter[isRemoved]': 'false',
                'fields[institutes]': 'title,summary'
            }
        };

        Api.jsonApiGet('institutes', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}