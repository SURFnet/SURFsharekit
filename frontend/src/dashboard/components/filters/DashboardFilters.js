import IconButton from "../../../components/buttons/iconbutton/IconButton";
import {faFilter} from "@fortawesome/free-solid-svg-icons/faFilter";
import ModalButton, {MODAL_ALIGNMENT, MODAL_VERTICAL_ALIGNMENT} from "../../../styled-components/buttons/ModalButton";
import SURFButton from "../../../styled-components/buttons/SURFButton";
import {flame, majorelle, spaceCadet, SURFShapeLeft, white} from "../../../Mixins";
import {faChevronDown} from "@fortawesome/free-solid-svg-icons";
import React, {useCallback, useEffect, useRef, useState} from "react";
import './dashboardfilters.scss'
import {useTranslation} from "react-i18next";
import styled from "styled-components";
import {RadioGroup} from "../../../components/field/radiogroup/RadioGroup";
import {faTimes} from "@fortawesome/free-solid-svg-icons/faTimes";
import {filter} from "rxjs";
import {Checkbox} from "../../../components/Checkbox";
import {Label} from "../../../components/field/FormField";
import debounce from "debounce-promise";

function DashboardFilters(props) {
    const {i18n, t} = useTranslation();

    const filterRefs = useRef([])

    const [showFilters, setShowFilters] = useState(false)
    const [filterStates, setFilterStates] = useState(null);

    const debounceOnFilterChange = debounce(props.onFilterChange, 300)


    useEffect(() => {
        if (filterStates == null) {
            const initialStates = {};

            for (const filter in props.filters) {
                initialStates[props.filters[filter]] = null
            }

            setFilterStates(initialStates)
        }
    }, [props.filters])

    const updateFilterState = (filter, value) => {
        const newState = Object.assign({}, filterStates)

        newState[filter] = value
        setFilterStates(newState);

        debounceOnFilterChange(newState)
    }

    const getButtonText = (filter) => {
        if (filterStates && filterStates[filter]) {
            const options = getOptions(filter);

            const foundOption = options.find(o => o.value === filterStates[filter])

            return foundOption?.label ?? filterStates[filter]
        }

        return t(`dashboard.tasks.table.filters.${filter}`, filter)
    }

    const toggleShowFilters = () => { setShowFilters(!showFilters) }

    const getOptions = (filter) => {
        const filterOptions = props.filters[filter]?.options ?? [];


        return filterOptions.map((option, index) => {
            const id = `${filter}-${index}`
            const value = option.value
            const label = `${(i18n.exists(option.label) ? t(option.label) : option.label)}`

            return { id, value, label }
        });
    }

    if (props.filters == null) {
        return (
            <text>loading...</text>
        )
    }

    const filterSet = (filter) => {
        return filterStates != null && filterStates[filter] != null;
    }

    const clearState = (event, filter, filterGroupId) => {
        const radioButtons = document.getElementById(filterGroupId).querySelectorAll('input[type="radio"]')

        radioButtons.forEach((radioButton) => {
            radioButton.checked = false;
        })

        updateFilterState(filter, null)
    }

    const resetAll = () => {
        setFilterStates({})

        resetRadioFilters();
        resetCheckboxFilters();

        debounceOnFilterChange({})
    }

    const resetRadioFilters = () => {
        const radioButtons = document.querySelectorAll('.dashboard-filter-group input[type="radio"]')

        radioButtons.forEach((radioButton) => {
            radioButton.checked = false;
        })
    }

    const resetCheckboxFilters = () => {
        const checkboxes = document.querySelectorAll('.filter-checkbox input[type="checkbox"]');

        checkboxes.forEach((checkbox) => {
            // there was no other to fix this...
            if (checkbox.checked) {
                checkbox.click();
            }
        })
    }

    return (
        <>
            <IconButton className={'filter-button'} icon={faFilter} onClick={toggleShowFilters} />
            { showFilters && <div className={'filters-wrapper'}>
                <div className={'filters'}>
                    { Object.keys(props.filters).map( (filter, index) => {

                        if (props.filters[filter].type === 'checkbox') {
                            return <div key={`filter-${filter}`} className={'filter-checkbox'}>
                                <Checkbox
                                    name={`checkbox-filter-${filter}`}
                                    onChange={(value) => {
                                        updateFilterState(filter, value ? value : null)
                                    }}
                                />
                                <Label text={ t(`dashboard.tasks.table.filters.${filter}`, filter) }/>
                            </div>
                        }

                        if (props.filters[filter].type === 'dropdown') {
                            return <ModalButton
                                key={`filter-${filter}`}
                                modalHorizontalAlignment={ index === (props.filters.length - 1) ? MODAL_ALIGNMENT.RIGHT : MODAL_ALIGNMENT.LEFT}
                                modalVerticalAlignment={MODAL_VERTICAL_ALIGNMENT.BOTTOM}
                                modal={
                                    <Modal>
                                        <RadioGroup
                                            className={'dashboard-filter-group'}
                                            id={`dashboard-filter-group-${filter}`}
                                            name={`dashboard-filter-${filter}`}
                                            options={getOptions(filter)}
                                            onChange={(value) => updateFilterState(filter, value)}
                                        />
                                    </Modal>
                                }
                                button={
                                    <SURFButton
                                        shape={SURFShapeLeft()}
                                        backgroundColor={white}
                                        contentSpacing={'space-between'}
                                        minWidth={'118px'}
                                        text={getButtonText(filter)}
                                        textSize={"12px"}
                                        textColor={spaceCadet}
                                        iconStart={filterSet(filter) ? faTimes : undefined}
                                        iconStartSize={"12px"}
                                        onIconStartClick={(e) => clearState(e, filter, `dashboard-filter-group-${filter}`)}
                                        iconEnd={faChevronDown}
                                        iconEndSize={"12px"}
                                        height={'33px'}
                                        padding={"0 16px"}
                                    />
                                }
                            />
                        }
                    }) }
                </div>
                <DeleteAllFilters onClick={resetAll}>{t("dashboard.tasks.table.filters.remove_filters")}</DeleteAllFilters>
            </div> }
        </>
    )
}

const Modal = styled.div`
    display: flex;
    flex-direction: column;
    min-width: 196px;
    gap: 5px;
    background: ${white};
    padding: 10px;
    ${SURFShapeLeft};
    box-shadow: 0px 4px 10px rgba(45, 54, 79, 0.2);
`;

const DeleteAllFilters = styled.span`
    font-size: 12px;
    color: ${majorelle};
    text-decoration: underline;
    height: 16px;
    position: absolute;
    bottom: -12px;
    right: 0;
    cursor: pointer;
    user-select: none;
`

export default DashboardFilters