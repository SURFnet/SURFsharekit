import React, {useEffect, useRef, useState} from "react";
import './multiselectsuborganisation.scss'
import {components} from 'react-select'
import AsyncSelect from 'react-select/async'
import Constants from '../../../sass/theme/_constants.scss'
import {faChevronDown} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {useTranslation} from "react-i18next";
import Api from "../../../util/api/Api";
import {OrganisationStatusLabel} from "../../../organisation/OrganisationStatusLabel";
import debounce from "debounce-promise";
import {SwitchField} from "../switch/Switch";
import {useNavigation} from "../../../providers/NavigationProvider";
import {useFormFieldRegistration} from "../../../util/hooks/useFormFieldRegistration";

function MultiSelectSuborganisation(props) {
    const {t} = useTranslation();
    const navigate = useNavigation();
    const [selectedOptionValues, setSelectedOptionValues] = useState(getInitialValues())
    const isFirstLoad = useRef(true);
    const [showInactive, setShowInactive] = useState(0);
    const loadOptions = inputValue => promiseOptions(inputValue)
    const debouncedLoadOptions = debounce(loadOptions, 1000)

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

    const style = {
        control: (base, state) => ({
            ...base,
            border: '1px solid ' + (state.isFocused ? Constants.majorelle : borderColor) + ' !important',
            boxShadow: 'none'
        }),
        input: (base, state) => ({
            ...base,
            display: (props.readonly) ? 'none' : 'flex',
            margin: 'auto 0', // remove default margin
            padding: 0,
            fontSize: '12px',
            fontFamily: 'Open Sans, sans-serif'
        })
    };

    useEffect(() => {
        if (props.setValue) {
            props.setValue(props.name, getOptionValues(), {shouldDirty: true})
            props.onChange(getOptionValues())
        }
    }, [selectedOptionValues])

    function getInitialValues() {
        if(props.defaultValue && props.defaultValue.length > 0 && props.defaultValue.every(item => item !== null)) {
            return props.defaultValue.map(optionData => {
                const summaryTitle = optionData?.summary?.title ?? t('organisation.unknown');
                return {
                    value: optionData.id,
                    optionTitle: summaryTitle,
                    optionLabel: null,
                    isRemoved: null,
                    label: summaryTitle,
                }
            });
        }

        return [];
    }

    function getOptionValues() {
        const parseSelectedOptionValues = () => {
            if(selectedOptionValues) {
                return selectedOptionValues.map(selectedOptionValue => {
                    return JSON.stringify({
                        id: selectedOptionValue.value,
                        summary: {
                            title: selectedOptionValue.label,
                        }
                    })
                })
            }
            return null
        }

        let noDefaultOrSelectedOptionValues = (!props.defaultValue || props.defaultValue.length === 0) && (!selectedOptionValues || selectedOptionValues.length === 0)
        return (noDefaultOrSelectedOptionValues ? null : parseSelectedOptionValues());
    }

    // Use the custom hook for form field registration
    const { hiddenInput } = useFormFieldRegistration(props, getOptionValues);

    const DropdownChevronIcon = () => {
        return <FontAwesomeIcon icon={faChevronDown}/>;
    };

    const DropdownIndicator = dropdownProps => {
        return (
            <components.DropdownIndicator {...dropdownProps}>
                <DropdownChevronIcon/>
            </components.DropdownIndicator>
        );
    };

    const Option = optionProps => {

        let inactiveOption;
        if(optionProps.data.isRemoved) {
            inactiveOption = <span>({t('multi_select_suborganisation_field.inactive')})</span>
        }

        return (
            <components.Option {...optionProps}>
                <div className={"options-container"}>
                    <div className={"option-title-container"}>
                        <div className={"option-title"}>{optionProps.data.optionTitle}</div>
                        <div className={"option-inactive"}>{inactiveOption}</div>
                    </div>
                    <OrganisationStatusLabel level={optionProps.data.optionLabel} partOfConsortium={false}/>
                </div>
            </components.Option>
        );
    };

    const promiseOptions = inputValue =>
        new Promise(resolve => {
            getSuborganisations(inputValue, (data) => {
                const options = data.filter(o => o != null).map(optionData => {
                    const summaryTitle = optionData?.summary?.title ?? t('organisation.unknown');
                    return {
                        value: optionData.id,
                        optionTitle: summaryTitle,
                        optionLabel: optionData.level,
                        isRemoved: optionData.isRemoved,
                        label: summaryTitle,
                    }
                })
                resolve(options)
            })
        });

    let placeholder = (props.readonly) ? "-" : t('multi_select_suborganisation_field.placeholder')

    return (
        <div className={"multi-select-suborganisation-container" + classAddition}>
            {/* Hidden input for react-hook-form registration */}
            {hiddenInput}
            
            {!props.readonly && props.showInactiveSwitch && <div className={"inactive-switch"}>
                <div className={"switch-row-text"}>
                    <h5 className={"bold-text"}>{t("organisation.tab_organizational_inactive")}</h5>
                </div>
                <SwitchField defaultValue={showInactive}
                             onChange={setShowInactive}/>
            </div>}

            {/* https://stackoverflow.com/questions/54107238/clear-cached-options-on-async-select*/}
            <AsyncSelect
                key={props.name + t('language.current_code') + showInactive}
                isDisabled={props.readonly}
                className="surf-multi-select-suborganisation"
                classNamePrefix="surf-multi-select"
                components={{
                    DropdownIndicator,
                    IndicatorSeparator: () => null,
                    Option
                }}
                defaultValue={selectedOptionValues}
                cacheOptions={true}
                defaultOptions={true}
                loadOptions={inputValue => debouncedLoadOptions(inputValue)}
                isSearchable={true}
                isMulti={true}
                placeholder={placeholder}
                styles={style}
                onChange={
                    (selection) => {
                        if(selection && selection.length > 0) {
                            setSelectedOptionValues(selection);
                        } else {
                            setSelectedOptionValues(null);
                        }
                    }
                }
            />
        </div>
    );

    function getSuborganisations(searchQuery, callback) {

        function onValidate(response) {
        }

        function onSuccess(response) {
            callback(response.data)
        }

        function onLocalFailure(error) {
            callback([]);
        }

        function onServerFailure(error) {
            callback([])
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        const config = {
            params: {
                'filter[level]': 'discipline,lectorate,department',
                'fields[institutes]': 'isRemoved,title,summary,level',
                'sort': 'title'
            }
        };

        if (!showInactive) {
            config.params['filter[inactive]'] = '0';
        }
        if (searchQuery.length > 0) {
            config.params['filter[title][LIKE]'] = '%' + searchQuery + '%'
        }

        Api.jsonApiGet('institutes', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

export default MultiSelectSuborganisation;