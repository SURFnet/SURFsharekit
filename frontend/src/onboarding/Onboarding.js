import EmptyPage, {GlobalEmptyPageMethods} from "../components/emptypage/EmptyPage";
import Background from "../resources/images/surf-background.png";
import React, {useEffect, useRef, useState} from "react";
import {useTranslation} from "react-i18next";
import './onboarding.scss'
import '../components/field/formfield.scss'
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import {
    faArrowLeft,
    faArrowRight,
    faBuilding,
    faTimes,
} from "@fortawesome/free-solid-svg-icons";
import {FormStep} from "../components/field/relatedrepoitempopup/RelatedRepoItemContent";
import {FormField} from "../components/field/FormField";
import {useForm} from "react-hook-form";
import {Navigate} from "react-router-dom";
import MemberPositionOptionsHelper from "../util/MemberPositionOptionsHelper";
import {SearchInput} from "../components/searchinput/SearchInput";
import {HelperFunctions} from "../util/HelperFunctions";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import AppStorage, {StorageKey, useAppStorageState} from "../util/AppStorage";
import {useGlobalState} from "../util/GlobalState";
import {useNavigation} from "../providers/NavigationProvider";
import {UserSuggestion} from "../dashboard/components/suggestion/UserSuggestion";
import {ThemedH3, ThemedP} from "../Elements";
import styled from "styled-components";
import MergeProfilePopup from "../profile/mergeprofilespopup/MergeProfilesPopup";

const LoadingContainer = styled.div`
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 450px;
`

const StyledLoadingIndicator = styled(LoadingIndicator)`
    margin-right: 20px;
`

const LoadingTitle = styled(ThemedH3)`
    margin-top: 30px;
    padding-bottom: 0;
`

const SuggestionTitle = styled(ThemedH3)`
    padding-bottom: 0 !important;
`

const SuggestionDescription = styled(ThemedP)`
    text-align: center;
    margin-bottom: 20px;
`

const SuggestionList = styled.div`
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 100%;
    max-height: 425px;
    overflow-y: auto;
    padding: 8px 8px 10px 8px;
`

const SuggestionButtonContainer = styled.div`
    display: flex;
    justify-content: right;
    margin-top: 40px;
`

const StyledIconButtonText = styled(IconButtonText)`
    flex-direction: row-reverse;
    gap: 7px;
`

const SelectedInstitutesList = styled.div`
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    column-gap: 10px;
    margin-bottom: 10px;
`

const NoResults = styled.div`
    text-align: center;
    margin-top: 20px;
`

const StyledSelectedInstitute = styled.div`
    display: flex;
    background-color: rgb(115,68,238);
    color: white;
    font-size: 12px;
    padding-left: 12px;
    align-items: center;
    border-radius: 2px 10px 10px;
    margin: 2px;
`

const InstituteTitle = styled.p`
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
`

const InstituteButton = styled.div`
    cursor: pointer;
    padding: 10px 12px;
`

