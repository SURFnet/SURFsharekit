import React, {useEffect, useRef, useState} from "react";
import '../publish-publication-view/publish-publication.scss'
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import Page, {GlobalPageMethods} from "../components/page/Page";
import {Link, Redirect, useHistory, useLocation} from "react-router-dom";
import {useTranslation} from "react-i18next";
import styled from "styled-components";
import {Form, FormSection} from "../components/field/FormField";
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
import {HelperFunctions} from "../util/HelperFunctions";
import VerificationPopup from "../verification/VerificationPopup";
import {deleteRepoItem, requestDeleteRepoItem} from "../components/reacttable/tables/publication/ReactPublicationTable";
import {useDirtyNavigationCheck} from "../util/hooks/useDirtyNavigationCheck";
import ValidationHelper, {VALIDATION_RESULT} from "../util/ValidationHelper";
import {
    desktopSideMenuWidth,
    greyMedium,
    majorelle,
    majorelleLight, mobileTabletMaxWidth,
    nunitoBold,
    roundedBackgroundPointyUpperLeft,
    spaceCadet,
    spaceCadetLight,
    white
} from "../Mixins";
import {ThemedButton} from "../Elements";
import {useGlobalState} from "../util/GlobalState";
import SURFButton from "../styled-components/buttons/SURFButton";
import DefaultPublicationViewHeader from "./DefaultPublicationViewHeader";
import ReviewFooter from "../styled-components/footer/ReviewFooter";
import {TASK_ACTION} from "../util/TaskHelper";
import axios from "axios";
import {SwalRepoItemPopup} from "../components/field/relatedrepoitempopup/RelatedRepoItemPopup";
import i18n from "../i18n";

