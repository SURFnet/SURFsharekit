import React, {useEffect, useReducer, useRef, useState} from "react";
import './editpublication.scss'
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import Page, {GlobalPageMethods} from "../components/page/Page";
import {Link, Redirect, useHistory} from "react-router-dom";
import {useTranslation} from "react-i18next";
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import {faCheck, faFileInvoice, faTrash} from "@fortawesome/free-solid-svg-icons";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import {Form} from "../components/field/FormField";
import ProgressSection from "../components/progresssection/ProgressSection";
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
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {HelperFunctions} from "../util/HelperFunctions";
import StatusIcon from "../components/statusicon/StatusIcon";
import IconButton from "../components/buttons/iconbutton/IconButton";
import VerificationPopup from "../verification/VerificationPopup";
import {deleteRepoItem} from "../components/reacttable/tables/ReactPublicationTable";
import i18n from "i18next";
import {useDirtyNavigationCheck} from "../util/hooks/useDirtyNavigationCheck";
import ValidationHelper, {VALIDATION_RESULT} from "../util/ValidationHelper";

const initialState = {};

function reducer(state, action) {
    switch (action.type) {
        case 'FORM_FIELD_ANSWERS':
            return {
                ...state,
                ...action.formFieldAnswers
            };
        default:
            throw new Error("");
    }
}

