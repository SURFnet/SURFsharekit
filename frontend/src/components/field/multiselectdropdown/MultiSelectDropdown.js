import React, {useEffect, useRef, useState} from "react";
import './multiselectdropdown.scss'
import {components} from 'react-select'
import AsyncSelect from 'react-select/async'
import Constants from '../../../sass/theme/_constants.scss'
import {faChevronDown} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {useTranslation} from "react-i18next";

function MultiSelectDropdown(props) {
    const {t} = useTranslation();
    const inputRef = useRef();
    const [selectedOptionValues, setSelectedOptionValues] = useState(getInitialValues)

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
            border: '1px solid ' + (state.isFocused ? Constants.majorelle : borderColor) + ' !important', //else will be overwritten by field-input style
            boxShadow: 'none'
        }),
        input: (base, state) => ({
            ...base,
            display: (props.readonly) ? 'none' : 'block'
        }),
    };

    useEffect(() => {
        if (props.register) {
            props.register({name: props.name}, {required: props.isRequired})
            props.setValue(props.name, getRealValues())
        }
    }, [props.register])

    useEffect(() => {
        if (props.register) {
            props.register({name: props.name}, {required: props.isRequired})
            props.setValue(props.name, getRealValues(), {shouldDirty: true})
            props.onChange(getRealValues())
        }
    }, [selectedOptionValues])

    function getInitialValues() {
        return props.defaultValue ? (props.defaultValue.map(value => props.options.find(option => value === option.value) ?? value)) : []
    }

    function getRealValues() {
        return ((!props.defaultValue || props.defaultValue.length === 0) && (!selectedOptionValues || selectedOptionValues.length === 0) ? null : selectedOptionValues);
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

    const mockOption = (inputValue) => {
        return {
            label: inputValue,
            labelNL: inputValue,
            labelEN: inputValue,
            value: inputValue,
        }
    }

    const selectMultiple = (values) => {
        values.filter((value, index, self) => self.indexOf(value) === index).forEach((v, i) => {
            setTimeout(() => {
                selectOption(v)
            }, 1 * i)
        })

        resetInputValue();
    }

    const selectOption = (value) => {
        if (selectedOptionValues && selectedOptionValues.map(o => o.value).includes(value.value)) {
            return;
        }

        return inputRef.current?.select?.select?.selectOption(value)
    }

    const resetInputValue = () => {
        if (inputRef.current?.select?.select?.inputRef) {
            setTimeout(() => {
                inputRef.current.select.select.onInputChange('', { action: 'set-value' });
            }, 100)
        }
    }

    const handleInputChange = (inputValue) => {
        if (props.delimiters != null && Array.isArray(props.delimiters)) {
            handleDelimiterInput(inputValue)
        }
    }

    const handleDelimiterInput = (inputValue) => {
        if (inputValue && inputValue.length && props.delimiters.some(s => inputValue.includes(s))) {
            const inputValues = inputValue.split(/,|;/)

            const values = inputValues.map(v => v.trim()).filter(v => v.length > 0).map(v => mockOption(v))
            selectMultiple(values)
        }
    }

    const promiseOptions = (inputValue) => {
        return new Promise(resolve => {
            props.getOptions(inputValue, resultingOptions => {
                const customOptionInList = resultingOptions.find(o => {
                    return (o.label === inputValue || o.labelNL === inputValue || o.labelNL === inputValue)
                })
                if (props.allowCustomOption && customOptionInList === undefined) {
                    resultingOptions = [mockOption(inputValue), ...resultingOptions]
                    resultingOptions = resultingOptions.filter(option => option.value.length > 0)
                } else {
                    //prepend custom option if exists
                    const index = resultingOptions.indexOf(customOptionInList);
                    if (index > -1) {
                        resultingOptions.splice(index, 1);
                        resultingOptions = [customOptionInList, ...resultingOptions];
                    }
                }
                resolve(resultingOptions)
            })
        });
    }

    function defaultValueToOptions(optionValues) {
        return optionValues !== null && optionValues.map(optionValue => {
            return {
                "label": t('language.current_code') === 'nl' ? optionValue.labelNL : optionValue.labelEN,
                "labelNL": optionValue.labelNL,
                "labelEN": optionValue.labelEN,
                "coalescedLabelNL": optionValue.coalescedLabelNL,
                "coalescedLabelEN": optionValue.coalescedLabelEN,
                "metafieldOptionCategory": optionValue.metafieldOptionCategory,
                "value": optionValue.value
            }
        })
    }

    let placeholder = (props.readonly) ? "-" : t('multi_select_dropdown_field.placeholder')

    return (
        <div className={"multi-select-dropdown-container" + classAddition}>
            <AsyncSelect
                ref={inputRef}
                key={props.name + t('language.current_code')}
                isDisabled={props.readonly}
                className="surf-multi-select-dropdown"
                classNamePrefix="surf-multi-select"
                components={{
                    DropdownIndicator,
                    IndicatorSeparator: () => null,
                }}
                defaultValue={defaultValueToOptions(selectedOptionValues)}
                cacheOptions={true}
                defaultOptions={true}
                loadOptions={promiseOptions}
                isSearchable={true}
                isMulti={true}
                placeholder={placeholder}
                styles={style}
                onInputChange={handleInputChange}
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
}


export default MultiSelectDropdown;