function DefaultPublicationView(props) {
    const contentRef = useRef();
    const {t} = useTranslation();
    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);
    const [user] = useAppStorageState(StorageKey.USER);
    const [repoItemAnswers, setRepoItemAnswers] = useState(null);
    const [isEditing, setIsEditing] = useState(false);
    const [isReviewing, setIsReviewing] = useState(false)
    const repoItemApiRequests = new RepoItemApiRequests();
    const {formState, register, handleSubmit, errors, setValue, getValues, trigger, reset, clearErrors} = useForm();
    const {dirtyFields} = formState
    const history = useHistory()
    const formFieldHelper = new FormFieldHelper();
    const publicationHeaderRef = useRef(0);
    const [formSubmitActionType, setFormSubmitActionType] = useState(null);
    const [declineReason, setDeclineReason] = useState("");
    const [marginTop, setMarginTop] = useState(0);
    const isProject = !!(history.location.state && history.location.state.isProject)
    const [showOnlyRequiredFields, setShowOnlyRequiredFields] = useState(0)

    const location = useLocation();
    const queryParams = new URLSearchParams(location.search);

    let scrollSectionsContainer = null
    let scrollSections = null

    const formIsDraft = props.repoItem ? props.repoItem.status.toLowerCase() === "draft" : null
    const formIsRevising = props.repoItem ? props.repoItem.status.toLowerCase() === "revising" : null
    const formIsSubmitted = props.repoItem ? props.repoItem.status.toLowerCase() === "submitted" : null
    const formIsDeclined = props.repoItem ? props.repoItem.status.toLowerCase() === "declined" : null
    const permissionCanPublish = props.repoItem ? props.repoItem.permissions.canPublish && !props.repoItem.isRemoved : null
    const needsToBeFinished = props.repoItem ? props.repoItem.needsToBeFinished : false;

    const formReducerStateRef = useRef();
    formReducerStateRef.current = props.formReducerState

    let repoItemSections = props.repoItem ? RepoItemHelper.getSectionsFromSteps(props.repoItem) : [];
    let hasUncompletedReviewTasks = props.repoItem && props.repoItem.tasks && props.repoItem.tasks.length > 0

    useDirtyNavigationCheck(history, dirtyFields)

    useEffect(() => {
        if (props.repoItem){
            if (props.repoItem.title !== null) {
                document.title = props.repoItem.title
            } else {
                document.title = "Concept"
            }

            const status = props.repoItem.status

            if (status === "Revising") {
                setIsEditing(true)
            }

            if (isReviewing && status !== "Submitted" && status !== "Revising") {
                setIsReviewing(false)
            }

        } else {
            document.title = "Loading..."
        }

    }, [props.repoItem])

    useEffect(() => {
        if (formSubmitActionType === "publish"){
            changeFormPublishState(getValues())
        }
    }, [formSubmitActionType]);

    useEffect(() => {
        setIsSideMenuCollapsed(true)
    }, [])

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
        if (publicationHeaderRef.current) {
            if (publicationHeaderRef.current.clientHeight >= 160) {
                setMarginTop((publicationHeaderRef.current.clientHeight / 2) + 15)
            } else {
                setMarginTop((publicationHeaderRef.current.clientHeight / 2))
            }
        }
    }, [props.repoItem, publicationHeaderRef, marginTop]);

    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            VerificationPopup.show(t("publication.required_error.title"), t("publication.required_error.subtitle"), () => {
            }, true)
        }
    }, [errors])

    if (!user) {
        return <Redirect to={'/unauthorized?redirect=publications/' + props.match.params.id}/>
    }

    let content;
    if (!props.repoItem) {
        content = <LoadingIndicator centerInPage={true}/>
    } else {
        content = getPageContent();
    }

    function getPageContent() {
        const permissionCanEdit = props.repoItem.permissions.canEdit && !props.repoItem.isRemoved
        const permissionCanPublish = props.repoItem.permissions.canPublish && !props.repoItem.isRemoved
        const permissionCanDelete = props.repoItem.permissions.canDelete

        // Only make fields editable when item has draft or revising status
        const formIsDraft = props.repoItem.status.toLowerCase() === "draft"
        const formIsRevising = props.repoItem.status.toLowerCase() === "revising"
        const formEditable = permissionCanEdit && (formIsDraft || formIsRevising)

        return (
            <>
                <FormContainer marginTop={marginTop} className={"form-elements-container"}>
                    <div className={"left-pane-container"}>
                        <RepoItemDetails/>
                    </div>

                    <Spacer/>

                    <Form formId={"edit-publication-form-id"}
                          isPublicationFlow={false}
                          isEditing={(isEditing || isReviewing)}
                          repoItem={props.repoItem}
                          showSectionHeaders={true}
                          errors={errors}
                          onValueChanged={formFieldValueChanged}
                          onSubmit={handleSubmit(() => changeFormPublishState(getValues()))}
                          register={register}
                          setValue={setValue}
                          formReducerState={formReducerStateRef.current}
                          readonly={!isEditing}
                          showOnlyRequiredFields={showOnlyRequiredFields}/>
                </FormContainer>
            </>
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
        const createdFormatted = HelperFunctions.getDateFormat(props.repoItem.createdLocal, dateFormatOptions)
        const lastEditedFormatted = HelperFunctions.getDateFormat(props.repoItem.lastEditedLocal, dateFormatOptions)

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

        return <div className={`repo-item-details ${props.repoItem.status === "Declined" ? "rejected" : ""}`}>

            <div className={"section"}>
                <div className={"section-title"}>{t("publication.created")}</div>
                <div
                    className={"date"}>{`${createdFormatted.day}-${createdFormatted.month}-${createdFormatted.year} ${createdFormatted.hour}:${createdFormatted.minute}`}</div>
                <CreatedLastEditedPersonLink person={props.repoItem.creator}/>
            </div>

            <div className={"section"}>
                <div className={"section-title"}>{t("publication.lastEdited")}</div>
                <div
                    className={"date"}>{`${lastEditedFormatted.day}-${lastEditedFormatted.month}-${lastEditedFormatted.year} ${lastEditedFormatted.hour}:${lastEditedFormatted.minute}`}</div>
                <CreatedLastEditedPersonLink person={props.repoItem.lastEditor}/>
            </div>

            <div className={"section"}>
                <div className={"section-title"}>{t("publication.organisation")}</div>
                <div className={"date"}>{props.repoItem.relatedTo.title}</div>
            </div>

            <div className={"section"}>
                {props.repoItem.status === "Declined" &&
                <div className={"decline-reason-text"}
                     onClick={() => VerificationPopup.show(t("publication.decline_reason_popup.title"), props.repoItem.declineReason, () => {
                     }, true, null, true)}>{t("publication.decline_reason")}</div>
                }
            </div>
        </div>
    }

    function getSaveButtonText() {
        if (props.repoItem) {
            if (props.repoItem.permissions.canEdit && props.repoItem.permissions.canDelete && props.repoItem.isRemoved) {
                return t("add_publication.button_undelete");
            } else if (isEditing) {
                return t("add_publication.button_save");
            } else {
                return t("add_publication.button_edit");
            }
        }
    }

    function getSaveAndPublishText() {
        if (props.repoItem) {
            if (!permissionCanPublish) {
                return t("add_publication.button_submit")
            }
            if (props.repoItem.isHistoricallyPublished && isEditing) {
                return t("add_publication.button_save_publish")
            } else {
                return t("add_publication.button_publish")
            }
        }
    }

    function handlePublish(){
        trigger().then(result => {
            if (result === false) {
                VerificationPopup.show(t("publication.validation_error.title"), t("publication.validation_error.subtitle"), () => {
                }, true)
            } else {
                setIsEditing(false);
                setFormSubmitActionType('publish');
            }
                handleSubmit(() => changeFormPublishState(getValues()))
        });
    }
    const saveEditButton = (
        <SURFButton
            disabled={isReviewing}
            backgroundColor={spaceCadet}
            highlightColor={spaceCadetLight}
            text={getSaveButtonText()}
            textSize={"14px"}
            textColor={white}
            padding={"0 30px"}
            onClick={() => {
                if (props.repoItem.isRemoved) {
                    restoreRepoItem()
                    return;
                }

                if (formIsDraft && !isEditing) {
                    if (needsToBeFinished) {
                        patchFormToDraft()
                    }
                    setIsEditing(!isEditing);
                    return;
                }

                if (formIsDraft && isEditing) {
                    saveForm(getValues())
                } else if (!formIsRevising && !formIsSubmitted) {
                    patchFormToDraft()
                }

                if (formIsRevising && isEditing) {
                    GlobalPageMethods.setFullScreenLoading(true)
                    trigger().then(result => {
                        if (result === true) {
                            patchRepoItem(props.repoItem, getValues(), "Submitted");
                            setIsEditing(!isEditing);
                        } else {
                            GlobalPageMethods.setFullScreenLoading(false)
                            VerificationPopup.show(t("publication.validation_error.title"), t("publication.validation_error.subtitle"), () => {}, true)
                            setIsEditing(isEditing);
                        }
                    });
                } else if (formIsSubmitted) {
                    patchFormToRevising()
                }

                if (!formIsRevising) {
                    setIsEditing(!isEditing);
                    setShowOnlyRequiredFields(0)
                }
            }}
        />
    )

    const saveAndPublishButton = (
        <SURFButton
            form="surf-form-edit-publication-form-id"
            backgroundColor={majorelle}
            highlightColor={majorelleLight}
            text={getSaveAndPublishText()}
            textSize={"14px"}
            textColor={white}
            padding={"0 30px"}
            onClick={() => {
                handlePublish()
                setShowOnlyRequiredFields(0)
            }}
        />
    );

    const reviseButton = (
        <SURFButton
            disabled={isReviewing || isEditing}
            backgroundColor={majorelle}
            highlightColor={majorelleLight}
            text={isReviewing ? t("add_publication.button_review_active") : t("add_publication.button_review")}
            textSize={"14px"}
            textColor={white}
            padding={"0 30px"}
            onClick={() => {
                setIsReviewing(!isReviewing)
            }}
        />
    );

    return (
        <>
            <Page id="add-publication"
                  fixedElements={[
                      <DefaultPublicationViewHeader
                          marginTop={marginTop}
                          repoItem={props.repoItem ? props.repoItem : null}
                          isEditing={isEditing}
                          saveAndPublishButton={saveAndPublishButton}
                          saveEditButton={saveEditButton}
                          reviseButton={reviseButton}
                          deletePublication={() => deletePublication()}
                          requestDeletePublication={() => requestDeletePublication()}
                          publicationHeaderRef={publicationHeaderRef}
                          showOnlyRequiredFields={showOnlyRequiredFields}
                          setShowOnlyRequiredFields={setShowOnlyRequiredFields}
                      />
                  ]}
                  contentRef={contentRef}
                  history={props.history}
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

            { isReviewing &&
                <ReviewFooter
                    onStop={() => {
                        setIsReviewing(false)
                    }}
                    onApprove={() => {
                        GlobalPageMethods.setFullScreenLoading(true);
                        if (hasUncompletedReviewTasks) {
                            patchTask(props.repoItem.tasks[0].id, TASK_ACTION.APPROVE);
                        } else {
                            patchRepoItem(props.repoItem, null, "Approved");
                        }
                    }}
                    onDecline={(declineReason) => {
                        GlobalPageMethods.setFullScreenLoading(true)
                        let repoItem = props.repoItem
                        repoItem.declineReason = declineReason
                        if(hasUncompletedReviewTasks) {
                            patchTask(repoItem.tasks[0].id, TASK_ACTION.DECLINE, declineReason);
                        } else {
                            patchRepoItem(repoItem, null, "Declined");
                        }
                    }}
                />
            }
        </>
    );

    function requestDeletePublication() {
        requestDeleteRepoItem(props.repoItem.id, history, () => {
            reset();
            Toaster.showToaster({type: "success", message: i18n.t("toast.repo_item.delete_request_success")})
            history.replace("/publications");
        })
    }

    function deletePublication() {
        const title = isProject ? t("projects.delete_popup.title") : t("publication.delete_popup.title")
        const subtitle = isProject ? t("projects.delete_popup.subtitle") : t("publication.delete_popup.subtitle")

        const deleteFunction = () => deleteRepoItem(props.repoItem.id, history, (responseData) => {
            reset();
            GlobalPageMethods.setFullScreenLoading(false)
            if (isProject) {
                history.replace('/projects')
            } else {
                history.replace('/publications')
            }
        }, (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError();
        })

        if (!formIsDraft) {
            VerificationPopup.show(title, subtitle, () => {
                GlobalPageMethods.setFullScreenLoading(true)
                patchRepoItem(props.repoItem, null, "Draft", deleteFunction);
            })
        } else {
            VerificationPopup.show(title, subtitle, () => {
                GlobalPageMethods.setFullScreenLoading(true)
                deleteFunction()
            })
        }
    }

    function restoreRepoItem() {
        VerificationPopup.show(t("verification.restore.title"), "", () => {
            const config = {
                headers: {
                    "Content-Type": "application/vnd.api+json",
                },
            };
            const patchData = {
                "data": {
                    "type": 'repoItem',
                    "id": props.repoItem.id,
                    "attributes": {
                        "isRemoved": false
                    }
                }
            }

            function onValidate(response) {
            }

            function onSuccess(response) {
                GlobalPageMethods.setFullScreenLoading(false)
                const repo = Api.dataFormatter.deserialize(response.data);
                props.onRepoItemChanged(repo)
            }

            function onFailure(error) {
                GlobalPageMethods.setFullScreenLoading(false)
                Toaster.showDefaultRequestError()
            }

            GlobalPageMethods.setFullScreenLoading(true)
            Api.patch(`repoItems/${props.repoItem.id}`, onValidate, onSuccess, onFailure, onFailure, config, patchData);
        })
    }

    function saveForm(formData) {
        const currentRepoItem = props.repoItem;
        GlobalPageMethods.setFullScreenLoading(true)
        patchRepoItem(currentRepoItem, formData, props.repoItem.status);
    }

    function patchFormToDraft() {
        GlobalPageMethods.setFullScreenLoading(true)
        patchRepoItem(props.repoItem, null, "Draft", () => {}, true);
    }

    function patchFormToRevising() {
        GlobalPageMethods.setFullScreenLoading(true)
        patchRepoItem(props.repoItem, null, "Revising", () => {}, true);
    }

    function changeFormPublishState(formData) {
        const currentRepoItem = props.repoItem;
        let newStatus;

        if (formSubmitActionType !== null) {
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

            if (newStatus === 'Submitted' && currentRepoItem.status !== 'Revising') { //Trying to publish
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
                    newValues => {
                        props.dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues})
                    })
            }
        }
    }

    function patchRepoItem(currentRepoItem, formData, status, onSuccessCallback = () => {}, disableToaster = false) {
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
            const repoItem = Api.dataFormatter.deserialize(response.data);

            if (!disableToaster) {
                Toaster.showToaster({message: t("publication.request_save_publication_success")})
            }

            props.onRepoItemChanged(repoItem)

            onSuccessCallback()
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
            "declineReason": props.repoItem.declineReason,
            "needsToBeFinished": false
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

    function patchTask(taskId, action, reasonOfDecline = null) {

        GlobalPageMethods.setFullScreenLoading(true)

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            history.go(0)
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            if(error.response.status === 404) {
                Toaster.showToaster({type: "info", message: t("dashboard.tasks.not_found")})
            } else {
                Toaster.showServerError(error)
            }
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
            }
        }

        // TODO, if taskId is empty, use repoItem to patch task or ignore patch
        const patchData = {
            "data": {
                "type": "task",
                "id": taskId,
                "attributes": {
                    "action": action,
                    "reasonOfDecline": reasonOfDecline
                }
            }
        };

        const url = "tasks/" + taskId
        Api.patch(url, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData)
    }
}

