import "../../dropdown/dropdown.scss";
import Api from "../../../util/api/Api";
import React, {useEffect, useState} from "react";
import SURFButton from "../../../styled-components/buttons/SURFButton";
import Select from "react-select";
import Toaster from "../../../util/toaster/Toaster";
import styled from "styled-components";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import {GlobalPageMethods} from "../../page/Page";
import {Tooltip} from "../FormField";
import {useTranslation} from "react-i18next";
import {
    majorelle,
    majorelleLight,
    spaceCadet,
    spaceCadetLight,
    SURFShapeRight
} from "../../../Mixins";
import LoadingIndicator from "../../loadingindicator/LoadingIndicator";
import SuggestionCheckboxes from "./suggestioncheckbox/SuggestionCheckbox";
import SelectMethod from "./selectmethod/SelectMethod";
import {HelperFunctions} from "../../../util/HelperFunctions";
import {useNavigation} from "../../../providers/NavigationProvider";

const Container = styled.div`
    display: flex;
    flex-direction: column;
    padding: 20px;
`

const TopFlexContainer = styled.div`
    width: 100%;
    display: flex;
    justify-content: space-between;
`

const VocabularySelectionContainer = styled.div`
    display: flex;
    flex-direction: column;
    gap: 15px;
`

const VocabularyOptionField = styled(Select)`
    width: 100%;
    height: auto !important;
`

const Flex = styled.div`
    align-items: center;
    height: auto;
    width: 100%;
    display: flex;
    margin-top: 10px;
`

const Buttons = styled.div`
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
`

const VocabularyLinkedFile = styled.span`
    text-align: center;
`

