import React, {useEffect, useState} from "react";
import './dropdown.scss'
import Select, {components} from 'react-select'
import Constants from '../../sass/theme/_constants.scss'
import {faChevronDown} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {useTranslation} from "react-i18next";

function Dropdown(props) {
    const {t} = useTranslation();

    let selectedOption = (props.options ?? []).find((option) => {
        return option.value === props.defaultValue
    });

    const [defaultOption, setDefaultOption] = useState(selectedOption);
    const [selectedOptionValue, setSelectedOptionValue] = useState(selectedOption ? selectedOption.value : null)
    const borderColor = props.borderColor ?? Constants.inputBorderColor;

    const style = {
        control: (base, state) => ({
            ...base,
            border: '1px solid ' + (state.isFocused ? Constants.majorelle : borderColor) + ' !important', //else will be overwritten by field-input style
            boxShadow: 'none'
        })
    };

    useEffect(() => {
        if (props.register) {
            props.register({name: props.name}, {required: props.isRequired})

            if(selectedOptionValue !== undefined && selectedOptionValue !== null) {
                props.setValue(props.name, selectedOptionValue)
            } else if (defaultOption !== undefined && defaultOption.value !== null){
                props.setValue(props.name, defaultOption.value)
            } else {
                props.setValue(props.name, null)
            }
        }
    }, [props.register, defaultOption])

    const CustomSelectElement = props2 => (
        <div className={"surf-select__custom-select-container"}>
            {props.icon && (<div className={"surf-select__icon-wrapper"}>
                {props.icon}
            </div>)}
            <div className={"surf-select__text-wrapper"}>
                {t('language.current_code') === 'nl' ? props2.data.labelNL : props2.data.labelEN}
            </div>
        </div>
    );

    const DropdownChevronIcon = () => {
        return <FontAwesomeIcon icon={faChevronDown}/>;
    };

    const DropdownIndicator = props => {
        return (
            <components.DropdownIndicator {...props}>
                <DropdownChevronIcon/>
            </components.DropdownIndicator>
        );
    };

    let options = [];
    if(props.readonly) {
        options = [{
            value: null,
            labelNL: '-',
            labelEN: '-'
        }].concat(props.options);
    } else {
        if (props.allowNullValue) {
            options = [{
                value: null,
                labelNL: 'Selecteer...',
                labelEN: 'Select...'
            }].concat(props.options);
        } else {
            options = props.options
            if (!selectedOption) {
                selectedOption = options[0]
            }
        }
    }

    if(props.isRequired && props.options.length === 1) {
        selectedOption = options[1]
        if(!defaultOption) {
            setDefaultOption(selectedOption)
        }
    }

    let placeholder = (props.readonly) ? "-" : t('dropdown_field.placeholder')

    return (
        <div className={"dropdown-container " + props.classNameSuffix}>
            <Select
                key={defaultOption}
                isDisabled={props.readonly}
                className={"surf-dropdown" + ((props.readonly) ? " readonly" : "")}
                classNamePrefix="surf-select"
                components={{
                    DropdownIndicator,
                    IndicatorSeparator: () => null,
                    SingleValue: CustomSelectElement
                }}
                defaultValue={defaultOption}
                isSearchable={props.isSearchable}
                options={props.disableDefaultSort === true ? getOptions() : getSortedOptions(options)}
                placeholder={placeholder}
                styles={style}
                onChange={
                    (selection) => {
                        if (selection) {
                            setSelectedOptionValue(selection.value);
                            if (props.setValue) {
                                props.setValue(props.name, selection.value, {shouldValidate: true, shouldDirty: true})
                            }

                            props.onChange(selection.value)
                        } else {
                            setSelectedOptionValue(null);
                            if (props.setValue) {
                                props.setValue(props.name, null, {shouldValidate: true,  shouldDirty: true})
                            }
                            props.onChange(null)
                        }
                    }
                }
            />
        </div>
    );

    function getOptions() {
        return options.map(option => {
            option.label = t('language.current_code') === 'nl' ? option.labelNL : option.labelEN;
            return option;
        })
    }

    function getSortedOptions(optionsToSort) {

        let emptyOption = null
        let mappedOptions = []
        optionsToSort.forEach(option => {
            option.label = t('language.current_code') === 'nl' ? option.labelNL : option.labelEN;
            if(option.value === null) {
                emptyOption = option
            } else {
                mappedOptions.push(option)
            }
        })

        let sortedOptions = mappedOptions.sort((a, b) => {
            const textA = a.label?.toLowerCase() ?? ''
            const textB = b.label?.toLowerCase() ?? ''
            return (textA < textB) ? -1 : (textA > textB) ? 1 : 0;
        })

        if(emptyOption !== null) {
            sortedOptions.unshift(emptyOption);
        }

        return sortedOptions
    }
}

export default Dropdown;