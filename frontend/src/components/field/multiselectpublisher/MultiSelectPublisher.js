import React, {useEffect, useState} from "react";
import './multiselectpublisher.scss';
import {useTranslation} from "react-i18next";
import {useHistory} from "react-router-dom";
import Constants from "../../../sass/theme/_constants.scss";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faChevronDown} from "@fortawesome/free-solid-svg-icons";
import {components} from "react-select";
import {OrganisationStatusLabel} from "../../../organisation/OrganisationStatusLabel";
import debounce from "debounce-promise";
import AsyncSelect from "react-select/async";
import Api from "../../../util/api/Api";
import {SwitchField} from "../switch/Switch";

function MultiSelectPublisher(props) {
    const {t} = useTranslation();
    const history = useHistory();
    const [selectedOptionValues, setSelectedOptionValues] = useState(getInitialValues())
    const [showInactive, setShowInactive] = useState(0);
    const loadOptions = inputValue => promiseOptions(inputValue)
    const debouncedLoadOptions = debounce(loadOptions, 1000)
    const isMultiSelectInstitute =  props.attributeKey?.toLowerCase() === "allowedforinstitute"

    // MultiSelectInstitute logic
    const getInitialShowMultiSelectInstituteField = () => {
        if (props.formState) {
            const accessRightState = Object.values(props.formState).find(state => state.field.attributeKey === 'AccessRight');
            if (accessRightState && accessRightState.state) {
                const selectedOption = accessRightState.field.options.find(o => o.key === accessRightState.state);
                return selectedOption.value === "restrictedaccess";
            }
        }
        return false;
    };
    const [showMultiSelectInstituteField, setShowMultiSelectInstituteField] = useState(getInitialShowMultiSelectInstituteField)

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
        label: (base: state) => ({
            ...base,
            paddingRight: '10px'
        }),
        control: (base, state) => ({
            ...base,
            border: '1px solid ' + (state.isFocused ? Constants.majorelle : borderColor) + ' !important', //else will be overwritten by field-input style
            boxShadow: 'none'
        }),
        input: (base, state) => ({
            ...base,
            display: (props.readonly) ? 'none' : 'block'
        }),

        /** Used to display differently for MultiSelectInstitute */
        multiValueRemove: (base, state) => {
            return (state.data.isFixed && isMultiSelectInstitute) ? {
                ...base,
                display: 'none',
            } : base;
        },
        multiValueLabel: (base, state) => {
            return (state.data.isFixed && isMultiSelectInstitute) ? {
                ...base,
                paddingRight: '12px !important',
            } : base;
        },
        clearIndicator: (base, state) => {
            return (isMultiSelectInstitute) ? {
                ...base,
                display: 'none',
            } : base;
        }
    };

    useEffect(() => {
        if (props.formState) {
            const accessRightState = Object.values(props.formState).find(state => state.field.attributeKey === 'AccessRight')
            if (accessRightState && accessRightState.state) {
                const selectedOption = accessRightState.field.options.find(o => o.key === accessRightState.state)
                setShowMultiSelectInstituteField(selectedOption.value === "restrictedaccess")
            }
        }
    }, [props.formState])

    useEffect(() => {
        if (props.register) {
            props.register({name: props.name}, {required: props.isRequired})
            props.setValue(props.name, getOptionValues())
            props.onChange(getOptionValues())
        }
    }, [props.register])

    useEffect(() => {
        if (props.setValue) {
            props.setValue(props.name, getOptionValues(), {shouldDirty: true})
            props.onChange(getOptionValues())
        }
    }, [selectedOptionValues])

    function getInitialValues() {
        if (props.defaultValue) {
            return props.defaultValue.map((optionData, index) => {
                return {
                    value: optionData.id,
                    optionTitle: optionData.summary.title,
                    optionLabel: null,
                    isRemoved: null,
                    label: optionData.summary.title,
                    isFixed: index === 0
                }
            });
        }

        return [];
    }

    function getOptionValues() {
        const parseSelectedOptionValues = () => {
            if (selectedOptionValues) {
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
        if (optionProps.data.isRemoved) {
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
            getRootOrganisations(inputValue, (data) => {
                const options = data.map(optionData => {
                    return {
                        value: optionData.id,
                        optionTitle: optionData.summary.title,
                        optionLabel: optionData.level,
                        isRemoved: optionData.isRemoved,
                        label: optionData.summary.title,
                        isFixed: false
                    }
                })
                resolve(options)
            })
        });

    let placeholder = (props.readonly) ? "-" : t('multi_select_suborganisation_field.placeholder')

    if (!showMultiSelectInstituteField && isMultiSelectInstitute) {
        return null;
    }

    return (
        <div className={"multi-select-suborganisation-container" + classAddition}>
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
                value={selectedOptionValues}
                isClearable={props.attributeKey?.toLowerCase() === "allowedforinstitute" ? selectedOptionValues.filter((v) => !v.isFixed) : true}
                defaultValue={selectedOptionValues}
                cacheOptions={false}
                defaultOptions={true}
                showInactive={showInactive}
                loadOptions={inputValue => debouncedLoadOptions(inputValue)}
                isSearchable={true}
                isMulti={true}
                placeholder={placeholder}
                styles={style}
                onChange={
                    (selection) => {
                        if (selection && selection.length > 0) {
                            setSelectedOptionValues(selection);
                        } else {
                            setSelectedOptionValues(null);
                        }
                    }
                }
            />
        </div>
    );

    function getRootOrganisations(searchQuery, callback) {

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
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        const config = {
            params: {
                'filter[level]': 'organisation,consortium',
                'fields[institutes]': 'isRemoved,title,summary,level',
                'filter[scope]': 'off',
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

export default MultiSelectPublisher;

