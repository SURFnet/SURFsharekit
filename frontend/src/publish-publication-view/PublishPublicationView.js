import React, {useEffect, useRef, useState} from "react";
import './publish-publication.scss'
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import Page, {GlobalPageMethods} from "../components/page/Page";
import {Link, Redirect, useHistory} from "react-router-dom";
import {useTranslation} from "react-i18next";
import styled from "styled-components";
import {Form} from "../components/field/FormField";
import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import RepoItemHelper from "../util/RepoItemHelper";
import RepoItemApiRequests from "../util/api/RepoItemApiRequests";
import Toaster from "../util/toaster/Toaster";
import Api from "../util/api/Api";
import {useForm} from "react-hook-form";
import FormFieldHelper from "../util/FormFieldHelper";
import {
    createRelatedRepoItem,
    createValues,
    removeRelatedRepoItem,
    removeValues,
    setRelatedRepoItemOrder,
    showRepoItemPopup
} from "../components/field/repoitem/RelatedRepoItemHandles";
import VerificationPopup from "../verification/VerificationPopup";
import {useDirtyNavigationCheck} from "../util/hooks/useDirtyNavigationCheck";
import ValidationHelper, {VALIDATION_RESULT} from "../util/ValidationHelper";
import {useGlobalState} from "../util/GlobalState";
import PublishPublicationViewHeader from "./PublishPublicationViewHeader";
import {PublicationViewMode} from "../publication/Publication";
import PublicationFlowFooter from "../components/publicationflowfooter/PublicationFlowFooter";
import {CollapsePersonMergeFooterEvent} from "../util/events/Events";
import PublishPublicationCompletion from "./PublishPublicationCompletion";
import {deleteRepoItem} from "../components/reacttable/tables/publication/ReactPublicationTable";
import MergeProfilePopup from "../profile/mergeprofilespopup/MergeProfilesPopup";
import CheckDetailsPopup from "./popup/CheckDetailsPopup";
import axios from "axios";
import {SwalRepoItemPopup} from "../components/field/relatedrepoitempopup/RelatedRepoItemPopup";

