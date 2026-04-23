import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faSpinner, faTimes} from "@fortawesome/free-solid-svg-icons";
import React, {useEffect, useRef, useState} from "react";
import "./addpublicationpopup.scss"
import '../../components/field/formfield.scss'
import {useTranslation} from "react-i18next";
import {RadioGroup} from "../../components/field/radiogroup/RadioGroup";
import {
    instituteCreateRepoItemPermissions,
    instituteCreateRepoItemPermissionToRealType,
    instituteCreateRepoItemPermissionToString
} from "../Publications";
import {FormStep} from "../../components/field/relatedrepoitempopup/RelatedRepoItemContent";
import SearchRepoItemTable from "../../components/searchrepoitemtable/SearchRepoItemTable";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";
import styled from "styled-components";
import SURFButton from "../../styled-components/buttons/SURFButton";
import {
    majorelle,
    majorelleLight,
} from "../../Mixins";
import {BlackButton, NextButton, PreviousButton} from "../../styled-components/buttons/NavigationButtons";
import {SwitchField} from "../../components/field/switch/Switch";
import {Step2SelectionContainer, UseTemplateContainer, UseTemplateLabel} from "./AddPublicationPopupContentStyled";
import SearchLMSRepoItemTable from "../../components/searchLMSRepoItemTable/SearchLMSRepoItemTable";
import {useNavigation} from "../../providers/NavigationProvider";
import Api from "../../util/api/Api";
import axios from "axios";
import Toaster from "../../util/toaster/Toaster";
import LmsFlaggedPopup from "./LmsFlaggedPopup";

/**
 * AddPublicationPopupContent Component
 * 
 * A multi-step popup component for adding new publications or projects to the system.
 * The component handles the creation flow for different types of publications,
 * This flow guides the user by first selecting an institute, if his rights allow it. 
 * The person then can proceed to select a publication type and then either use a template or upload a new publication.
 * Optionally, it can also be used to create a new LMS item.
 * 
 * @component
 * @param {Object} popupProps - The props passed to the component
 * @param {Array} popupProps.institutes - List of available institutes
 * @param {boolean} popupProps.isProject - Whether this is a project creation flow
 * @param {Function} popupProps.onCancel - Callback for when the popup is cancelled
 * @param {Function} popupProps.instituteAndTypeSelected - Callback when institute and type are selected
 * @param {Function} popupProps.repoItemToCopySelected - Callback when a template is selected
 * @param {Function} popupProps.lmsItemToCreate - Callback when an LMS item is selected
 */
