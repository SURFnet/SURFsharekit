import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import React, {useEffect, useRef, useState} from "react";
import "./vocabularypopup.scss"
import "../formfield.scss"
import {useTranslation} from "react-i18next";
import {RadioGroup} from "../radiogroup/RadioGroup";
import ButtonText from "../../buttons/buttontext/ButtonText";
import {FormStep} from "../relatedrepoitempopup/RelatedRepoItemContent";
import {SearchInput} from "../../searchinput/SearchInput";
import {GlobalPageMethods} from "../../page/Page";
import Api from "../../../util/api/Api";
import Toaster from "../../../util/toaster/Toaster";
import {CheckboxGroup} from "../checkboxgroup/CheckboxGroup";
import {HelperFunctions} from "../../../util/HelperFunctions";
import i18n from "i18next";
import LoadingIndicator from "../../loadingindicator/LoadingIndicator";

function VocabularyPopupContent(props) {

    const {t} = useTranslation()
    let label = t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN';

    return (
        <div className={"add-publication-popup-content-wrapper"}>
            <div className={"add-publication-popup-content"}>
                <div className={"close-button-container"}
                     onClick={props.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                <PopupContent {...props}/>
            </div>
        </div>
    )

    function PopupContent(props) {

        const [currentStepIndex, setCurrentStepIndex] = useState(0);
        const [previouslySelectedOptions, setPreviouslySelectedOptions] = useState([]);
        const [options, setOptions] = useState([]);
        const [optionsUnavailable, setOptionsUnavailable] = useState(false);
        const [selectedOption, setSelectedOption] = useState(null);
        const [selectedCheckboxes, setSelectedCheckboxes] = useState([]);
        const [currentQuery, setCurrentQuery] = useState("");
        const [optionsAreEmpty, setOptionsAreEmpty] = useState(false);
        const [loaderActive, setLoaderActive] = useState(true);
        const debouncedQueryChange = HelperFunctions.debounce(setCurrentQuery)
        const STEP_AMOUNT = 4;
        const [searchValue, setSearchValue] = useState('');
        const firstUpdate = useRef(true);

        useEffect(() => {
            setOptionsUnavailable(options.every((item) => item.hasChildren === false))
        }, [options])

        useEffect(() => {
            if(!firstUpdate.current){
                setOptionsAreEmpty(false);
                setLoaderActive(true);
                let parentOption;
                if(currentStepIndex === 0){
                    parentOption = null;
                } else {
                    parentOption = previouslySelectedOptions[currentStepIndex - 1];
                }
                setOptions([])
                searchMetaFieldOptions(parentOption ? parentOption.id : null, currentQuery, (response) => {
                    setOptions(response.data);
                    setNoOptionsTextAndHideLoader(response)
                })
            }
            firstUpdate.current = false;
        }, [currentQuery]);

        useEffect(() => {
            clearOptionsAndActivateLoader()
            const parentOptionId = getSelectedOptionParentId();
            getVocabulary(props, parentOptionId,(response) => {
                setOptions(response.data);
                setNoOptionsTextAndHideLoader(response)
            });
        }, [previouslySelectedOptions])

        function getSelectedOptionParentId() {
            if(previouslySelectedOptions && previouslySelectedOptions.length > 0){
                let lastAddedItem = previouslySelectedOptions[previouslySelectedOptions.length-1]
                if(lastAddedItem){
                    return lastAddedItem.id
                }
            }
            return null;
        }

        function getOptionsOfStepIndex(stepIndex) {
            if(previouslySelectedOptions && previouslySelectedOptions.length > 0){
                let parentOption;
                if(stepIndex === 0){
                    parentOption = null;
                } else {
                    parentOption = previouslySelectedOptions[stepIndex - 1];
                }
                getVocabulary(props, parentOption ? parentOption.id : null, (response) => {
                    setOptions(response.data);
                    setNoOptionsTextAndHideLoader(response);
                });
            }
        }

        function setOptionSelected(stepIndex) {
            if(previouslySelectedOptions && previouslySelectedOptions[stepIndex]){
                setSelectedOption(previouslySelectedOptions[stepIndex]);
            }
        }

        function handleStepClick(stepIndex) {
            if(stepIndex < previouslySelectedOptions.length){
                clearOptionsAndActivateLoader();
                setCurrentStepIndex(stepIndex);
                getOptionsOfStepIndex(stepIndex);
                setOptionSelected(stepIndex);
                setSearchValue('');
            } else if (stepIndex === (STEP_AMOUNT - 1) && selectedCheckboxes.length !== 0){
                clearOptionsAndActivateLoader();
                setCurrentStepIndex(stepIndex);
                getOptionsOfStepIndex(stepIndex);
                setSearchValue('');
            }
        }

        function isOptionPreviouslySelectedForCurrentStep() {
            return !!previouslySelectedOptions[currentStepIndex];
        }

        function removePreviouslySelectedOptionsAfterCurrentIndex(selectedOption) {
            let prevOptions = previouslySelectedOptions;

            prevOptions.length = currentStepIndex;
            if(currentStepIndex !== 0){
                prevOptions[prevOptions.length] = selectedOption;
            }
            setPreviouslySelectedOptions(prevOptions);
            setSelectedCheckboxes([]);
        }

        function setDefaultOption() {
            if(currentStepIndex === 0 && previouslySelectedOptions.length === 0){
                if(selectedOption){
                    return selectedOption.value;
                } else {
                    return null;
                }
            } else if (previouslySelectedOptions[currentStepIndex]){
                    return previouslySelectedOptions[currentStepIndex].value;
            }else if (currentStepIndex === (STEP_AMOUNT -1)){

                let defaultValues = [];
                selectedCheckboxes.map(o => defaultValues.push(o.value))
                return defaultValues
            }
            return null;
        }

        function nextStep() {
            setCurrentStepIndex(currentStepIndex + 1);
            setSearchValue('');
            if(previouslySelectedOptions[currentStepIndex] && currentStepIndex !== (STEP_AMOUNT - 1)){
                setSelectedOption(previouslySelectedOptions[currentStepIndex]);
            }
        }

        function setNoOptionsTextAndHideLoader(response) {
            if(response.data.length === 0){
                setOptionsAreEmpty(true);
            } else {
                setOptionsAreEmpty(false);
            }
            setLoaderActive(false);
        }

        function clearOptionsAndActivateLoader() {
            if(optionsAreEmpty){
                setOptionsAreEmpty(false);
            }
            setLoaderActive(true);
            setOptions([]);
        }

        return(
            <div>
                <h3>{t('vocabulary_field.add')}</h3>
                <div className='flex-row form-step-list justify-between'>
                    <FormStep
                        active={currentStepIndex === 0}
                        number={1}
                        onClick={() => {handleStepClick(0)}}
                        completed={previouslySelectedOptions.length >= 1 && currentStepIndex !== 0}/>
                    <div className='form-step-divider'/>
                    <FormStep
                        active={currentStepIndex === 1}
                        number={2}
                        onClick={() => {handleStepClick(1)}}
                        completed={previouslySelectedOptions.length >= 2 && currentStepIndex !== 1}
                        stepDisabled={(optionsAreEmpty || optionsUnavailable) && currentStepIndex < 1}/>
                    <div className='form-step-divider'/>
                    <FormStep
                        active={currentStepIndex === 2}
                        number={3}
                        onClick={() => {handleStepClick(2)}}
                        completed={previouslySelectedOptions.length >= 3 && currentStepIndex !== 2}
                        stepDisabled={(optionsAreEmpty || optionsUnavailable) && currentStepIndex < 2}/>
                    <div className='form-step-divider'/>
                    <FormStep
                        active={currentStepIndex === 3}
                        number={4}
                        onClick={() => {handleStepClick(3)}}
                        stepDisabled={(optionsAreEmpty || optionsUnavailable) && currentStepIndex < 3}/>
                </div>

                <div>
                    {previouslySelectedOptions.length !== 0 && <h4 className={"type-title"}>
                        {t('language.current_code') === 'nl' ? previouslySelectedOptions[previouslySelectedOptions.length - 1].coalescedLabelNL : previouslySelectedOptions[previouslySelectedOptions.length - 1].coalescedLabelEN}
                    </h4>}
                    {previouslySelectedOptions.length === 0 &&<h4 className={"type-title"}>{t('vocabulary_field.popup.step_1_content_title')}</h4>}
                    <SearchInput
                        className={"search-input-container"}
                        placeholder={t("navigation_bar.search")}
                        onChange={(e) => {
                            setSearchValue(e.target.value)
                            debouncedQueryChange(e.target.value)
                        }}
                        value={searchValue}/>
                    { !optionsUnavailable
                        ? <RadioGroup
                            name={"vocabulary-popup-radio-group"}
                            readonly={false}
                            options={options}
                            onChange={(change) => {
                                let selectedOption = options.find((o) => {
                                    return o.value === change
                                })
                                setSelectedOption(selectedOption)
                                if (selectedOption.hasChildren === true) { setOptionsAreEmpty(false) } else { setOptionsAreEmpty(true) }
                                if(isOptionPreviouslySelectedForCurrentStep()){
                                    removePreviouslySelectedOptionsAfterCurrentIndex(selectedOption)
                                }
                            }}
                            defaultValue={setDefaultOption()}/>

                        : <CheckboxGroup
                            name={"vocabulary-popup-checkbox-group"}
                            options={options}
                            onChange={(change) => {
                                let selectedOptions = [];
                                for(let i = 0; i < change.length; i++){
                                    let option = options.find((o) => {
                                        return o.value === change[i]
                                    })
                                    if(option){
                                        selectedOptions.push(option);
                                    }
                                }
                                setSelectedCheckboxes(selectedOptions);
                            }}
                            defaultValue={setDefaultOption()}/>
                    }

                    {loaderActive ? <LoadingIndicator/> : null}

                    <PopupButtons
                        {...props}
                        selectedOption={selectedOption}/>
                </div>
            </div>
        )

        function PopupButtons(props) {

            function handleAddButtonClick() {
                if(selectedCheckboxes.length !== 0){
                    let arr = [];
                    selectedCheckboxes.forEach(option => {
                        arr.push(option)
                    })
                    props.selectedVocabulary({
                        value: arr
                    });
                } else {
                    if(currentStepIndex === previouslySelectedOptions.length && selectedOption){
                        props.selectedVocabulary({
                           value: [selectedOption]
                        });
                    } else {
                        props.selectedVocabulary({
                            value: [previouslySelectedOptions[previouslySelectedOptions.length - 1]]
                        });
                    }
                }
            }

            return (
                <div className={"button-container"}>
                    <ButtonText text={props.buttonText ?? t('action.add')}
                                className={""}
                                buttonType={"add"}
                                disabled={props.selectedOption === null && currentStepIndex === 0 && previouslySelectedOptions.length === 0}
                                onClick={() => {
                                    handleAddButtonClick()
                                }}/>

                    { currentStepIndex !== (STEP_AMOUNT - 1) && !optionsAreEmpty && <ButtonText text={props.buttonText ?? t('action.next')}
                                                                    buttonType={"callToAction"}
                                                                    disabled={props.selectedOption === null && currentStepIndex === 0 && previouslySelectedOptions.length === 0 || selectedOption === null}
                                                                    className={"save-button"}
                                                                    onClick={() => {
                                                                        if(!previouslySelectedOptions[currentStepIndex] || currentStepIndex === 0 && previouslySelectedOptions.length === 0){
                                                                            setPreviouslySelectedOptions(previouslySelectedOptions => [...previouslySelectedOptions, props.selectedOption])
                                                                        } else {
                                                                            clearOptionsAndActivateLoader();
                                                                            let parentOptionId = null;
                                                                            if(previouslySelectedOptions.length !== 0){
                                                                                parentOptionId = previouslySelectedOptions[currentStepIndex].id;
                                                                            }
                                                                            getVocabulary(props, parentOptionId, (response) => {
                                                                                setOptions(response.data);
                                                                                setNoOptionsTextAndHideLoader(response);
                                                                            });
                                                                        }
                                                                        setSelectedOption(null);
                                                                        nextStep()
                                                                    }}/>}
                </div>
            )
        }
    }

    function getVocabulary(props, parentOptionId ,onSuccess) {

        function onValidate(response) {}
        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
            GlobalPageMethods.setFullScreenLoading(false)
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                props.history.push('/login?redirect=' + window.location.pathname);
            }
            GlobalPageMethods.setFullScreenLoading(false)
        }

        const vocabularyCallConfig = {
            params: {
                "filter[fieldKey]": props.name,
                "filter[ParentOption]": parentOptionId ?? 'null',
                "filter[isRemoved][EQ]": 0,
                'page[number]': 1,
                'page[size]': 1000,
            }
        };

        // Sort on label when retainOrder is false
        if (!props.retainOrder) {
            vocabularyCallConfig.params.sort = label
        }

        Api.jsonApiGet('metaFieldOptions', onValidate, onSuccess, onLocalFailure, onServerFailure, vocabularyCallConfig);
    }

    function searchMetaFieldOptions(parentOptionId, searchQuery, onSuccess) {

        function onValidate(response) {}
        function onLocalFailure(error) {
            console.log(error);
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            console.log(error);
            Toaster.showServerError(error)
        }

        const config = {
            params: {
                "filter[fieldKey]": props.name,
                "filter[ParentOption]": parentOptionId ?? 'null',
                "filter[value][LIKE]": '%' + searchQuery + '%',
                "filter[isRemoved][EQ]": 0,
                'page[number]': 1,
                'page[size]': 1000,
            }
        };

        // Sort on label when retainOrder is false
        if (!props.retainOrder) {
            config.params.sort = label
        }

        Api.jsonApiGet('metaFieldOptions', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

export default VocabularyPopupContent;