const Spacer = styled.div`
    width: 242px;

    @media only screen and (max-width: ${mobileTabletMaxWidth}px) {
    width: 0;
    }
`;


const ThemedFieldSet = styled.fieldset`
    display: flex;
    align-items: baseline;
    justify-content: end;
    gap: 60px;
    
    :checked + label:before {
        border: 5.04px solid #F3BA5A;
    }
    //hide the original checkbox by just moving it waaaaaay left
    [type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    //show the label on a location relative to new button
    label {
        @include open-sans;
        font-size: 14px;
        position: relative;
        line-height: $selectable-size;
        padding-left: $selectable-size + 10px;
        display: inline-block;
        color: $text-color;
    }

    .option:first-child {
        margin-top: 5px;
    }

    .option:not(:first-child) {
        margin-top: 10px;
    }
`;

const StepFooterContainer = styled.div`
    width: ${props => props.isSideMenuCollapsed ? "100%" : `calc(100% - ${desktopSideMenuWidth})`};
    margin-left: ${props => props.isSideMenuCollapsed ? 0 : desktopSideMenuWidth};
    height: 95px;
    position: fixed;
    bottom: 0;
    left: 0;
    background-color: white;
    transition: margin width 0.2s ease;
`;

const StepFooterProgressBar = styled.div`
    width: 100vw;
    height: 5px;
    background-color: ${greyMedium};
`;