export function AddPublicationPopupContent(popupProps) {
    const institutes = popupProps.institutes
    const skipStep1 = institutes.length === 1
    const isProject = popupProps.isProject === true

    const {t} = useTranslation()
    const [currentStepIndex, setCurrentStepIndex] = useState((skipStep1) ? 1 : 0);
    const [selectedPublicationType, setSelectedPublicationType] = useState(null)
    const [selectedRadioOption, setSelectedRadioOption] = useState(null)
    const [useTemplate, setUseTemplate] = useState(false)
    const selectedInstitute = useRef((skipStep1) ? institutes[0] : null);
    const userRoles = useAppStorageState(StorageKey.USER_ROLES);
    const userHasExtendedAccess = userRoles ? userRoles.find(c => c !== 'Student' && c !== 'Default Member') : false;

    return (
        <div className={"add-publication-popup-content-wrapper"}>
            <div className={"add-publication-popup-content"}>
                <div className={"close-button-container"}
                     onClick={popupProps.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                <PopupContent/>
            </div>
        </div>
    )

    /**
     * PopupContent Component
     * 
     * Internal component that manages the step-based content of the popup.
     * Handles the rendering of different steps based on the current step index.
     * 
     * @component
     * @private
     */
    function PopupContent() {
        let indexOffset = skipStep1 ? -1 : 0;

        const steps = [
            <Step1Content key="step-1" />,
            <Step2Content key="step-2" />,
            <Step3Content key="step-3" />,
            <LMSContent key="step-3" className={"LMS"} />,
        ];

        return (
            <div>
                <div className={"header-container"}>
                    <h3 className={"popup-title"}>{ isProject ? t('projects.new_project') : t('add_publication.popup.title')}</h3>
                    <div className='flex-row form-step-list'>
                        {!skipStep1 && <FormStep
                            active={currentStepIndex === 0}
                            number={1 + indexOffset}
                            title={t('add_publication.popup.step1_title')}
                        />}
                        {!skipStep1 && !isProject && <div className='form-step-divider'/>}
                        {!isProject && <FormStep
                            active={currentStepIndex === 1}
                            number={2 + indexOffset}
                            title={t('add_publication.popup.step2_title')}
                        />
                        }
                        {userHasExtendedAccess && !isProject && <div className='form-step-divider'/>}
                        {userHasExtendedAccess && !isProject && <FormStep
                            active={[2, 3].includes(currentStepIndex)}
                            number={3 + indexOffset}
                            title={t('add_publication.popup.step3_title')}/>}
                    </div>
                </div>

                {/*Render steps based on currentStepIndex*/}
                {steps[currentStepIndex]}
            </div>
        )

        /**
         * Step1Content Component
         * 
         * First step of the popup that allows users to select an institute.
         * If there's only one institute, this step is skipped.
         * 
         * @component
         * @private
         */
        function Step1Content(props) {
            function instituteRowClicked(institute) {
                if(isProject) {
                    popupProps.instituteAndTypeSelected({
                        institute: institute,
                        selectedPublicationType: "Project",
                        lmsEnabled: institute.lmsEnabled
                    })
                } else {
                    selectedInstitute.current = institute
                    setCurrentStepIndex(1)
                }
            }

            let institutesHtml = []
            for (let i = 0; i < institutes.length; i++) {
                const instituteWrapper = institutes[i]
                institutesHtml.push(<div className={"institute-option"} onClick={() => {
                    instituteRowClicked(instituteWrapper)
                }}>
                    <div className={"status-color-indicator"}/>
                    <div className={"icon-container"}>
                        <div className="fas fa-building icon"/>
                    </div>
                    <div className={"title"}>{instituteWrapper.title}</div>
                </div>)
            }

            return <div>
                <h4 className={"type-title"}>{t('add_publication.popup.organisation_title')}</h4>

                <div className={"institute-options"}>
                    {institutesHtml}
                </div>
            </div>
        }

        /**
         * Step2Content Component
         * 
         * Second step of the popup that allows users to select the publication type
         * and optionally use a template. Handles different publication types
         * including learning objects and projects.
         * 
         * @component
         * @private
         */
        function Step2Content(props) {
            const Step2PopupContent = (props) => <div {...props} />
            const publicationIsLearningObject = selectedRadioOption === "canCreateLearningObject"
            const userCanRequestLMSItem = selectedInstitute.current?.permissions?.canRequestLmsItem

            const repoItemTypesToCreate = instituteCreateRepoItemPermissions(selectedInstitute.current).filter(p => p !== "canCreateProject").map((p) => {
                return {
                    "value": p,
                    "label": instituteCreateRepoItemPermissionToString(p, t)
                }
            })

            return (
                <Step2PopupContent>
                    <h4 className={"type-title"}>{t('add_publication.popup.type_title')}</h4>

                    <Step2SelectionContainer>
                        <RadioGroup
                            name={"add-publication-radoi-group"}
                            readonly={false}
                            options={repoItemTypesToCreate}
                            defaultValue={selectedPublicationType}
                            onChange={(change) => {
                                setSelectedRadioOption(change);
                                setSelectedPublicationType(change);
                            }}
                        />

                        <UseTemplateContainer>
                            <UseTemplateLabel>{t('add_publication.popup.use_template')}</UseTemplateLabel>
                            <SwitchField
                                name="useTemplate"
                                defaultValue={useTemplate ? true : 0}
                                onChange={() => setUseTemplate(prevState => (!prevState))}
                                readonly={false}
                            />
                        </UseTemplateContainer>
                    </Step2SelectionContainer>

                    <Footer>
                        {!skipStep1 ?
                            <PreviousButton
                                text={props.buttonText ?? t('action.previous')}
                                onClick={() => {
                                    setCurrentStepIndex(0)
                                }}
                            />
                            :
                            <div></div>
                        }

                        <ButtonWrapper>
                            {/*LMS Logic*/}
                            { (userCanRequestLMSItem && publicationIsLearningObject && useTemplate === false && selectedInstitute.current.lmsEnabled) ?
                                <BlackButton
                                    disabled={!selectedPublicationType}
                                    text={props.buttonText ?? t('add_publication.popup.upload_from_lms')}
                                    padding={"0 10px"}
                                    onClick={() => {
                                        setCurrentStepIndex(3)
                                    }}
                                />
                                :
                                <></>
                            }

                            <NextButton
                                disabled={!selectedPublicationType}
                                text={useTemplate ? t('add_publication.popup.next') : t('add_publication.popup.confirm')}
                                onClick={() => {
                                    useTemplate ?
                                    setCurrentStepIndex(2)
                                    :
                                    popupProps.instituteAndTypeSelected({
                                        institute: selectedInstitute.current,
                                        selectedPublicationType: instituteCreateRepoItemPermissionToRealType(selectedPublicationType)
                                    })
                                }}
                            />
                        </ButtonWrapper>
                    </Footer>
                </Step2PopupContent>
            )
        }

        /**
         * Step3Content Component
         * 
         * Third step of the popup that allows users to select a template
         * for their publication. Only shown for users with extended access.
         * 
         * @component
         * @private
         */
        function Step3Content(props) {
            const [repoItemToCopy, setRepoItemToCopy] = useState(null)

            return <div>
                <h4 className={"type-title"}>{t('add_publication.popup.template_title')}</h4>

                <SearchRepoItemTable onRepoItemSelect={(repoItem) => {setRepoItemToCopy(repoItem)}}
                                     selectedRepoItems={repoItemToCopy && [repoItemToCopy]}
                                     hideNextButton={true}
                                     multiSelect={false}
                                     repoType={selectedPublicationType}
                                     filters={
                                         {
                                             'filter[permissions.canCopy]': true,
                                             'filter[scope]': selectedInstitute.current.id,
                                             'filter[isRemoved]': 0,
                                             'filter[repoType]': instituteCreateRepoItemPermissionToRealType(selectedPublicationType)
                                         }
                                     }
                />

                <Footer>
                    <PreviousButton
                        text={props.buttonText ?? t('action.previous')}
                        onClick={() => {
                            setCurrentStepIndex(1)
                        }}
                    />

                    <ButtonWrapper>
                        <BlackButton
                            text={props.buttonText ?? t('add_publication.popup.no_template')}
                            onClick={() => {
                                popupProps.instituteAndTypeSelected({
                                    institute: selectedInstitute.current,
                                    selectedPublicationType: instituteCreateRepoItemPermissionToRealType(selectedPublicationType)
                                })
                            }}
                        />

                        <SURFButton
                            disabled={repoItemToCopy == null}
                            text={props.buttonText ?? t('add_publication.popup.confirm')}
                            backgroundColor={majorelle}
                            highlightColor={majorelleLight}
                            width={'90px'}
                            onClick={() => {
                                popupProps.repoItemToCopySelected(repoItemToCopy)
                            }}
                        />
                    </ButtonWrapper>
                </Footer>
            </div>
        }

        /**
         * LMSContent Component
         * 
         * Special step for handling LMS (Learning Management System) items.
         * Allows users to select and import content from the LMS.
         * 
         * @component
         * @private
         */
        function LMSContent(props) {
            const [lmsItemToUse, setLmsItemToUse] = useState(null);
            const [isFlagging, setIsFlagging] = useState(false);
            const navigate = useNavigation();

            /**
             * Handles the next step in the LMS item creation process.
             * Posts the selected LMS item UUID to the backend for processing.
             * 
             * @function
             * @returns {void}
             * 
             * @example
             * When an LMS item is selected and next is clicked:
             * handleNextClick() // Posts to lms/flag/{uuid} and proceeds on success
             */
            const handleNextClick = () => {
                if (!lmsItemToUse || isFlagging) return;
                setIsFlagging(true);

                const config = {
                    headers: {
                        "Content-Type": "application/vnd.api+json",
                    }
                };

                const postData = {
                    "data": {
                        "institute": selectedInstitute.current.id,
                    }
                };

                Api.post(`lms/flag/${lmsItemToUse.id}`,
                    (response) => {
                        if (response?.status === 200) {
                            setIsFlagging(false);
                            LmsFlaggedPopup.show(() => {
                                popupProps.lmsItemToCreate();
                            });
                        } else {
                            setIsFlagging(false);
                            Toaster.showDefaultRequestError();
                        }
                    },
                    (error) => {
                        if (!axios.isCancel(error)) {
                            setIsFlagging(false);
                            Toaster.showDefaultRequestSuccess();
                        }
                    },
                    (error) => {
                        if (!axios.isCancel(error)) {
                            setIsFlagging(false);
                            Toaster.showServerError(error);
                            if (error?.response?.status === 401) {
                                navigate('/login?redirect=' + window.location.pathname);
                            }
                        }
                    },
                    (error) => {
                        if (!axios.isCancel(error)) {
                            setIsFlagging(false);
                            Toaster.showServerError(error);
                            if (error?.response?.status === 401) {
                                navigate('/login?redirect=' + window.location.pathname);
                            }
                        }
                    },
                    config,
                    postData
                );
            };

            return (
                <div>
                    <SearchLMSRepoItemTable
                        onLMSItemSelect={(lmsItem) => {setLmsItemToUse(lmsItem)}}
                        selectedLMSItems={lmsItemToUse && [lmsItemToUse]}
                        hideNextButton={true}
                        multiSelect={false}
                        repoType={selectedPublicationType}
                        instituteUuid={selectedInstitute.current.id}
                    />

                    <Footer>
                        <PreviousButton
                            text={props.buttonText ?? t('action.previous')}
                            onClick={() => {
                                setCurrentStepIndex(1)
                            }}
                            disabled={isFlagging}
                        />

                        <NextButton
                            disabled={lmsItemToUse == null || isFlagging}
                            text={isFlagging ? (
                                <span>
                                    <FontAwesomeIcon icon={faSpinner} spin={true}/> {props.buttonText ?? t('add_publication.popup.confirm')}
                                </span>
                            ) : (props.buttonText ?? t('add_publication.popup.confirm'))}
                            onClick={handleNextClick}
                        />
                    </Footer>
                </div>
            );
        }
    }
}

const Footer = styled.div`
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    padding-top: 50px;
`;

const ButtonWrapper = styled.div`
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    gap: 10px;
`;