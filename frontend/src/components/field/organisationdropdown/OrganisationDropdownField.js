import Constants from "../../../sass/theme/_constants.scss";
import Dropdown from "../../dropdown/Dropdown";
import React, {useEffect, useState} from "react";
import Api from "../../../util/api/Api";
import {useTranslation} from "react-i18next";
import {useNavigation} from "../../../providers/NavigationProvider";

export function OrganisationDropdownField(props) {
    let classAddition = '';
    classAddition += (props.readonly ? ' disabled readonly' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');
    const [organisations, setOrganisations] = useState(props.defaultValue ? [props.defaultValue] : [])
    const [showError, setShowError] = useState(false)
    const navigate = useNavigation();

    useEffect(() => {
        if (!props.readonly) {
            getOrganisations()
        }
    }, [])

    let borderColor = Constants.inputBorderColor;
    if (props.isValid) {
        borderColor = Constants.textColorValid
    } else if (props.hasError) {
        borderColor = Constants.textColorError;
    }

    const {t} = useTranslation()

    const errorElement = <div className={'loading-failed'} onClick={getOrganisations}>
        {t('error_message.load_options_failed')}
    </div>

    return <div className={"field-input" + classAddition} style={{padding: 0, border: 0}}>
        <Dropdown
            readonly={props.readonly}
            placeholder={props.placeholder}
            onChange={props.onChange}
            register={props.register}
            allowNullValue={true}
            disableDefaultSort={true}
            isSearchable={props.isSearchable}
            defaultValue={props.defaultValue ? props.defaultValue.id : undefined}
            options={organisations.filter(o => o != null).map(organisation => {
                const label = organisation?.summary?.title ?? t('organisation.unknown');
                return {
                    value: organisation.id,
                    labelNL: label,
                    labelEN: label
                }
            })}
            setValue={(name, value, shouldValidate) => {
                const selectedOrganisation = organisations.find(organisation => organisation.id === value)
                props.setValue(
                    name,
                    selectedOrganisation ? selectedOrganisation.id : null,
                    shouldValidate
                )
            }}
            isRequired={props.isRequired}
            borderColor={borderColor}
            name={props.name}
        />
        {showError && errorElement}
    </div>


    function getOrganisations() {
        setShowError(false)

        function onValidate(response) {
        }

        function onSuccess(response) {
            setOrganisations([...response.data])
        }

        function onLocalFailure(error) {
            console.log(error);
            setShowError(true)
        }

        function onServerFailure(error) {
            console.log(error);
            setShowError(true)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        const config = {
            params: {
                'filter[level]': 'organisation,consortium',
                'filter[scope]': props.onlyScopedInstitutes ? 'on' : 'off',
                'filter[isRemoved]': 'false',
                'fields[institutes]': 'title,summary',
                'page[size]': 200
            }
        };

        Api.jsonApiGet('institutes', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}