const StepFooterIndicators = styled.div`
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    height: 100%;
`;

const Steps = styled.div``;

const DeclineOrApproveTitle = styled.div`
    display: flex;
    flex-grow: 1;
    color: white;
    ${nunitoBold()}
    font-size: 25px;
`

const Label = styled.label`
    color: white;
    font-weight: bold;
`;

const RadioLabel = styled(Label)`
    font-size: 14px;
    position: relative;
    line-height: 18px;
    padding-left: calc(18px + 10px);
    display: inline-block;
        
    //show new checkbox
    :before {
        content: '';
        position: absolute;
        cursor: pointer;
        left: 0;
        top: 0;
        width: 18px;
        height: 18px;
        border: 1px solid #E5E5E5;
        border-radius: 50%;
        background-color: #F8F8F8;
    }

    :after {
        width: 18px;
        height: 18px;
        position: absolute;
        top: 0;
        left: 0;
    }
`

const DeclineReasonWrapper = styled.div`
    display: flex;
    flex-direction: column;
    margin-top: 30px;
`;

const ThemedTextArea = styled.textarea`
    background: white;
    border-radius: 2px 15px 15px;
    border: 1px solid #E5E5E5;
    font-family: 'Open Sans', sans-serif;
    font-weight: 400;
    font-size: 12px;
    line-height: 14px;
    vertical-align: center;
    height: 100px;
    width: 500px;
    margin-top: 5px;
    padding: 12px;
    outline: none;
    resize: none;
    &:focus {
        border: 1px solid $ocean-green;
    }
`

const ThemedOption = styled.div`
    transform: scale(1.2) translateX(-9px);
`

const ThemedRadio = styled.input`
    display: flex;
    flex-direction: column;

    :checked + label:before {
        border: 5.04px solid #F3BA5A;
    }

    :checked + label:before {
        border: ($selectable-size * 0.28) solid $text-color-active;
    }

    :disabled + label:before {
        opacity: 0.33;
        cursor: auto;
    }
`

const Inputs = styled.div`
    display: flex;
    flex-direction: column;
`

const ApproveOrDeclineButton = styled(ThemedButton)`
    margin-top: 30px;
    width: 130px;
    height: 40px;
    ${roundedBackgroundPointyUpperLeft()}
    color: white;
    ${nunitoBold()}
    font-size: 14px;
    line-height: 19px;
    background-color: #64C3A5;
    align-self: end;
    ${props => props.enabled ? '' : 'background: #A5AAAE;'};
`

const DeclineOrApprovedFormSection = styled(FormSection)`
    display: flex;
    min-height: 120px;
    background-color: #7344EE;
`

export const FormContainer = styled.div`
    margin-top: ${props => `${props.marginTop}px`};
      padding-left: 80px;
      padding-right: 80px;
`;

export default DefaultPublicationView;