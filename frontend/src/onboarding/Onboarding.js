import EmptyPage, {GlobalEmptyPageMethods} from "../components/emptypage/EmptyPage";
import Background from "../resources/images/surf-background.jpeg";
import React, {useEffect, useRef, useState} from "react";
import {useTranslation} from "react-i18next";
import './onboarding.scss'
import '../components/field/formfield.scss'
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import {faArrowLeft, faArrowRight, faBuilding} from "@fortawesome/free-solid-svg-icons";
import {FormStep} from "../components/field/relatedrepoitempopup/RelatedRepoItemContent";
import {FormField} from "../components/field/FormField";
import {useForm} from "react-hook-form";
import {Redirect} from "react-router-dom";
import MemberPositionOptionsHelper from "../util/MemberPositionOptionsHelper";
import {SearchInput} from "../components/searchinput/SearchInput";
import {HelperFunctions} from "../util/HelperFunctions";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import AppStorage, {StorageKey} from "../util/AppStorage";
import {Jsona} from "jsona";

export default function Onboarding(props) {

    const [currentStepIndex, setCurrentStepIndex] = useState(0);
    const {t} = useTranslation();
    const profileFormSubmitButtonRef = useRef();
    const [memberData, setMemberData] = useState(props.location?.state?.memberData)
    const [selectedInstitute, setSelectedInstitute] = useState(null)
    const [currentProfileFormData, setCurrentProfileFormData] = useState(null)

    if (!memberData) {
        return <Redirect to={'unauthorized?redirect=login'}/>
    }

    const isMemberStudent = memberData.position === "student";

    let formSteps;
    let totalSteps;
    if(isMemberStudent) {
        //Show all steps
        totalSteps = 3;
        formSteps = (
            <div className='onboarding-steps flex-row form-step-list'>
                <FormStep active={currentStepIndex === 0}
                          number={1}
                          title={t('onboarding.institute_step.title')}/>
                <div className='form-step-divider'/>
                <FormStep active={currentStepIndex === 1}
                          number={2}
                          title={t('onboarding.profile_step.title')}/>
                <div className='form-step-divider'/>
                <FormStep active={currentStepIndex === 2}
                          number={3}
                          title={t('onboarding.start_step.title')}/>
            </div>
        )
    } else {
        //Show profile step and start step
        totalSteps = 2
        formSteps = (
            <div className='onboarding-steps flex-row form-step-list'>
                <FormStep active={currentStepIndex === 0}
                          number={1}
                          title={t('onboarding.profile_step.title')}/>
                <div className='form-step-divider'/>
                <FormStep active={currentStepIndex === 1}
                          number={2}
                          title={t('onboarding.start_step.title')}/>
            </div>
        )
    }

    const isFirstStep = (currentStepIndex === 0)
    const isLastStep = (currentStepIndex === totalSteps - 1)

    function getStepContent() {

        const instituteStepContent = (
            <InstituteStepContent didSelectInstitute={(institute) => {
                                      setSelectedInstitute(institute)
                                  }}/>
        )

        const profileStepContent = (
            <ProfileStepContent memberData={memberData}
                                submitButtonRef={profileFormSubmitButtonRef}
                                savedProfile={(profileFormData) => {
                                    //Form values validated, proceed to next step
                                    setCurrentStepIndex(currentStepIndex + 1);
                                    setCurrentProfileFormData(profileFormData)
                                }}/>
        )

        const startStepContent = (
            <StartStepContent memberData={memberData}
                              selectedInstitute={selectedInstitute}
                              profileFormData={currentProfileFormData}
                              history={props.history}/>
        )

        if(isMemberStudent) {
            if (currentStepIndex === 0) {
                return instituteStepContent
            }
            if (currentStepIndex === 1) {
                return profileStepContent
            }
            if (currentStepIndex === 2) {
                return startStepContent
            }
        } else {
            if (currentStepIndex === 0) {
                return profileStepContent
            }
            if (currentStepIndex === 1) {
                return startStepContent
            }
        }
    }

    function previousStep() {
        if(currentStepIndex !== 0) {
            setCurrentStepIndex(currentStepIndex - 1)
        }
    }

    function nextStep() {
        if(currentStepIndex !== (totalSteps - 1)) {
            setCurrentStepIndex(currentStepIndex + 1)
        }
    }

    const content = (
        <div className={"onboarding-page"}>
            <div className={"onboarding-wrapper"}>
                <div className={"onboarding-container"}>
                    <div className={"logo-wrapper"}>
                        <img alt="Surf" id="login-logo" src={require('../resources/images/surf-sharekit-logo.png')}/>
                    </div>

                    <div className='onboarding-steps-list flex-row form-step-list'>
                        {formSteps}
                    </div>

                    {getStepContent()}

                    <div className={"button-container"}>
                        <IconButtonText className={"onboarding-previous-button"}
                                        faIcon={faArrowLeft}
                                        buttonText={t("action.previous")}
                                        onClick={()=>{
                                            if(isMemberStudent && currentStepIndex === 1 && selectedInstitute) {
                                                setSelectedInstitute(null)
                                                previousStep()
                                            } else {
                                                previousStep()
                                            }
                                        }}
                                        style={{visibility: isFirstStep ? "hidden" : "visible"}}
                        />

                        <IconButtonText className={`onboarding-next-button${(currentStepIndex === 0 && isMemberStudent && !selectedInstitute) ? " disabled" : ""}`}
                                        faIcon={faArrowRight}
                                        buttonText={t("action.next")}
                                        onClick={()=>{
                                            if(currentStepIndex === 0 && isMemberStudent && selectedInstitute) {
                                                nextStep()
                                            } else if((isMemberStudent && currentStepIndex === 1) || (!isMemberStudent && currentStepIndex === 0)) {
                                                profileFormSubmitButtonRef.current.click();
                                            }
                                        }}
                                        style={{visibility: isLastStep ? "hidden" : "visible"}}
                        />
                    </div>
                </div>
            </div>
        </div>
    )

    return (
        <EmptyPage id="onboarding"
                   history={props.history}
                   content={content}
                   style={{backgroundImage: `url('` + Background + `')`}}
        />
    )
}

