import React, {useEffect, useState} from "react";
import './dropdown.scss'
import Select, {components} from 'react-select'
import Constants from '../../sass/theme/_constants.scss'
import {faChevronDown} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {useTranslation} from "react-i18next";
import CopyrightIcon from '../../resources/icons/ic-copyright.svg'
import ChevronDownBlackIcon from '../../resources/icons/ic-chevron-down-black.svg'
import OpenAccessIcon from '../../resources/icons/ic-open-access.png'
import RestrictedAccessIcon from '../../resources/icons/ic-restricted-access.png'
import ClosedAccessIcon from '../../resources/icons/ic-closed-access.png'
import {SetCopiedMetaField} from "../../util/events/Events";
import {useFormFieldRegistration} from "../../util/hooks/useFormFieldRegistration";

//'Right of Use' dropdown images
import AllRightsIcon from '../../resources/icons/rightofusedropdown/allrights.svg'
import CCBY from '../../resources/icons/rightofusedropdown/cc-by.svg'
import CCBY0 from '../../resources/icons/rightofusedropdown/cc-by-0.svg'
import CCBYNC from '../../resources/icons/rightofusedropdown/cc-by-nc.svg'
import CCBYNCND from '../../resources/icons/rightofusedropdown/cc-by-nc-nd.svg'
import CCBYNCSA from '../../resources/icons/rightofusedropdown/cc-by-nc-sa.svg'
import CCBYND from '../../resources/icons/rightofusedropdown/cc-by-nd.svg'
import CCBYSA from '../../resources/icons/rightofusedropdown/cc-by-sa.svg'
import PublicDomain from '../../resources/icons/rightofusedropdown/publicdomain.svg'
import VideoAndSound from '../../resources/icons/rightofusedropdown/videoandsound.svg'
import Youtube from '../../resources/icons/rightofusedropdown/youtube.svg'
import styled from "styled-components";