function PublishPublicationView(props) {
    const repoItemApiRequests = new RepoItemApiRequests();
    const {t} = useTranslation();
    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);
    const [user] = useAppStorageState(StorageKey.USER);
    const {formState, register, handleSubmit, errors, setValue, getValues, trigger, reset, clearErrors} = useForm();
    const {dirtyFields} = formState
    const history = useHistory()
    const formFieldHelper = new FormFieldHelper();
    const formSubmitButton = useRef();
    const formSaveAndSubmitButton = useRef();
    const formApproveOrDeclineButton = useRef();
    const [published, setPublished] = useState(false)
    const [formSubmitActionType, setFormSubmitActionType] = useState(null);
    const [declineReason, setDeclineReason] = useState("");
    const [currentIndex, setCurrentIndex] = useState(0);
    const [validatedStepIds, setValidatedStepIds] = useState([]);
    const [currentSelectedStep, setCurrentSelectedStep] = useState(props.repoItem ? props.repoItem.steps[0] : null)
    const isProject = !!(history.location.state && history.location.state.isProject)
    let repoItemSections = props.repoItem ? RepoItemHelper.getSectionsFromSteps(props.repoItem) : [];
    const [showOnlyRequiredFields, setShowOnlyRequiredFields] = useState(0)

    const formReducerStateRef = useRef();
    formReducerStateRef.current = props.formReducerState

    useDirtyNavigationCheck(history, dirtyFields)

    useEffect(() => {
        if (props.repoItem){
            getRequiredFieldKeysFromStep()
            if (props.repoItem.title !== null) {
                document.title = props.repoItem.title
            } else {
                document.title = "Concept"
            }
        } else {
            document.title = "Loading..."
        }
    }, [props.repoItem])

    useEffect(() => {
        setIsSideMenuCollapsed(true)
        window.dispatchEvent(new CollapsePersonMergeFooterEvent(true))

        return () => {
            window.dispatchEvent(new CollapsePersonMergeFooterEvent(false))
        }
    }, [])

    useEffect(() => {
        if (formSubmitActionType === "publish"){
            changeFormPublishState(getValues())
        }
    }, [formSubmitActionType]);

    useEffect(() => {
        if (props.repoItem) {

            const newFormData = {}
            repoItemSections = RepoItemHelper.getSectionsFromSteps(props.repoItem)
            repoItemSections.map((section, i) => {
                section.fields.forEach(field => {
                    let formFieldAnswer = formFieldHelper.getFieldAnswer(props.repoItem, field)
                    if (typeof formFieldAnswer === 'object' && !!formFieldAnswer) {
                        formFieldAnswer = JSON.stringify(formFieldAnswer)
                    }
                    newFormData[field.key] = formFieldAnswer
                });
            });
            reset(newFormData)
        }
    }, [props.repoItem])

    useEffect(() => {
        getRequiredFieldKeysFromStep()
    }, [currentSelectedStep])

    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            VerificationPopup.show(t("publication.required_error.title"), t("publication.required_error.subtitle"), () => {
            }, true)
        }
    }, [errors])

    if (user === null) {
        return <Redirect to={'/unauthorized?redirect=publications/' + props.match.params.id}/>
    }

    let content;
    if (props.repoItem === null) {
        content = <LoadingIndicator centerInPage={true}/>
    } else {
        content = getPageContent();
    }

    function getPageContent() {
        const permissionCanEdit = props.repoItem.permissions.canEdit && !props.repoItem.isRemoved
        const permissionCanPublish = props.repoItem.permissions.canPublish && !props.repoItem.isRemoved

        const formIsDraft = props.repoItem.status.toLowerCase() === "draft"
        const formEditable = permissionCanEdit && formIsDraft

        return (
            <EditPublicationRoot>
                <div className={"form-elements-container"}>
                    <Form formId={"edit-publication-form-id"}
                          isPublicationFlow={true}
                          isEditing={true}
                          repoItem={props.repoItem}
                          sectionsToShow={currentSelectedStep && currentSelectedStep.templateSections}
                          currentlySelectedStep={currentIndex}
                          containsHiddenSections={true}
                          showSectionHeaders={true}
                          errors={errors}
                          onValueChanged={formFieldValueChanged}
                          onSubmit={handleSubmit(() => changeFormPublishState(getValues()))}
                          register={register}
                          setValue={setValue}
                          formReducerState={formReducerStateRef.current}
                          readonly={!formEditable}
                          showOnlyRequiredFields={showOnlyRequiredFields}/>
                </div>
                <PublicationFlowFooter
                    isSideMenuCollapsed={isSideMenuCollapsed}
                    currentIndex={currentIndex}
                    repoItem={props.repoItem}
                    currentSelectedStep={currentSelectedStep}
                    handleNext={() => handleNext()}
                    handlePrevious={() => handlePrevious()}
                    handleStepClick={(index) => handleStepClick(index)}
                    validatedStepIds={validatedStepIds}
                    currentStepId={currentSelectedStep}
                />
            </EditPublicationRoot>
        )
    }

    return (
        <Page id="add-publication"
              fixedElements={[
                <PublishPublicationViewHeader
                    title={currentSelectedStep && t('language.current_code') === 'nl' ? currentSelectedStep.titleNL : currentSelectedStep.titleEN}
                    subtitle={currentSelectedStep && t('language.current_code') === 'nl' ? currentSelectedStep.subtitleNL : currentSelectedStep.subtitleEN}
                    onSave={() => saveForm(getValues())}
                    onStop={() => history.push('/publications')}
                    onDelete={() => deletePublication()}
                    onCheckDetails={() => CheckDetailsPopup.show(props.repoItem)}
                    showOnlyRequiredFields={showOnlyRequiredFields}
                    setShowOnlyRequiredFields={setShowOnlyRequiredFields}
                />
              ]}
              history={history}
              activeMenuItem={isProject ? "projects" : "publications"}
              breadcrumbs={[
                  {
                      path: '../dashboard',
                      title: 'side_menu.dashboard'
                  },
                  {
                      path: isProject ? '../projects' : '../publications',
                      title: isProject ? 'side_menu.projects' : 'side_menu.my_publications'
                  },
                  {
                      path: isProject ? './projects' : './publications',
                      title: props.repoItem === null ? '' : (props.repoItem.title && props.repoItem.title !== "") ? props.repoItem.title : (isProject ? 'projects.new_project' : 'add_publication.popup.title')
                  }
              ]}
              content={content}
              showBackButton={true}/>
    );

    function getRequiredFieldKeysFromStep(){
        if (currentSelectedStep){
            const fields = currentSelectedStep.templateSections.map((section, index) => {
                return section.fields
            }).flat(1).filter(f => f.required === 1)

            return fields.map(key => {
                return key.key
            })
        }
    }

    function getValidationRegexKeysFromStep(){
        if (currentSelectedStep) {
            const fields = currentSelectedStep.templateSections.map((section, index) => {
                return section.fields
            }).flat(1).filter(f => f.validationRegex !== null)

            return fields.map(key => {
                return key.key
            })
        }
    }

    function checkForRequiredFields(){
        const getFilledRequiredFields = Object.entries(getValues(getRequiredFieldKeysFromStep()))
        let hasRequiredFieldsBeenFilled = true
        getFilledRequiredFields.forEach((field) => {
            if (field[1] === null || field[1] === "" || field[1] === undefined || field[1] === false) {
                hasRequiredFieldsBeenFilled = false
            }
        })

        if (hasRequiredFieldsBeenFilled){
            if (!(validatedStepIds.indexOf(currentSelectedStep.id) > -1)) {
                setValidatedStepIds([...validatedStepIds, currentSelectedStep.id])
            }
        } else {
            if (validatedStepIds.indexOf(currentSelectedStep.id) > -1) {
                validatedStepIds.splice(currentSelectedStep.id, 1);
            }
        }
    }

    function handleNext() {
        if (currentIndex < (props.repoItem.steps.length - 1)) {
            checkForRequiredFields()
            setCurrentSelectedStep(props.repoItem.steps[currentIndex + 1])
            setCurrentIndex(currentIndex + 1);
        } else {
            trigger().then(result => {
                if (result === false) {
                    VerificationPopup.show(t("publication.validation_error.title"), t("publication.validation_error.subtitle"), () => {
                    }, true)
                } else {
                    setFormSubmitActionType('publish')
                }
            });
        }
    }

    function handlePrevious() {
        if (currentIndex > 0) {
            checkForRequiredFields()
            setCurrentSelectedStep(props.repoItem.steps[currentIndex - 1])
            setCurrentIndex(currentIndex - 1);
        } else {
            if(props.repoItem.isHistoricallyPublished) {
                props.setViewMode(PublicationViewMode.DEFAULT)
            } else {
                history.goBack()
            }
        }
    }

    function handleStepClick(index){
        checkForRequiredFields()
        setCurrentSelectedStep(props.repoItem.steps[index]);
        setCurrentIndex(index);
    }

    function saveForm(formData) {
        const currentRepoItem = props.repoItem;
        GlobalPageMethods.setFullScreenLoading(true)
        setCurrentSelectedStep(currentSelectedStep)
        patchRepoItem(currentRepoItem, formData, props.repoItem.status);
    }

    function checkDetails() {

    }

    function deletePublication() {
        const title = isProject ? t("projects.delete_popup.title") : t("publication.delete_popup.title")
        const subtitle = isProject ? t("projects.delete_popup.subtitle") : t("publication.delete_popup.subtitle")

        const deleteFunction = () => deleteRepoItem(props.repoItem.id, history, (responseData) => {
            reset();
            GlobalPageMethods.setFullScreenLoading(false)
            history.replace('/publications')
        }, (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError();
        })

        VerificationPopup.show(title, subtitle, () => {
            GlobalPageMethods.setFullScreenLoading(true)
            deleteFunction()
        })
    }

    function changeFormPublishState(formData) {
        const currentRepoItem = props.repoItem;
        let newStatus;

        if (formSubmitActionType != null) {
            currentRepoItem.declineReason = ""
            if (formSubmitActionType === 'approve') {
                newStatus = 'Approved'
            } else if (formSubmitActionType === 'decline') {
                if (!declineReason) {
                    VerificationPopup.show(t("publication.missing_decline_reason_popup.title"), t("publication.missing_decline_reason_popup.subtitle"), () => {
                    }, true)
                    return;
                } else {
                    newStatus = 'Declined'
                    currentRepoItem.declineReason = declineReason
                }
            } else if (currentRepoItem.status === 'Draft' || currentRepoItem.status === 'Declined') {
                newStatus = currentRepoItem.permissions.canPublish ? 'Approved' : 'Submitted';
            } else {
                newStatus = 'Draft';
            }

            if (newStatus === 'Submitted') { //Trying to publish
                if (errors.length > 0) {
                    return;
                }
            }

            setFormSubmitActionType(null);
            GlobalPageMethods.setFullScreenLoading(true)
            patchRepoItem(currentRepoItem, formData, newStatus);
        }
    }

    async function createExtraRepoItems(repoType, repoItemId, count) {
        // create extra RepoItemPerson objects, there was already 1 created when opening the relatedRepoItemPopup
        // so create (props.selectedPersons.length - 1) repoItems

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const postData = {
            "data": {
                "type": "repoItem",
                "attributes": {
                    "repoType": repoType
                },
                "relationships": {
                    "relatedTo": {
                        "data": {
                            "type": "institute",
                            "id": repoItemId
                        }
                    }
                }
            }
        };
        const requestList = []
        for(let i = 0; i < (count); i++) {
            requestList.push(axios.post("repoItems", postData, Api.getRequestConfig(config)))
        }
        return Promise.all(requestList)
    }


    function formFieldValueChanged(field, changedValue) {

        const fieldType = formFieldHelper.getFieldType(field.fieldType);

        if (fieldType === 'attachment' || fieldType === 'personinvolved' ||
            fieldType === 'repoitemlink' || fieldType === 'repoitemlearningobject' || fieldType === 'repoitemresearchobject') {
            const action = changedValue;
            if (action.type === 'create') {
                const repoTypeMap = {
                    'attachment': 'RepoItemRepoItemFile',
                    'personinvolved': 'RepoItemPerson',
                    'repoitemlink': 'RepoItemLink',
                    'repoitemlearningobject': 'RepoItemLearningObject',
                    'repoitemresearchobject': 'RepoItemResearchObject'
                };

                let fieldValuesAsString = getValues()[field.key];
                let fieldValuesAsArray = []
                if (fieldValuesAsString !== undefined && fieldValuesAsString !== null && fieldValuesAsString !== '') {
                    fieldValuesAsArray = JSON.parse(fieldValuesAsString).map(ri => ri.summary.repoItem ? ri.summary.repoItem.id : ri.id)
                }

                if (fieldType === 'attachment'){
                    createExtraRepoItems(repoTypeMap[fieldType], props.repoItem.relatedTo.id, action.value.length).then(axios.spread((...responses) => {
                        const repoItemList = responses.map((response) => {
                            return Api.dataFormatter.deserialize(response.data);
                        })

                        showRepoItemPopup (
                            formReducerStateRef.current,
                            field,
                            props.repoItem.id,
                            action.value,
                            (newValues) => {
                                props.dispatch({ type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues });
                            },
                            props.repoItem,
                            fieldValuesAsArray,
                            null,
                            repoItemList
                        );
                    })).catch(errors => {
                        SwalRepoItemPopup.close()
                        Toaster.showDefaultRequestError()
                    })
                } else {
                    showRepoItemPopup(
                        formReducerStateRef.current,
                        field,
                        null,
                        action.value,
                        newValues => {
                            props.dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                        },
                        props.repoItem,
                        fieldValuesAsArray,
                        (onSuccess, onFailure) => {
                            createRelatedRepoItem(repoTypeMap[fieldType], props.repoItem.relatedTo.id, history, onSuccess, onFailure)
                        }
                    )
                }
            } else if (action.type === 'edit') {
                showRepoItemPopup(formReducerStateRef.current, field, action.value, null, newValues => {
                        props.dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                    }
                    , props.repoItem
                )
            } else if (action.type === 'delete') {
                removeRelatedRepoItem(formReducerStateRef.current, field, action.value,
                    newValues => {
                        props.dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                    })
            } else if (action.type === 'sort change') {
                setRelatedRepoItemOrder(formReducerStateRef.current, field, action.value,
                    newValues => {
                        props.dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                    })
            }
        }
        if (fieldType === 'tree-multiselect') {
            const action = changedValue;
            if (action.type === 'delete') {
                removeValues(formReducerStateRef.current, field, action.value,
                    newValues => {
                        props.dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                    })
            } else if (action.type === 'create') {
                createValues(formReducerStateRef.current, field, action.value,
                    (newValues) => {
                        props.dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                    })
            }
        }
    }

    function patchRepoItem(currentRepoItem, formData, status) {
        const answers = (formData) ? formFieldHelper.getAllFormAnswersForRepoItem(props.repoItem, formData) : null;

        const shouldCheckChannelDependencyErrors = ValidationHelper.shouldCheckChannelDependencyErrors(status)

        if (shouldCheckChannelDependencyErrors) {
            let validationResult = ValidationHelper.hasChannelDependencyErrors(answers, props.repoItem);

            if (validationResult === VALIDATION_RESULT.DEPENDENCY_ERROR) {
                GlobalPageMethods.setFullScreenLoading(false);
                VerificationPopup.show(t("add_publication.channel_dependency_error.title"), t("add_publication.channel_dependency_error.subtitle"), () => {
                }, true)
                return;
            } else if (validationResult === VALIDATION_RESULT.NO_CHANNEL) {
                GlobalPageMethods.setFullScreenLoading(false);
                VerificationPopup.show(t("add_publication.no_channel_error.title"), t("add_publication.no_channel_error.subtitle"), () => {
                }, true)
                return;
            }
        }

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showToaster({message: t("publication.request_save_publication_success")})
            const repo = Api.dataFormatter.deserialize(response.data);
            props.onRepoItemChanged(repo)
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError();
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            },
            params: {
                'include': "relatedTo",
            }
        }

        const attributes = {
            "status": status,
            "repoType": props.repoItem.repoType,
            "declineReason": props.repoItem.declineReason
        }
        if (answers) {
            attributes["answers"] = answers;
        }

        const patchData = {
            "data": {
                "type": "repoItem",
                "id": props.repoItem.id,
                "attributes": attributes
            }
        };

        const url = "repoItems/" + currentRepoItem.id
        Api.patch(url, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData)
    }
}

const EditPublicationRoot = styled.div`
    margin-top: 60px;
    margin-bottom: 200px;
    padding-left: 80px;
    padding-right: 80px;
`;


export default PublishPublicationView;