export function InstituteStepContent(props) {
    const {t} = useTranslation();
    const [currentQuery, setCurrentQuery] = useState("");
    const [institutes, setInstitutes] = useState(null);
    const [selectedInstitute, setSelectedInstitute] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const debouncedQueryChange = HelperFunctions.debounce(setCurrentQuery)

    useEffect(() => {
        setIsLoading(true)
        searchInstitutes(currentQuery)
    }, [currentQuery])

    return (
        <div className={"institute-step-content"}>
            <h3>{t('onboarding.institute_step.title')}</h3>
            <SearchInput placeholder={t("navigation_bar.search")}
                         onChange={(e) => {
                             debouncedQueryChange(e.target.value)
                         }}/>
            <div className={`institute-options-list${isLoading ? " loading-state" : ""}`}>
                {
                    isLoading && <LoadingIndicator/>
                }
                {
                    !isLoading && institutes && institutes.map((institute) => {
                        return <InstituteOptionRow key={institute.id}
                                                   institute={institute}
                                                   selectedInstitute={selectedInstitute}
                                                   onClick={didSelectRow}/>
                    })
                }
            </div>
        </div>
    )

    function didSelectRow(institute) {
        setSelectedInstitute(institute)
        props.didSelectInstitute(institute)
    }

    function searchInstitutes(searchQuery = "") {
        setIsLoading(true)

        const config = {
            params: {
                'filter[level]': 'discipline',
                'filter[isRemoved]': 0,
                'page[number]': 1,
                'page[size]': 50,
            }
        };

        if(searchQuery && searchQuery.length > 0) {
            config.params['filter[title][LIKE]'] = '%' + searchQuery + '%';
        }

        function onValidate(response) {}

        function onSuccess(response) {
            setIsLoading(false)
            const instituteResults = Api.dataFormatter.deserialize(response.data) ?? [];
            setInstitutes(instituteResults)
        }

        function onLocalFailure(error) {
            setIsLoading(false)
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            setIsLoading(false)
            Toaster.showServerError(error)
        }

        Api.get('institutes', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

export function InstituteOptionRow(props) {
    const isSelectedInstitute = props.selectedInstitute && props.selectedInstitute.id === props.institute.id

    return (
        <div className={`institute-option-row${isSelectedInstitute ? " selected-institute" : ""}`}
             onClick={() => {
                 props.onClick(props.institute)
             }}>
            <div className={"institute-option-icon"}>
                <FontAwesomeIcon icon={faBuilding}/>
            </div>
            <div className={"institute-option-title"}>
                {props.institute.title}
            </div>
        </div>
    )
}

export function ProfileStepContent(props) {

    const {register, handleSubmit, errors, setValue} = useForm();
    const {t} = useTranslation();
    const profileFormRef = useRef();

    function getOrganisationOption(instituteName) {
        return {
            value: instituteName,
            labelNL: instituteName,
            labelEN: instituteName
        }
    }

    const organisationOption = []
    if(props.memberData.institutes.length > 0) {
        organisationOption.push(getOrganisationOption(props.memberData.institutes[0]))
    }

    const functionOptions = new MemberPositionOptionsHelper().getPositionOptions();

    return (
        <div className={"profile-step-content"}>
            <h3>{t('onboarding.profile_step.title')}</h3>
            <form id={"onboarding-profile-form"}
                  onSubmit={handleSubmit(props.savedProfile)}
                  ref={profileFormRef}>
                <div className={"form-row flex-row"}>
                    <div className={"flex-column form-field-container first-name-field"}>
                        <FormField key={"firstName"}
                                   classAddition={''}
                                   type={"text"}
                                   label={t("onboarding.profile_step.first_name")}
                                   isRequired={true}
                                   readonly={false}
                                   error={errors["firstName"]}
                                   name={"firstName"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={props.memberData.firstName}
                        />
                    </div>
                    <div className={"flex-column form-field-container surname-prefix-field"}>
                        <FormField key={"surnamePrefix"}
                                   type={"text"}
                                   label={t("onboarding.profile_step.surname_prefix")}
                                   isRequired={false}
                                   readonly={false}
                                   error={errors["surnamePrefix"]}
                                   name={"surnamePrefix"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={props.memberData.surnamePrefix}
                        />
                    </div>
                    <div className={"flex-column form-field-container surname-field"}>
                        <FormField key={"surname"}
                                   type={"text"}
                                   label={t("onboarding.profile_step.surname")}
                                   isRequired={true}
                                   readonly={false}
                                   error={errors["surname"]}
                                   name={"surname"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={props.memberData.surname}
                        />
                    </div>
                </div>
                <div className={"form-row flex-row"}>
                    <div className={"flex-column form-field-container"}>
                        <FormField key={"organisation"}
                                   options={organisationOption}
                                   type={"dropdown"}
                                   label={t("onboarding.profile_step.organisation")}
                                   isRequired={true}
                                   readonly={true}
                                   name={"organisation"}
                        />
                    </div>
                    <div className={"flex-column form-field-container"}>
                        <FormField key={"position"}
                                   options={functionOptions}
                                   type={"dropdown"}
                                   label={t("onboarding.profile_step.position")}
                                   isRequired={true}
                                   readonly={props.memberData.position === 'student'}
                                   error={errors["position"]}
                                   name={"position"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={props.memberData.position}
                        />
                    </div>
                </div>
                <div className={"form-row flex-row"}>
                    <div className={"flex-column form-field-container"}>
                        <FormField key={"email"}
                                   classAddition={''}
                                   type={"email"}
                                   label={t("onboarding.profile_step.email")}
                                   isRequired={true}
                                   readonly={true}
                                   error={errors["email"]}
                                   name={"email"}
                                   defaultValue={props.memberData.email}
                        />
                    </div>
                </div>
                <div className={"form-row flex-row"}>
                    <div className={"flex-column form-field-container"}>
                        <FormField key={"secondaryEmail"}
                                   classAddition={''}
                                   type={"email"}
                                   label={t("onboarding.profile_step.secondary_email")}
                                   isRequired={false}
                                   error={errors["secondaryEmail"]}
                                   name={"secondaryEmail"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={props.memberData.secondaryEmail}
                        />
                    </div>
                </div>
                <button ref={props.submitButtonRef}
                        id="profile-save-button"
                        type="submit"
                        form="onboarding-profile-form"
                        style={{display: "none"}}
                />
            </form>
        </div>
    )
}

export function StartStepContent(props) {
    const {t} = useTranslation();

    return (
        <div className={"start-step-content"}>
            <h3>{t('onboarding.start_step.title')}</h3>
            <div className={"start-text"}>
                {t("onboarding.start_step.text")}
            </div>
            <ButtonText className={"save-button"}
                        text={t("onboarding.start_step.start")}
                        buttonType={"callToAction"}
                        onClick={() => {
                            saveOnboardingData();
                        }}/>
        </div>
    )

    function saveOnboardingData() {
        GlobalEmptyPageMethods.setFullScreenLoading(true)

        function onSuccess(responseData) {
            //If user updated own profile
            const savedUser = AppStorage.get(StorageKey.USER);
            const userAfterOnboarding = Api.dataFormatter.deserialize(responseData.data);
            if (savedUser.id === userAfterOnboarding.id) {
                if (userAfterOnboarding.name !== savedUser.name) {
                    savedUser.name = userAfterOnboarding.name;
                    AppStorage.set(StorageKey.USER, savedUser);
                }
            }

            GlobalEmptyPageMethods.setFullScreenLoading(false)
            const redirect = AppStorage.get(StorageKey.STATE_REDIRECT)
            const isRedirectPrivate = AppStorage.get(StorageKey.STATE_NEEDS_ACCESSTOKEN)

            if (redirect) {
                if (isRedirectPrivate) {
                    Api.downloadFileWithAccessTokenAndPopup(redirect, null)
                    props.history.push('dashboard');
                } else {
                    props.history.push(redirect);
                }
            } else {
                props.history.push('dashboard');
            }
        }

        function onLocalFailure(error) {
            GlobalEmptyPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            GlobalEmptyPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                props.history.push('/login?redirect=' + window.location.pathname);
            }
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const patchData = {
            "data": {
                "type": "person",
                "id": props.memberData.id,
                "attributes":{
                    "hasFinishedOnboarding": 1,
                    ...props.profileFormData
                }
            }
        };

        if(props.selectedInstitute) {
            patchData.data.attributes["discipline"] = props.selectedInstitute.id
        }

        Api.patch('persons/' + props.memberData.id, () => {}, onSuccess, onLocalFailure, onServerFailure, config, patchData);
    }
}