function Dropdown(props) {
    const {t} = useTranslation();
    const isRightOfUseDropdown = (props.type === 'rightofusedropdown')

    const hasObjectDefaultValue = props.defaultValue && typeof props.defaultValue === 'object';
    const defaultValueId = hasObjectDefaultValue
        ? (props.defaultValue.id ?? props.defaultValue.value ?? props.defaultValue?.optionKey ?? null)
        : props.defaultValue ?? null;

    const defaultSummary = hasObjectDefaultValue ? props.defaultValue.summary : null;
    const summaryLabelNL = defaultSummary?.labelNL || defaultSummary?.value || defaultSummary?.title || null;
    const summaryLabelEN = defaultSummary?.labelEN || defaultSummary?.value || defaultSummary?.title || null;
    const summaryOption = (summaryLabelNL || summaryLabelEN) && defaultValueId ? {
        value: defaultValueId,
        labelNL: summaryLabelNL,
        labelEN: summaryLabelEN,
        label: t('language.current_code') === 'nl' ? summaryLabelNL : summaryLabelEN
    } : null;

    let selectedOption = (props.options ?? []).find((option) => option.value === defaultValueId) || summaryOption;

    const [defaultOption, setDefaultOption] = useState(selectedOption);
    const [selectedOptionValue, setSelectedOptionValue] = useState(selectedOption ? selectedOption.value : null)
    const borderColor = props.borderColor ?? Constants.inputBorderColor;
    const currentSelection = getSelectedOption() || defaultOption;

    const style = {
        control: (base, state) => ({
                ...base,
                border: '1px solid ' + (state.isFocused ? Constants.majorelle : borderColor) + ' !important',
                boxShadow: 'none'
        }),
        option: (base) => ({
            ...base,
            backgroundColor: undefined,
            color: undefined,
            ':active': { backgroundColor: undefined },
            ...(isRightOfUseDropdown ? { padding: '4px 12px' } : {})
        }),
        ...(isRightOfUseDropdown && {
            groupHeading: (provided, state) => ({
                ...provided,
                color: 'black',
                fontWeight: '700',
            }),
        })
    };

    useEffect(() => {
        window.addEventListener("SetCopiedMetaField", handleCopyMetaFieldValue);
        return () => window.removeEventListener("SetCopiedMetaField", handleCopyMetaFieldValue);
    }, []);

    function handleCopyMetaFieldValue(event) {
        if (event && event.data && event.data.key) {
            if (event.data.key === props.name) {
                setSelectedOptionValue(event.data.value)
                if (props.setValue) {
                    props.setValue(event.data.key, event.data.value, { shouldValidate: true, shouldDirty: true });
                }
            }
        }
    }

    const IconComponent = (iconName) => {
        const upperCaseIconName = iconName.toUpperCase();

        const iconMap = {
            'OPENACCESS': OpenAccessIcon,
            'RESTRICTEDACCESS': RestrictedAccessIcon,
            'CLOSEDACCESS': ClosedAccessIcon,
            'ALLRIGHTS': AllRightsIcon,
            'CC-BY': CCBY,
            'CC-BY-0': CCBY0,
            'CC-BY-NC': CCBYNC,
            'CC-BY-NC-ND': CCBYNCND,
            'CC-BY-NC-SA': CCBYNCSA,
            'CC-BY-ND': CCBYND,
            'CC-BY-SA': CCBYSA,
            'PUBLICDOMAIN': PublicDomain,
            'VIDEOANDSOUND': VideoAndSound,
            'YOUTUBE': Youtube
        };

        if (iconMap.hasOwnProperty(upperCaseIconName)) {
            return iconMap[upperCaseIconName]
        }
    };

    const CustomSelectElement = (props) => (
        <span>
            {t('language.current_code') === 'nl' ? props.data.labelNL : props.data.labelEN}
        </span>
    );

    const DropdownChevronIcon = () => {
        return <img src={ChevronDownBlackIcon}/>;
    };

    const DropdownIndicator = props => {
        return (
            <components.DropdownIndicator {...props}>
                <DropdownChevronIcon/>
            </components.DropdownIndicator>
        );
    };

    const Control = ({children, ...props}) => {
        return (
            <components.Control {...props}>
                <FlexContainer>
                    { currentSelection && currentSelection.icon !== null &&
                        <div>
                            <img src={IconComponent(currentSelection.icon)}/>
                            <Divider />
                        </div>
                    }
                    {children}
                </FlexContainer>
            </components.Control>
        );
    };

    let options = [];
    if(props.readonly) {
        options = [{
            value: null,
            labelNL: '-',
            labelEN: '-',
        }].concat(props.options);
    } else {
        if (props.allowNullValue) {
            options = [{
                value: null,
                label: 'Selecteer...',
                labelNL: 'Selecteer...',
                labelEN: 'Select...',
                coalescedLabelNL: 'Selecteer...',
                coalescedLabelEN: 'Select...',
                metafieldOptioncategory: '',
                categorySort: 0
            }].concat(props.options);
        } else {
            options = props.options
            if (!selectedOption) {
                selectedOption = options[0]
            }
        }
    }

    function getSelectedOption(options) {
        const optionList = options ? options : props.options;
        const matchedOption = optionList?.find((option) => option.value === selectedOptionValue);

        if (matchedOption) {
            return matchedOption;
        }

        if (defaultOption && defaultOption.value === selectedOptionValue) {
            return defaultOption;
        }

        return null;
    }

    const getGroupedOptions = (options) => {
        const groupedOptions = {};

        options.forEach(option => {
            const category = option.metafieldOptionCategory || t('dropdown_field.other');
            const label = t('language.current_code') === 'nl' ? option.labelNL : option.labelEN;

            if (!groupedOptions[category]) {
                groupedOptions[category] = [];
            }

            const optionWithMergedLabel = { ...option, label };
            groupedOptions[category].push(optionWithMergedLabel);
        });

        const sortedCategories = Object.keys(groupedOptions).sort((a, b) => {
            if (a === t('dropdown_field.other')) return 1;
            if (b === t('dropdown_field.other')) return -1;

            const categoryASort = Math.min(...groupedOptions[a].map(option => option.categorySort));
            const categoryBSort = Math.min(...groupedOptions[b].map(option => option.categorySort));
            return categoryASort - categoryBSort;
        });

        return sortedCategories.map(category => ({
            label: category,
            options: groupedOptions[category].sort((a, b) => a.categorySort - b.categorySort)
        }));
    }

    function getOptions() {
        return options.map(option => {
            option.label = t('language.current_code') === 'nl' ? option.labelNL : option.labelEN;
            return option;
        })
    }

    if(props.isRequired && props.options.length === 1) {
        selectedOption = options[1]
        if(!defaultOption) {
            setDefaultOption(selectedOption)
        }
    }

    // Use the custom hook for form field registration
    const { hiddenInput } = useFormFieldRegistration(props, () => selectedOptionValue);

    let placeholder = (props.readonly) ? "-" : t('dropdown_field.placeholder')

    return (
        <div className={`dropdown-container ` + props.classNameSuffix}>
            {/* Hidden input for react-hook-form registration */}
            {hiddenInput}
            
            <Select
                key={defaultOption}
                isDisabled={props.readonly}
                className={"surf-dropdown" + ((props.readonly) ? " readonly" : "")}
                classNamePrefix="surf-select"
                components={{
                    ...(isRightOfUseDropdown ? { Control } : {}),
                    DropdownIndicator,
                    IndicatorSeparator: () => null,
                    SingleValue: CustomSelectElement
                }}
                defaultValue={defaultOption}
                isSearchable={props.isSearchable}
                options={isRightOfUseDropdown ? getGroupedOptions(options) : props.disableDefaultSort === true ? getOptions() : getSortedOptions(options)}
                placeholder={placeholder}
                styles={style}
                formatOptionLabel={option => (
                    <div className={`align-center ${isRightOfUseDropdown ? 'flex-row' : 'flex-row-reverse'}`}>
                        <span>{option.label}</span>
                        {option.icon && <img className={"option-image"} src={IconComponent(option.icon)} alt=""/>}
                    </div>
                )}
                value={(() => {
                    const selected = getSelectedOption(options) || defaultOption;
                    if (selected) {
                        return selected;
                    }
                    return options && options.length > 0 ? options[0] : null;
                })()}
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

const Divider = styled.div`
  width: 1px;
  height: 30px; 
  background-color: #ccc;
`

const FlexContainer = styled.div`
    width: 100%;
    display: flex;
    flex-direction: row;
    gap: 8px;
    max-height: 50px !important;
    
    div {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    img {
        max-height: 15px;
    }
    
    span {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        overflow: hidden;
    }
    
    input {
        position: absolute;
    }
`

export default Dropdown;
