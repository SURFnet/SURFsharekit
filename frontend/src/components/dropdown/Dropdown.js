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
    let selectedOption = (props.options ?? []).find((option) => {
        return option.value === props.defaultValue
    });

    const [defaultOption, setDefaultOption] = useState(selectedOption);
    const [selectedOptionValue, setSelectedOptionValue] = useState(selectedOption ? selectedOption.value : null)
    const borderColor = props.borderColor ?? Constants.inputBorderColor;
    const currentSelection = getSelectedOption()

    const style = {
        control: (base, state) => ({
                ...base,
                border: '1px solid ' + (state.isFocused ? Constants.majorelle : borderColor) + ' !important',
                boxShadow: 'none'
        }),
        ...(props.type === "rightofusedropdown" && {
            groupHeading: (provided, state) => ({
                ...provided,
                color: 'black',
                fontWeight: '700',
            }),
            option: (base) => ({
                ...base,
                padding: '4px 12px'
            })
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

    useEffect(() => {
        if (props.register) {
            props.register({name: props.name}, {required: props.isRequired})

            if (selectedOptionValue !== undefined ) {
                if (selectedOptionValue === null) {
                    props.setValue(props.name, options[0].value)
                } else {
                    props.setValue(props.name, selectedOptionValue)
                }
            } else if (defaultOption !== undefined && defaultOption.value !== null) {
                props.setValue(props.name, defaultOption.value)
            } else {
                props.setValue(props.name, null)
            }
        }
    }, [props.register, defaultOption, selectedOptionValue])

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
                    {
                        currentSelection && currentSelection.icon !== null &&
                        <div style={{display: "flex", flexDirection: "row", gap: "8px"}}>
                            <img src={IconComponent(currentSelection.icon)} alt=""/>
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
        return (options ? options : props.options).find((option) => {
            return option.value === selectedOptionValue
        })
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

    let placeholder = (props.readonly) ? "-" : t('dropdown_field.placeholder')

    return (
        <div className={`dropdown-container ` + props.classNameSuffix}>
            { props.type === "rightofusedropdown" ?
                <Select
                    key={defaultOption}
                    isDisabled={props.readonly}
                    className={"surf-dropdown" + ((props.readonly) ? " readonly" : "")}
                    classNamePrefix="surf-select"
                    components={{
                        Control,
                        DropdownIndicator,
                        IndicatorSeparator: () => null,
                        SingleValue: CustomSelectElement
                    }}
                    defaultValue={defaultOption}
                    isSearchable={props.isSearchable}
                    options={getGroupedOptions(options)}
                    placeholder={placeholder}
                    styles={style}
                    formatOptionLabel={ option => (
                        <div className={"align-center"}>
                            <span>{option.label}</span>
                            {option.icon && <img className={"option-image"} src={IconComponent(option.icon)} alt=""/>}
                        </div>
                    )}
                    value={(selectedOptionValue && selectedOptionValue !== null ) ? getSelectedOption(options) : defaultOption}
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
                :
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
                    formatOptionLabel={ option => (
                        <div className={"align-center"}>
                            {option.icon && <img className={"option-image"} src={IconComponent(option.icon)} alt=""/>}
                            <span>{option.label}</span>
                        </div>
                    )}
                    value={selectedOptionValue ? getSelectedOption(options) : options[0].value}
                    onChange={
                        (selection) => {
                            if (selection) {
                                setSelectedOptionValue(selection.value);
                                if (props.setValue) {
                                    props.setValue(props.name, (selection.value), {shouldValidate: true, shouldDirty: true})
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
            }

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
`

export default Dropdown;