function EditPublication(props) {
    const [formReducerState, dispatch] = useReducer(reducer, initialState);
    const contentRef = useRef();
    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);
    const [repoItem, setRepoItem] = useState(null);
    //Keeping track of repoItemAnswers forces an update when they change in 'updateProgressPerSection'. This will update the sections in the FormField with the correct repo item answer info as it will re-render.
    const [repoItemAnswers, setRepoItemAnswers] = useState(null);
    const [updateProgress, setUpdateProgress] = useState(false);
    const repoItemApiRequests = new RepoItemApiRequests();
    const {formState, register, handleSubmit, errors, setValue, getValues, trigger, reset} = useForm();
    const {dirtyFields} = formState
    const history = useHistory()
    const formFieldHelper = new FormFieldHelper();
    const hadErrors = useRef(false);
    const formSubmitButton = useRef();
    const formSaveAndSubmitButton = useRef();
    const formApproveOrDeclineButton = useRef();
    const formReducerStateRef = useRef();
    const [formSubmitActionType, setFormSubmitActionType] = useState(null);
    const [declineReason, setDeclineReason] = useState("");
    formReducerStateRef.current = formReducerState;
    let setProgressPerSection = null
    let setActiveSection = null
    let scrollSectionsContainer = null
    let scrollSections = null

    useDirtyNavigationCheck(history, dirtyFields)

    useEffect(() => {
        if (repoItem) {
            const newFormData = {}
            repoItem.sections.map((section, i) => {
                section.fields.forEach(field => {
                    let formFieldAnswer = formFieldHelper.getFieldAnswer(repoItem, field)
                    if (typeof formFieldAnswer === 'object' && !!formFieldAnswer) {
                        formFieldAnswer = JSON.stringify(formFieldAnswer)
                    }
                    newFormData[field.key] = formFieldAnswer
                });
            });
            reset(newFormData)
        }
    }, [repoItem])

    useEffect(() => {
        if (repoItem) {
            updateProgressPerSection();
        }
    }, [updateProgress]);

    useEffect(() => {
        if (errors && Object.keys(errors).length) {
            hadErrors.current = true;
        }
    }, [errors]);
    useEffect(() => {
        if (user === null) {
            return
        }
        getRepoItem();
    }, [])

    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            VerificationPopup.show(t("publication.required_error.title"), t("publication.required_error.subtitle"), () => {
            }, true)
        }
    }, [errors])

    if (user === null) {
        return <Redirect to={'../unauthorized?redirect=publications/' + props.match.params.id}/>
    }

    let content;
    if (repoItem === null) {
        content = <LoadingIndicator centerInPage={true}/>
    } else {
        content = getPageContent();
    }

    function getPageContent() {
        const permissionCanEdit = repoItem.permissions.canEdit
        const permissionCanPublish = repoItem.permissions.canPublish

        const formIsDraft = repoItem.status.toLowerCase() === "draft"
        const formEditable = permissionCanEdit && formIsDraft

        let buttonText = '';
        if (repoItem.status === 'Draft' || repoItem.status === 'Declined') {
            if (permissionCanPublish) {
                buttonText = t(repoItem.isHistoricallyPublished ? "add_publication.button_save_republish" : "add_publication.button_save_publish")
            } else {
                buttonText = t("add_publication.button_submit");
            }
        }

        let submitButton = undefined
        if (permissionCanEdit && buttonText) {
            submitButton = (
                <button
                    id="publish-button"
                    type="submit"
                    form="surf-form-edit-publication-form-id"
                    ref={formSubmitButton}
                    onClick={() => {
                        setFormSubmitActionType('publish')
                        handleSubmit(() => changeFormPublishState(getValues()))
                    }}>
                    <IconButton className={"icon-button-login-info"}
                                text={buttonText}
                                icon={faFileInvoice}/>
                </button>
            );
        }

        let declineOrApproveSection = undefined
        if (permissionCanPublish && repoItem.status === 'Submitted') {
            declineOrApproveSection = (
                <div className={"form-section decline-or-approve"}>
                    <div className={'title'}>
                        {t('action.rate')}
                    </div>

                    <div className={"inputs"}>
                        <fieldset className={'radio'}>
                            <div className={"option"}>
                                <input
                                    id={"decline"}
                                    type="radio"
                                    value="decline"
                                    data-keeper-edited="yes"
                                    checked={formSubmitActionType === 'decline'}
                                    onChange={() => setFormSubmitActionType('decline')}
                                />
                                <label htmlFor={"decline"}>
                                    {t('action.decline')}
                                </label>
                            </div>
                            <div className={"option"}>
                                <input
                                    id={"approve"}
                                    type="radio"
                                    value="approve"
                                    checked={formSubmitActionType === 'approve'}
                                    onChange={() => {
                                        setFormSubmitActionType('approve')
                                    }}
                                />
                                <label htmlFor={"approve"}>
                                    {t('action.approve')}
                                </label>
                            </div>
                        </fieldset>

                        {formSubmitActionType === 'decline' &&
                            <div className={"decline-reason-wrapper"}>
                                <label htmlFor={"decline-reason"}>
                                    {t('publication.decline_reason_label')}
                                </label>
                                <textarea id={"decline-reason"} value={declineReason} onChange={(e) => setDeclineReason(e.target.value)}/>
                            </div>
                        }

                        <button
                            className={`button ${!formSubmitActionType ? "disabled" : ""}`}
                            id="rate-button"
                            type="submit"
                            form="surf-form-edit-publication-form-id"
                            ref={formApproveOrDeclineButton}
                            disabled={!formSubmitActionType}
                            onClick={() => {
                                handleSubmit(() => changeFormPublishState(getValues()))
                            }}>{t('action.rate')}</button>
                    </div>
                </div>
            )
        }

        let saveButtonText
        if (!permissionCanPublish) {
            saveButtonText = (formIsDraft) ? t("add_publication.button_save") : t("add_publication.button_edit");
        } else {
            saveButtonText = (formIsDraft) ? t("add_publication.button_save") : t("add_publication.button_depublish");
        }
        const saveEditButton = <ButtonText className={"save-button"}
                                           text={saveButtonText}
                                           form="surf-form-edit-publication-form-id"
                                           onClick={() => (formIsDraft) ? saveForm(getValues()) : patchFormToDraft()}/>;

        const saveAndPublishButton = (
            <button
                id="save-publish-button"
                type="submit"
                form="surf-form-edit-publication-form-id"
                ref={formSaveAndSubmitButton}
                onClick={() => {
                    setFormSubmitActionType('publish')
                    handleSubmit(() => changeFormPublishState(getValues()))
                }}>
                <ButtonText className={"save-button"}
                            text={t(repoItem.isHistoricallyPublished ? "add_publication.button_save_republish" : "add_publication.button_save_publish")}
                            buttonType={"callToAction"}
                            form="surf-form-edit-publication-form-id"/>
            </button>
        );

        const progressSectionFooter = (
            <>
                {saveEditButton}
                {formIsDraft && permissionCanPublish && saveAndPublishButton}
            </>
        )

        return (
            <div>
                <div className={"title-row"}>
                    <h1>{RepoItemHelper.getTranslatedRepoType(repoItem.repoType)}</h1>
                    {
                        permissionCanEdit &&
                        <div className={"actions-container"}>
                            {repoItem.permissions.canDelete && <IconButtonText faIcon={faTrash}
                                                                               onClick={deletePublication}/>}
                            {saveEditButton}
                        </div>
                    }
                </div>
                <div className={"form-elements-container"}>
                    <div className={"left-pane-container"}>
                        <RepoItemDetails/>

                        <ProgressSection
                            sections={repoItem.sections}
                            setProgressPerSectionFunction={(setProgressPerSectionFunction) => {
                                setProgressPerSection = setProgressPerSectionFunction
                            }}
                            setActiveSectionFunction={(setActiveSectionFunction) => {
                                setActiveSection = setActiveSectionFunction
                            }}
                            footer={permissionCanEdit && progressSectionFooter}
                        />
                    </div>

                    <Form formId={"edit-publication-form-id"}
                          repoItem={repoItem}
                          showSectionHeaders={true}
                          errors={errors}
                          onValueChanged={formFieldValueChanged}
                          onSubmit={handleSubmit(() => changeFormPublishState(getValues()))}
                          register={register}
                          setValue={setValue}
                          formReducerState={formReducerState}
                          submitButton={submitButton}
                          extraContent={declineOrApproveSection}
                          readonly={!formEditable}/>
                </div>
            </div>
        )
    }

    function RepoItemDetails() {
        const dateFormatOptions = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }
        const createdFormatted = HelperFunctions.getDateFormat(repoItem.createdLocal, dateFormatOptions)
        const lastEditedFormatted = HelperFunctions.getDateFormat(repoItem.lastEditedLocal, dateFormatOptions)

        function CreatedLastEditedPersonLink(props) {
            let personContent
            if (props.person && props.person.permissions && props.person.permissions.canView === true) {
                personContent = <Link to={"/profile/" + props.person.id}>{props.person.name}</Link>
            } else {
                personContent = <React.Fragment>{props.person.name}</React.Fragment>
            }
            return <div className={"person"}>
                {t("publication.created_last_edited_prefix")}{personContent}
            </div>
        }

        return <div className={`repo-item-details ${repoItem.status === "Declined" ? "rejected" : ""}`}>

            <div className={"section"}>
                <div className={"section-title"}>{t("publication.created")}</div>
                <div
                    className={"date"}>{`${createdFormatted.day}-${createdFormatted.month}-${createdFormatted.year} ${createdFormatted.hour}:${createdFormatted.minute}`}</div>
                <CreatedLastEditedPersonLink person={repoItem.creator}/>
            </div>

            <div className={"section"}>
                <div className={"section-title"}>{t("publication.lastEdited")}</div>
                <div
                    className={"date"}>{`${lastEditedFormatted.day}-${lastEditedFormatted.month}-${lastEditedFormatted.year} ${lastEditedFormatted.hour}:${lastEditedFormatted.minute}`}</div>
                <CreatedLastEditedPersonLink person={repoItem.lastEditor}/>
            </div>

            <div className={"section"}>
                <div className={"section-title"}>{t("publication.organisation")}</div>
                <div className={"date"}>{repoItem.relatedTo.title}</div>
            </div>

            <div className={"section"}>
                <div className={"section-title"}>{t("publication.status")}</div>
                <div className={"status"}>
                    <StatusIcon colorHex={RepoItemHelper.getStatusColor(repoItem.status)}
                                text={repoItem.isArchived ? i18n.t('publication.state.archived') : RepoItemHelper.getStatusText(repoItem.status)}/>
                </div>
                {repoItem.status === "Declined" &&
                    <div className={"decline-reason-text"} onClick={() => VerificationPopup.show(t("publication.decline_reason_popup.title"), repoItem.declineReason, () => {}, true, null, true)}>{t("publication.decline_reason")}</div>
                }
            </div>
        </div>
    }

    function handleScroll() {
        if (!scrollSections) {
            scrollSectionsContainer = contentRef.current.querySelector("#surf-form-edit-publication-form-id .form-sections-container")
            scrollSections = contentRef.current.querySelectorAll("#surf-form-edit-publication-form-id .form-sections-container > .form-section")
        }
        const scrollBottom = (contentRef.current.scrollTop + contentRef.current.clientHeight)
        const targetOffset = (contentRef.current.clientHeight / 2)
        let activeSectionIndex = 0;
        for (const sectionRef of scrollSections) {
            if (scrollBottom < (sectionRef.offsetTop + targetOffset)) {
                activeSectionIndex = activeSectionIndex - 1
                break;
            }
            activeSectionIndex++;
        }
        activeSectionIndex = Math.min(activeSectionIndex, (repoItem.sections.length - 1))
        if (scrollBottom >= (scrollSectionsContainer.offsetTop + scrollSectionsContainer.clientHeight)) {
            activeSectionIndex = (scrollSections.length - 1)
        }
        setActiveSection(repoItem.sections[activeSectionIndex])
    }


    return (
        <Page id="add-publication"
              contentRef={contentRef}
              onScroll={handleScroll}
              history={props.history}
              activeMenuItem={"publications"}
              breadcrumbs={[
                  {
                      path: '../dashboard',
                      title: 'side_menu.dashboard'
                  },
                  {
                      path: '../publications',
                      title: 'side_menu.my_publications'
                  },
                  {
                      path: './publications',
                      title: repoItem === null ? '' : (repoItem.title && repoItem.title !== "") ? repoItem.title : 'add_publication.popup.title'
                  }
              ]}
              content={content}
              showBackButton={true}/>
    );

    function deletePublication() {
        VerificationPopup.show(t("publication.delete_popup.title"), t("publication.delete_popup.subtitle"), () => {
            GlobalPageMethods.setFullScreenLoading(true)
            deleteRepoItem(repoItem.id, history, (responseData) => {
                reset();
                GlobalPageMethods.setFullScreenLoading(false)
                props.history.replace('/publications');
            }, () => {
                GlobalPageMethods.setFullScreenLoading(false)
                Toaster.showDefaultRequestError();
            })
        })
    }

    function saveForm(formData) {
        const currentRepoItem = repoItem;
        GlobalPageMethods.setFullScreenLoading(true)
        patchRepoItem(currentRepoItem, formData, repoItem.status);
    }

    function patchFormToDraft() {
        GlobalPageMethods.setFullScreenLoading(true)
        patchRepoItem(repoItem, null, "Draft");
    }

    function changeFormPublishState(formData) {
        const currentRepoItem = repoItem;
        let newStatus;

        if(formSubmitActionType != null) {
            currentRepoItem.declineReason = ""
            if (formSubmitActionType === 'approve') {
                newStatus = 'Approved'
            } else if (formSubmitActionType === 'decline') {
                if(!declineReason) {
                    VerificationPopup.show(t("publication.missing_decline_reason_popup.title"), t("publication.missing_decline_reason_popup.subtitle"), () => {}, true)
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

    function formFieldValueChanged(field, changedValue) {
        const fieldType = formFieldHelper.getFieldType(field.fieldType);

        if (fieldType === 'attachment' || fieldType === 'personinvolved' ||
            fieldType === 'repoitemlink' || fieldType === 'repoitemlearningobject') {
            const action = changedValue;
            if (action.type === 'create') {
                const repoTypeMap = {
                    'attachment': 'RepoItemRepoItemFile',
                    'personinvolved': 'RepoItemPerson',
                    'repoitemlink': 'RepoItemLink',
                    'repoitemlearningobject': 'RepoItemLearningObject'
                };

                let fieldValuesAsString = getValues()[field.key];
                let fieldValuesAsArray = []
                if (fieldValuesAsString !== undefined && fieldValuesAsString !== null && fieldValuesAsString !== '') {
                    fieldValuesAsArray = JSON.parse(fieldValuesAsString).map(ri => ri.summary.repoItem ? ri.summary.repoItem.id : ri.id)
                }
                createRelatedRepoItem(repoTypeMap[fieldType], repoItem.relatedTo.id, history, createdRepoItem => showRepoItemPopup(
                    formReducerStateRef.current,
                    field,
                    createdRepoItem.id,
                    action.value,
                    newValues => {
                        dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                        updateProgressPerSection();
                        console.log(errors)
                        if (Object.keys(errors).length > 0) {
                            trigger();
                        }
                    },
                    repoItem,
                    fieldValuesAsArray
                ))
            } else if (action.type === 'edit') {
                showRepoItemPopup(formReducerStateRef.current, field, action.value, null, newValues => {
                        dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                        setUpdateProgress(!updateProgress)
                    }
                    , repoItem
                )
            } else if (action.type === 'delete') {
                removeRelatedRepoItem(formReducerStateRef.current, field, action.value,
                    newValues => {
                        dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                        setUpdateProgress(!updateProgress)
                    })
            } else if (action.type === 'sort change') {
                setRelatedRepoItemOrder(formReducerStateRef.current, field, action.value,
                    newValues => {
                        dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                        setUpdateProgress(!updateProgress)
                    })
            }
        }
        if (fieldType === 'tree-multiselect') {
            const action = changedValue;
            if (action.type === 'delete') {
                removeValues(formReducerStateRef.current, field, action.value,
                    newValues => {
                        dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                        setUpdateProgress(!updateProgress)
                    })
            } else if (action.type === 'create') {
                createValues(formReducerStateRef.current, field, action.value,
                    (newValues) => {
                        dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                        setUpdateProgress(!updateProgress)
                    })
            }
        } else {
            if (hadErrors.current) {
                trigger()
            }
            setUpdateProgress(!updateProgress)
        }
    }

    function updateProgressPerSection() {
        const repoItemCopy = JSON.parse(JSON.stringify(repoItem));
        repoItemCopy.answers = formFieldHelper.getAllFormAnswersForRepoItem(repoItemCopy, getValues());

        const newProgress = repoItemCopy.sections.map(section => {
            let progress = RepoItemHelper.getProgressForSection(repoItemCopy, section);
            if (progress > 0 && section.isUsedForSelection) {
                progress = 100
            }
            progress = Math.round(progress)
            return {
                section: section,
                progress: progress
            }
        });

        setRepoItemAnswers(repoItemCopy.answers)
        setProgressPerSection(newProgress);
    }

    function getRepoItem() {

        function onValidate(response) {
        }

        function onSuccess(response) {
            const receivedRepoItem = response.data;
            let formFieldValueState = {};

            RepoItemHelper.getAllFields(receivedRepoItem).forEach((field) => {
                let fieldAnswer = formFieldHelper.getFieldAnswer(receivedRepoItem, field)
                formFieldValueState = {
                    ...formFieldValueState,
                    [field.key]: fieldAnswer
                }
            });

            dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: formFieldValueState});
            setRepoItem(receivedRepoItem);
        }

        function onServerFailure(error) {
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                props.history.push('/login?redirect=' + window.location.pathname);
            } else if (error.response.status === 403) { //We're not authorized to see this page
                props.history.replace('/forbidden')
            } else if (error && error.response && (error.response.status === 423)) { //The object is inaccesible
                props.history.replace('/removed');
            } else {
                Toaster.showServerError(error)
            }
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError();
        }

        repoItemApiRequests.getRepoItem(props.match.params.id, onValidate, onSuccess, onLocalFailure, onServerFailure, ['relatedTo.lastEditor','creator'])
    }

    function patchRepoItem(currentRepoItem, formData, status) {
        const answers = (formData) ? formFieldHelper.getAllFormAnswersForRepoItem(repoItem, formData) : null;

        const shouldCheckChannelDependencyErrors = ValidationHelper.shouldCheckChannelDependencyErrors(status)

        if (shouldCheckChannelDependencyErrors) {
            let validationResult = ValidationHelper.hasChannelDependencyErrors(answers, repoItem);

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
            setRepoItem(repo)
            setProgressPerSection(repo.sections.map(section => {
                let progress = RepoItemHelper.getProgressForSection(repo, section);
                if (progress > 0 && section.isUsedForSelection) {
                    progress = 100
                }
                progress = Math.round(progress)
                return {
                    section: section,
                    progress: progress
                }
            }))
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                props.history.push('/login?redirect=' + window.location.pathname);
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
            "repoType": repoItem.repoType,
            "declineReason": repoItem.declineReason
        }
        if (answers) {
            attributes["answers"] = answers;
        }

        const patchData = {
            "data": {
                "type": "repoItem",
                "id": repoItem.id,
                "attributes": attributes
            }
        };

        const url = "repoItems/" + currentRepoItem.id
        Api.patch(url, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData)
    }
}

export default EditPublication;