function VocabularyPopupContent2(props){
    const {t} = useTranslation()

    let label = t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN';

    return (
        <div className={"add-publication-popup-content-wrapper"}>
            <PopupContent {...props} />
        </div>
    )

    function PopupContent(props){
        const [options, setOptions] = useState([])
        const [selectedVocabulary, setSelectedVocabulary] = useState(null)
        const [selectedMethod, setSelectedMethod] = useState('method-diy')
        const [selectedSuggestions, setSelectedSuggestions] = useState([])
        const [fileUuidForScanning, setFileUuidForScanning] = useState(null)
        const [suggestions, setSuggestions] = useState(null)
        const [loading, setLoading] = useState(false)
        const [step, setStep] = useState(1)
        const navigate = useNavigation()

        const resolveTitle = (jsonKey: string | null | undefined, label: string): string => {
            const title = jsonKey?.includes("vocabulary")
                ? t("vocabulary_field.popup.title")
                : label;

            return HelperFunctions.capitalize(title);
        };

        useEffect(() => {
            getVocabulary(props.name, (response) => {
                setOptions(response.data);
            }, (error) => {
                Toaster.showServerError(error)
                GlobalPageMethods.setFullScreenLoading(false)
            })
        }, []);

        const nextButtonDisabled =
            selectedVocabulary === null ||
            !selectedMethod ||
            (selectedMethod === 'method-ai' && !fileUuidForScanning) ||
            (step === 2 && suggestions === null) ||
            (step === 2 && selectedSuggestions.length === 0)

        const CustomSelectElement = (props2) => {
            const isDisabled = props2.data.disabled;

            return (
                <div
                    className={`surf-select__custom-select-container`}
                    style={{
                        pointerEvents: isDisabled ? 'none' : 'auto',
                        opacity: isDisabled ? 0.5 : 1
                    }}
                >
                    <div className={`surf-select__text-wrapper ${isDisabled ? ' disabled' : ''}`}>
                        {t('language.current_code') === 'nl' ? props2.data.labelNL : props2.data.labelEN}
                    </div>
                </div>
            );
        };

        function createRelatedRepoItem(successCallback, failureCallback) {

            function onValidate(response) {
            }

            function onSuccess(response) {
                console.log(response.data)
                successCallback(response.data)
            }

            function onLocalFailure(error) {
                Toaster.showServerError(error)
                failureCallback(error)
            }

            function onServerFailure(error) {
                Toaster.showServerError(error)
                failureCallback(error)
                if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                    navigate('/login?redirect=' + window.location.pathname);
                }
            }

            const config = {
                headers: {
                    "Content-Type": "application/vnd.api+json",
                }
            }

            const postData = {
                "repoItemRepoItemFileUuid": fileUuidForScanning.id,
                "metaFieldUuid": props.name,
                "metaFieldOptionUuid": selectedVocabulary.id,
            };

            Api.post('metadata-suggestion', onValidate, onSuccess, onLocalFailure, onServerFailure, config, postData)
        }

        function getOptions() {
            return options.map(option => {
                option.label = t('language.current_code') === 'nl' ? option.labelNL : option.labelEN;
                option.disabled = props.vocabularies.some(vocab => vocab.id === option.id);

                return option;
            });
        }

        function getVocabulary(name, onSuccess, errorCallback = () => {}) {
            function onValidate(response) {}
            function onLocalFailure(error) {
                errorCallback(error)
            }

            function onServerFailure(error) {
                if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                    navigate('/login?redirect=' + window.location.pathname);
                }
                errorCallback(error)
            }

            const vocabularyCallConfig = {
                params: {
                    "filter[fieldKey]": name,
                    "filter[ParentOption]": 'null',
                    "filter[isRemoved][EQ]": 0,
                    'page[number]': 1,
                    'page[size]': 10,
                }
            };

            if (!props.retainOrder) {
                vocabularyCallConfig.params.sort = label
            }

            Api.jsonApiGet('metaFieldOptions', onValidate, onSuccess, onLocalFailure, onServerFailure, vocabularyCallConfig);
        }

        const handleAddVocabulary = () => {
            if (selectedMethod === 'method-diy' && selectedVocabulary) {
                props.selectedVocabularyOption(selectedVocabulary);
            } else if (selectedMethod === 'method-ai' && selectedSuggestions) {
                props.selectedMetaFieldOptions(selectedVocabulary, selectedSuggestions);
            }
        };

        const handleSetSuggestions = () => {
            setLoading(true);

            createRelatedRepoItem((response) => {
                const reformattedSuggestions = response.suggestions.map(({metaFieldOptionUuid, labelEN, labelNL}) => ({
                    metaFieldOptionUuid,
                    labelEN,
                    labelNL,
                }));

                setSuggestions(reformattedSuggestions);
                setLoading(false);

            }, (error) => {
                console.log(`Something went wrong: ${error}`)
                setLoading(false)
            });
        }


        const findFileUuidThroughRepoItem = () => {
            setLoading(true)

            if (!props.formReducerState) {
                setLoading(false)
                return null;
            }

            const filteredItems = Object.values(props.formReducerState).flat().filter(
                (item) =>
                    item?.summary?.accessRight === "openaccess" &&
                    item?.summary?.repoType === "RepoItemRepoItemFile"
            );

            const importantItem = filteredItems.find((item) => item?.summary?.important === true);
            const matchedItem = importantItem || filteredItems[0];

            setLoading(false)
            return matchedItem
                ? { id: matchedItem.summary.id, title: matchedItem.summary.title }
                : null;
        }

        const handleAddVocabularyThroughAI = (): void => {
            const file = findFileUuidThroughRepoItem()
            setFileUuidForScanning(file)
        }

        /**
         * Adds or removes an option from the selected list.
         *
         * If the option is already selected, it gets removed.
         * If the option is not selected, it gets added.
         *
         * @param {string} uuid - The unique ID of the option to toggle
         */
        const handleOptionChange = (uuid) => {
            setSelectedSuggestions(prevSelected => {
                // If prevSelected is null or undefined, initialize it as an empty array
                const currentSelected = prevSelected || [];

                // Check if the option is already selected
                if (currentSelected.includes(uuid)) {
                    // Remove the option if it's already selected
                    return currentSelected.filter(id => id !== uuid);
                } else {
                    // Add the option if it's not already selected
                    return [...currentSelected, uuid];
                }
            });
        };

        return (
            <Container>
                <TopFlexContainer >
                    <h3>{resolveTitle(props.jsonKey, props.label)}</h3>
                    <FontAwesomeIcon className={"close-button-container"} icon={faTimes} style={{cursor: "pointer"}} onClick={props.onCancel}/>
                </TopFlexContainer>

                { step === 1 &&
                    <VocabularySelectionContainer>
                        <Flex>
                            <VocabularyOptionField
                                className={"surf-dropdown"}
                                classNamePrefix={"surf-select"}
                                options={getOptions()}
                                isOptionDisabled={(option) => option.disabled}
                                onChange={(selection) => {
                                    setSelectedVocabulary(selection)
                                    setSelectedMethod(selectedMethod)
                                }}
                                components={{
                                    SingleValue: CustomSelectElement
                                }}
                                /* Needed extra CSS to align it to the left */
                                formatOptionLabel={option => (
                                    <div style={{alignSelf: "start", margin: 0, textAlign: "left", color: `${option.disabled ? 'lightgray' : 'black'}`}}>
                                        <span>{option.label}</span>
                                    </div>
                                )}
                            />
                            <Tooltip text={t("vocabulary_field.popup.explanation")}/>
                        </Flex>
                        { (props.jsonKey.includes('vocabulary') && props.jsonKey) &&
                            <SelectMethod
                                selectedVocabulary={selectedVocabulary}
                                onMethodSelect={(selectedMethod) => {
                                    setSelectedMethod(selectedMethod);
                                    if (selectedMethod === 'method-ai') {
                                        handleAddVocabularyThroughAI();
                                    }
                                }}
                            />
                        }

                        {/* Display the name of the file */}
                        { loading ? (<LoadingIndicator />) : (
                            selectedMethod === 'method-ai' && (
                                fileUuidForScanning ?
                                    <VocabularyLinkedFile>{t("vocabulary_field.popup.selected_file.result")} {fileUuidForScanning.title}</VocabularyLinkedFile>
                                    :
                                    <VocabularyLinkedFile>{t("vocabulary_field.popup.selected_file.no_result")}</VocabularyLinkedFile>
                            )
                        )}
                    </VocabularySelectionContainer>
                }

                { step === 2 &&
                    <>
                        { loading ? (<LoadingIndicator />) : (
                            suggestions ?
                                <SuggestionCheckboxes
                                    suggestions={suggestions}
                                    selectedOptions={selectedSuggestions}
                                    onOptionChange={handleOptionChange}
                                />
                                :
                                <p>Nothing found</p>
                        )}
                    </>
                }

                <Buttons>
                    <SURFButton
                        shape={SURFShapeRight}
                        backgroundColor={spaceCadet}
                        highlightColor={spaceCadetLight}
                        text={t("vocabulary_field.actions.cancel")}
                        padding={'0px 32px 0px 32px'}
                        onClick={props.onCancel}
                    />
                    <SURFButton
                        shape={SURFShapeRight}
                        backgroundColor={majorelle}
                        highlightColor={majorelleLight}
                        text={
                            (selectedMethod === 'method-ai' && step === 1)  ?
                                t("vocabulary_field.actions.generate") :
                                props.jsonKey?.includes('vocabulary') ? t('vocabulary_field.actions.add') : (t("language.current_code") === 'nl') ? `${props.label[0] + props.label.slice(1).toLowerCase()} ${t('action.add').toLowerCase()}` : `${t('action.add')} ${props.label.toLowerCase()}`
                        }
                        padding={'0px 32px 0px 32px'}
                        disabled={nextButtonDisabled}
                        onClick={() => {
                            if (selectedMethod === 'method-diy') {
                                handleAddVocabulary();
                            } else if (selectedMethod === 'method-ai') {
                                if (step === 1) {
                                    handleSetSuggestions();
                                    setStep(2);
                                } else if (step === 2 && suggestions.length > 0) {
                                    handleAddVocabulary();
                                }
                            }
                        }}
                    />
                </Buttons>
            </Container>
        )
    }
}

export default VocabularyPopupContent2;