export default function Onboarding(props) {
    const [currentStepIndex, setCurrentStepIndex] = useState(0);
    const {t} = useTranslation();
    const profileFormSubmitButtonRef = useRef();
    const [memberData, setMemberData] = useAppStorageState(StorageKey.MEMBER_DATA);
    const [selectedInstitutes, setSelectedInstitutes] = useState(() => {
        const defaultInstitute = getDefaultInstituteFromMemberData(memberData);
        return defaultInstitute ? [defaultInstitute] : [];
    });
    const [currentProfileFormData, setCurrentProfileFormData] = useState(null)
    const [isTopMenuVisible, setTopMenuVisible] = useGlobalState('isTopMenuVisible', true);
    const [loadingSuggestions, setLoadingSuggestions] = useState(true);
    const navigate = useNavigation()

    useEffect(() => {
        setTopMenuVisible(false)

        if (props.location?.state?.memberData && !memberData) {
            setMemberData(props.location.state.memberData);
        }
    },[])

    useEffect(() => {
        if (!selectedInstitutes || selectedInstitutes.length === 0) {
            const defaultInstitute = getDefaultInstituteFromMemberData(memberData);
            if (defaultInstitute) {
                setSelectedInstitutes([defaultInstitute]);
            }
        }
    }, [memberData, selectedInstitutes]);

    if (!memberData) {
        return <Navigate to={'login?redirect=login'}/>
    }

    const isMemberStudent = memberData.position === "student";

    let totalSteps = 4;
    let totalStepsVisible = 2;
    let formSteps = (
        <div className='onboarding-steps flex-row form-step-list'>
            <FormStep active={currentStepIndex === 0}
                      number={1}
                      title={t('onboarding.profile_step.title')}/>
            <div className='form-step-divider'/>
            <FormStep active={currentStepIndex === 1}
                      number={2}
                      title={t('onboarding.institute_step.title')}/>
        </div>
    )

    const isFirstStep = (currentStepIndex === 0)
    const isLastStep = (currentStepIndex === totalSteps - 1)

    function getStepContent() {

        const instituteStepContent = (
            <InstituteStepContent
                memberData={memberData}
                selectedInstitutes={selectedInstitutes}
                setSelectedInstitutes={setSelectedInstitutes}
            />
        )

        const profileStepContent = (
            <ProfileStepContent
                memberData={memberData}
                submitButtonRef={profileFormSubmitButtonRef}
                savedProfile={(profileFormData) => {
                    //Form values validated, proceed to next step
                    setCurrentStepIndex(currentStepIndex + 1);
                    setCurrentProfileFormData(profileFormData)
                }}
            />
        )

        const startStepContent = (
            <StartStepContent
                memberData={memberData}
                selectedInstitutes={selectedInstitutes}
                profileFormData={currentProfileFormData}
                navigate={navigate}
            />
        )

        const suggestionStepContent = (
            <SuggestionStepContent
                memberData={memberData}
                isLoading={loadingSuggestions}
                setIsLoading={setLoadingSuggestions}
                nextStep={nextStep}
                navigate={navigate}
            />
        )

        const studentSteps = [
            profileStepContent,
            instituteStepContent,
            suggestionStepContent,
            startStepContent,
        ];

        const memberSteps = [
            profileStepContent,
            instituteStepContent,
            suggestionStepContent,
            startStepContent,
        ];

        const steps = isMemberStudent ? studentSteps : memberSteps;

        return steps[currentStepIndex];
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
                    { currentStepIndex <= totalStepsVisible - 1 && (
                        <>
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
                                                    previousStep()
                                                }}
                                                style={{visibility: isFirstStep ? "hidden" : "visible"}}
                                />

                                <IconButtonText className={"onboarding-next-button"}
                                                faIcon={faArrowRight}
                                                buttonText={t("action.next")}
                                                onClick={()=>{
                                                    if (currentStepIndex === 0) {
                                                        profileFormSubmitButtonRef.current?.click();
                                                        return;
                                                    }

                                                    nextStep();
                                                }}
                                                style={{visibility: isLastStep ? "hidden" : "visible"}}
                                />
                            </div>
                        </>
                    ) || (
                        <>
                            {getStepContent()}
                        </>
                    )}
                </div>
            </div>
        </div>
    )

    return (
        <EmptyPage id="onboarding"
                   content={content}
                   style={{backgroundImage: `url('` + Background + `')`}}
        />
    )
}

function getDefaultInstituteFromMemberData(memberData) {
    const rootInstituteSummary = memberData?.rootInstitutesSummary?.[0];
    if (rootInstituteSummary?.id && rootInstituteSummary?.title) {
        return {
            id: rootInstituteSummary.id,
            title: rootInstituteSummary.title
        }
    }
    return null;
}

