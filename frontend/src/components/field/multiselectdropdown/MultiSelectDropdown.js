import React, {useEffect, useRef, useState} from "react";
import './multiselectdropdown.scss'
import {components} from 'react-select'
import AsyncSelect from 'react-select/async'
import Constants from '../../../sass/theme/_constants.scss'
import {faChevronDown} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {useTranslation} from "react-i18next";

import {useFormFieldRegistration} from "../../../util/hooks/useFormFieldRegistration";

function MultiSelectDropdown(props) {
    const {t} = useTranslation();
    const [selectedOptionValues, setSelectedOptionValues] = useState(getInitialValues)

    function getInitialValues() {
        if (!props.defaultValue) {
            return [];
        }

        return props.defaultValue.map((value) => {
            const optionId = value?.id ?? value?.value ?? value;
            const matchingOption = props.options?.find(option => optionId === option.value || optionId === option.id);

            return matchingOption ?? value;
        });
    }

    function getRealValues() {
        return ((!props.defaultValue || props.defaultValue.length === 0) && (!selectedOptionValues || selectedOptionValues.length === 0) ? null : selectedOptionValues);
    }
    const {hiddenInput} = useFormFieldRegistration(
        props,
        () => getRealValues()
    );
    const inputRef = useRef();

    useEffect(() => {
        if (props.setValue) {
            props.setValue(props.name, getRealValues(), {shouldDirty: true})
        }
        if (props.onChange) {
            props.onChange(getRealValues())
        }
    }, [selectedOptionValues, props.defaultValue]);

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
            display: (props.readonly) ? 'none' : 'block',
            fontSize: '12px',
            fontFamily: 'Open Sans, sans-serif'
        })
    };


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
        const uniqueValues = values.filter((value, index, self) => {
            const isUnique = self.findIndex(v => v.value === value.value) === index;
            const isNotSelected = !selectedOptionValues?.some(selected => selected.value === value.value);
            return isUnique && isNotSelected;
        });

        if (uniqueValues.length > 0) {
            const newSelectedValues = [...(selectedOptionValues || []), ...uniqueValues];
            setSelectedOptionValues(newSelectedValues);
        }

        resetInputValue();
    }

    const resetInputValue = () => {
        if (inputRef.current?.inputRef) {
            setTimeout(() => {
                inputRef.current.onInputChange('', { action: 'set-value' });
            }, 100)
        }
    }

    const handleInputChange = (inputValue) => {
        if (props.delimiters != null && Array.isArray(props.delimiters)) {
            handleDelimiterInput(inputValue)
        }
    }

    const handlePaste = (event) => {
        const paste = (event.clipboardData || window.clipboardData).getData('text');
        if (paste && paste.includes(',')) {
            event.preventDefault();
            handleDelimiterInput(paste);
        }
    }

    const handleDelimiterInput = (inputValue) => {
        const hasDelimiters = props.delimiters && props.delimiters.some(s => inputValue.includes(s));
        const isMultiselectDropdownWithComma = props.type === 'multiselectdropdown' && inputValue.includes(',');
        
        if (inputValue && inputValue.length && (hasDelimiters || isMultiselectDropdownWithComma)) {
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

    function getSummaryLabel(optionValue, languageCode) {
        if (!optionValue || typeof optionValue !== 'object') {
            return null;
        }
        const summary = optionValue.summary || {};
        const localizedLabel = languageCode === 'nl' ? summary.labelNL : summary.labelEN;
        return localizedLabel || summary.value || summary.title || null;
    }

    function valueToOptions(optionValues) {
        if (!optionValues || optionValues.length === 0) {
            return [];
        }

        return optionValues
            .filter(optionValue => optionValue != null)
            .map(optionValue => {
                const summaryLabelNL = getSummaryLabel(optionValue, 'nl');
                const summaryLabelEN = getSummaryLabel(optionValue, 'en');
                const labelNL = optionValue?.labelNL || optionValue?.label || summaryLabelNL || summaryLabelEN || optionValue;
                const labelEN = optionValue?.labelEN || optionValue?.label || summaryLabelEN || summaryLabelNL || optionValue;
                const value = optionValue?.id ?? optionValue?.value ?? optionValue?.optionKey ?? optionValue;

                return {
                    "label": t('language.current_code') === 'nl' ? labelNL : labelEN,
                    "labelNL": labelNL,
                    "labelEN": labelEN,
                    "coalescedLabelNL": optionValue?.coalescedLabelNL,
                    "coalescedLabelEN": optionValue?.coalescedLabelEN,
                    "metafieldOptionCategory": optionValue?.metafieldOptionCategory,
                    "value": value,
                    "summary": optionValue?.summary
                }
            });
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
                value={valueToOptions(selectedOptionValues)}
                defaultValue={valueToOptions(selectedOptionValues)}
                cacheOptions={true}
                defaultOptions={true}
                loadOptions={promiseOptions}
                isSearchable={true}
                isMulti={true}
                placeholder={placeholder}
                styles={style}
                onInputChange={handleInputChange}
                onPaste={handlePaste}
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
            {hiddenInput}
        </div>
    );
}


export default MultiSelectDropdown;