export function InstituteStepContent(props) {
    const {t} = useTranslation();
    const [currentQuery, setCurrentQuery] = useState("");
    const [institutes, setInstitutes] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const debouncedQueryChange = HelperFunctions.debounce(setCurrentQuery)

    const removeInstitute = (instituteToRemove) => {
        const updatedInstitutes = props.selectedInstitutes.filter(institute => institute.id !== instituteToRemove.id);
        props.setSelectedInstitutes(updatedInstitutes);
    };

    useEffect(() => {
        setIsLoading(true)
        searchInstitutes(currentQuery, props.memberData)
    }, [currentQuery])

    return (
        <div className={"institute-step-content"}>
            <h3>{t('onboarding.institute_step.title')}</h3>
            <SelectedInstitutesList>
                {props.selectedInstitutes.map((institute) => (
                    <SelectedInstitute
                        key={institute.id}
                        institute={institute}
                        selectedInstitutes={props.selectedInstitutes}
                        onRemoveInstitute={removeInstitute}
                    />
                ))}
            </SelectedInstitutesList>
            <SearchInput placeholder={t("navigation_bar.search")}
                         onChange={(e) => {
                             debouncedQueryChange(e.target.value)
                         }}/>
            <div className={`institute-options-list${isLoading ? " loading-state" : ""}`}>
                {
                    isLoading && <LoadingIndicator/>
                }
                {
                    !isLoading && institutes && institutes.length > 0 && institutes.map((institute) => (
                        <InstituteOptionRow key={institute.id}
                           institute={institute}
                           selectedInstitutes={props.selectedInstitutes}
                           onClick={didSelectRow}/>
                    )) || !isLoading && <NoResults>{t("onboarding.institute_step.no_results")}</NoResults>
                }
            </div>
        </div>
    )

    function didSelectRow(institute) {
        const isAlreadySelected = props.selectedInstitutes.some(selected => selected.id === institute.id);
        if (!isAlreadySelected) {
            const updatedInstitutes = [...props.selectedInstitutes, institute];
            props.setSelectedInstitutes(updatedInstitutes);
        }
    }

    function searchInstitutes(searchQuery = "", memberData = null) {
        setIsLoading(true)

        let levels = 'discipline';
        if (memberData && memberData.position !== 'student') {
            levels = 'lectorate,department,discipline'
        }

        const config = {
            params: {
                'filter[level]': levels,
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

        const errorCallback = (error) => {
            setIsLoading(false)
            Toaster.showServerError(error)
        }

        function onLocalFailure(error) {
            errorCallback(error);
        }

        function onServerFailure(error) {
            errorCallback(error);
        }
        Api.get('institutes', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

export function InstituteOptionRow(props) {
    const isSelectedInstitute = props.selectedInstitutes.some(selectedInstitute => selectedInstitute.id === props.institute.id);

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

export function SelectedInstitute(props) {
    return (
        <StyledSelectedInstitute>
            <InstituteTitle>{props.institute?.title ?? ""}</InstituteTitle>
            <InstituteButton className={"selected-institute-icon"} onClick={() => {
                props.onRemoveInstitute(props.institute);
            }}>
                <FontAwesomeIcon icon={faTimes}/>
            </InstituteButton>
        </StyledSelectedInstitute>
    )
}

export function ProfileStepContent(props) {

    const {register, handleSubmit, formState: { errors}, setValue} = useForm();
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
    const defaultRootInstituteTitle = props.memberData.rootInstitutesSummary?.[0]?.title;
    const defaultInstituteTitle = defaultRootInstituteTitle || props.memberData.institutes?.[0];
    if(defaultInstituteTitle) {
        organisationOption.push(getOrganisationOption(defaultInstituteTitle))
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
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={defaultInstituteTitle}
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
                                   register={register}
                                   setValue={setValue}
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
    const navigate = props.navigate
    const personIsStudent = (props.memberData.position === 'student' || props.profileFormData?.position === 'student' || props.memberData.conextRoles === 'student')

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

        // Filter out the organisation field from form data
        const { organisation, ...filteredFormData } = props.profileFormData;

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const patchData = {
            "data": {
                "type": "person",
                "id": props.memberData.id,
                "attributes": {
                    "hasFinishedOnboarding": 1,
                    ...filteredFormData
                }
            }
        };

        // Only students need disciplines; persist all selected institute ids
        if (props.selectedInstitutes && props.selectedInstitutes.length > 0 && personIsStudent) {
            patchData.data.attributes["disciplines"] = props.selectedInstitutes.map(institute => institute.id);
        }

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
                    navigate('/dashboard', {replace: true});
                } else {
                    navigate(redirect);
                }
            } else {
                navigate('/dashboard', {replace: true});
            }
        }

        const errorCallback = (error) => {
            GlobalEmptyPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        };

        function onLocalFailure(error) {
            errorCallback(error);
        }

        function onServerFailure(error) {
            errorCallback(error);
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        Api.patch('persons/' + props.memberData.id, () => {}, onSuccess, onLocalFailure, onServerFailure, config, patchData);
    }
}

export function SuggestionStepContent(props) {
    const {t} = useTranslation();
    const [personSummaries, setPersonSummaries] = useState(null);
    const [personsToMerge, setPersonsToMerge] = useAppStorageState(StorageKey.PERSONS_TO_MERGE)
    const navigate = props.navigate

    useEffect(() => {
        getUserSuggestions()
        // Add the current user to the list of profiles to merge
        addProfileDataToMergeList(props.memberData)
    }, [])

    function getUserSuggestions() {
        const config = {
            params: {
                'filter[suggestion]': `${props.memberData.firstName} ${props.memberData.surnamePrefix ? props.memberData.surnamePrefix + ' ' : ''}${props.memberData.surname}`,
                "page[size]": 10
            }
        };
        props.setIsLoading(true)

        Api.jsonApiGet('personSummaries', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            // Go to next step if no suggestions are found
            if (response.data.length === 0) {
                AppStorage.remove(StorageKey.PERSONS_TO_MERGE)
                console.log("No suggestions found, go to next step")
                props.nextStep()
                return
            }

            setPersonSummaries(response.data)

            props.setIsLoading(false)
        }

        const errorCallback = (error) => {
            Toaster.showServerError(error)
            AppStorage.remove(StorageKey.PERSONS_TO_MERGE)
            props.nextStep()
        };

        function onServerFailure(error) {
            errorCallback(error);
        }

        function onLocalFailure(error) {
            errorCallback(error);
        }
    }

    function addProfileDataToMergeList(person) {
        const personsToMerge = AppStorage.get(StorageKey.PERSONS_TO_MERGE)
        if (!personsToMerge) {
            AppStorage.set(StorageKey.PERSONS_TO_MERGE, [person])
        } else {
            const personAlreadyAdded = personsToMerge.find(profile => person.id === profile.id)
            if (personAlreadyAdded) {
                return
            }
            setPersonsToMerge([...personsToMerge, person])
        }
    }

    function removeProfileDataToMergeList(person) {
        const personsToNotMerge = AppStorage.get(StorageKey.PERSONS_TO_MERGE)
        const newArray = personsToNotMerge.filter(profile => person.id !== profile.id)
        AppStorage.set(StorageKey.PERSONS_TO_MERGE, newArray);
    }

    function onContinue(){
        if (personsToMerge.length <= 1) {
            console.log("No profiles to merge, go to next step")
            AppStorage.remove(StorageKey.PERSONS_TO_MERGE)
            props.nextStep()

        } else {
            MergeProfilePopup.show(navigate, true, () => {
                console.log("Profiles merged successfully. Go to the next step");
                AppStorage.remove(StorageKey.PERSONS_TO_MERGE)
                props.nextStep()
            });
        }
    }

    return (
        <div>
            { props.isLoading &&
                <LoadingContainer>
                    <StyledLoadingIndicator />
                    <LoadingTitle>{t('onboarding.suggestion_step.loading.title')}</LoadingTitle>
                </LoadingContainer>
                ||
                <>
                    <SuggestionTitle>{props.memberData.name}, {t('onboarding.suggestion_step.title')}</SuggestionTitle>
                    <SuggestionDescription className={"description"}>{t('onboarding.suggestion_step.description')}</SuggestionDescription>

                    <SuggestionList className="suggestion-list">
                        {personSummaries && personSummaries.map((person, index) => (
                            <UserSuggestion person={person} key={index} addProfileDataToMergeList={addProfileDataToMergeList} removeProfileDataToMergeList={removeProfileDataToMergeList}/>
                        ))}
                    </SuggestionList>

                    <SuggestionButtonContainer>
                        <StyledIconButtonText
                            faIcon={faArrowRight}
                            buttonText={t("action.next")}
                            onClick={()=>{
                                onContinue()
                            }}
                        />
                    </SuggestionButtonContainer>
                </>
            }
        </div>
    )
}
