import axios from "axios";
import Api from "../util/api/Api";
import DefaultPublicationViewHeader from "./DefaultPublicationViewHeader";
import FormFieldHelper from "../util/FormFieldHelper";
import {Form} from "../components/field/FormField";
import {HelperFunctions} from "../util/HelperFunctions";
import i18n from "../i18n";
import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import {
    majorelle,
    majorelleLight,
    mobileTabletMaxWidth,
    spaceCadet,
    spaceCadetLight,
    white
} from "../Mixins";
import Page, {GlobalPageMethods} from "../components/page/Page";
import '../publish-publication-view/publish-publication.scss';
import React, {useEffect, useRef, useState} from "react";
import ReactRouterPrompt from "react-router-prompt";
import {
    archiveRepoItem,
    deleteRepoItem,
    requestDeleteRepoItem
} from "../components/reacttable/tables/publication/ReactPublicationTable";
import {
    createRelatedRepoItem,
    createValues,
    removeRelatedRepoItem,
    removeValues,
    setRelatedRepoItemOrder,
    showRepoItemPopup
} from "../components/field/repoitem/RelatedRepoItemHandles";
import RepoItemHelper from "../util/RepoItemHelper";
import RestorePublicationPopup from "../publications/restorepublicationpopup/RestorePublicationPopup";
import ReviewFooter from "../styled-components/footer/ReviewFooter";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import styled from "styled-components";
import SURFButton from "../styled-components/buttons/SURFButton";
import {SwalRepoItemPopup} from "../components/field/relatedrepoitempopup/RelatedRepoItemPopup";
import {TASK_ACTION} from "../util/TaskHelper";
import Toaster from "../util/toaster/Toaster";
import {useForm} from "react-hook-form";
import {useGlobalState} from "../util/GlobalState";
import {Link, Navigate, useLocation, useParams} from "react-router-dom";
import {useNavigation} from "../providers/NavigationProvider";
import {useTranslation} from "react-i18next";
import ValidationHelper, {VALIDATION_RESULT} from "../util/ValidationHelper";
import VerificationPopup from "../verification/VerificationPopup";
import {ReactComponent as IconPencil } from "../resources/icons/ic-pencil.svg";
import UpdateOrganisationPopup from "./update-organisation-popup/UpdateOrganisationPopup";

function DefaultPublicationView(props) {
    const contentRef = useRef();
    const {t} = useTranslation();
    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);
    const [user] = useAppStorageState(StorageKey.USER);
    const [isEditing, setIsEditing] = useState(false);
    const [isReviewing, setIsReviewing] = useState(false)
    const {
        formState,
        register,
        handleSubmit,
        formState: {errors, isDirty},
        setValue,
        getValues,
        trigger,
        reset,
        clearErrors
    } = useForm({
        shouldUnregister: false,
        defaultValues: {}
    });
    const {dirtyFields} = formState
    const navigate = useNavigation()
    const params = useParams()
    const location = useLocation()
    const formFieldHelper = new FormFieldHelper();
    const publicationHeaderRef = useRef(0);
    const [formSubmitActionType, setFormSubmitActionType] = useState(null);
    const [declineReason, setDeclineReason] = useState("");
    const [marginTop, setMarginTop] = useState(0);
    const isProject = !!(location.state && location.state.isProject)
    const [showOnlyRequiredFields, setShowOnlyRequiredFields] = useState(0)
    const queryParams = new URLSearchParams(location.search);

    const formIsDraft = props.repoItem ? props.repoItem.status.toLowerCase() === "draft" : null
    const formIsRevising = props.repoItem ? props.repoItem.status.toLowerCase() === "revising" : null
    const formIsSubmitted = props.repoItem ? props.repoItem.status.toLowerCase() === "submitted" : null
    const permissionCanPublish = props.repoItem ? props.repoItem.permissions.canPublish && !props.repoItem.isRemoved : null
    const needsToBeFinished = props.repoItem ? props.repoItem.needsToBeFinished : false;

    const formReducerStateRef = useRef();
    formReducerStateRef.current = props.formReducerState

    let repoItemSections = props.repoItem ? RepoItemHelper.getSectionsFromSteps(props.repoItem) : [];
    let hasUncompletedReviewTasks = props.repoItem && props.repoItem.tasks && props.repoItem.tasks.length > 0

    useEffect(() => {
        if (props.repoItem) {
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
        setIsSideMenuCollapsed(true)
    }, [])

    useEffect(() => {
        if (formSubmitActionType === "publish") {
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

            // Only reset if there are actual differences to prevent unnecessary dirty state changes
            const currentValues = getValues();
            const hasChanges = Object.keys(newFormData).some(key => {
                const currentValue = currentValues[key];
                const newValue = newFormData[key];

                // Handle null/undefined comparisons
                if (currentValue == null && newValue == null) return false;
                if (currentValue == null || newValue == null) return true;

                return currentValue !== newValue;
            });

            if (hasChanges || Object.keys(currentValues).length === 0) {
                reset(newFormData);
            }
        }

    }, [props.repoItem])

    // Additional useEffect to handle dirty state reset after form initialization
    useEffect(() => {
        if (props.repoItem && !isEditing && !isReviewing) {
            // Small delay to ensure form is fully initialized before clearing dirty state
            const timer = setTimeout(() => {
                reset(getValues(), {keepValues: true, keepDirty: false});
            }, 100);

            return () => clearTimeout(timer);
        }
    }, [props.repoItem, isEditing, isReviewing]);

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
        if (errors && Object.keys(errors).length > 0) {
            VerificationPopup.show(
                t("publication.required_error.title"),
                t("publication.required_error.subtitle"),
                () => {
                },
                true)
        }
    }, [errors])

    if (!user) {
        return <Navigate to={'/unauthorized?redirect=publications/' + params.id}/>
    }

    let content;
    if (!props.repoItem) {
        content = <LoadingIndicator centerInPage={true}/>
    } else {
        content = getPageContent();
    }

    function getPageContent() {
        const permissionCanEdit = props.repoItem.permissions.canEdit && !props.repoItem.isRemoved
        const permissionCanMoveRepoItem = props.repoItem.permissions.canMoveRepoItem && !props.repoItem.isRemoved

        return (
            <>
                <FormContainer marginTop={marginTop} className={"form-elements-container"}>
                    <div className={"left-pane-container"}>
                        <RepoItemDetails
                            canMoveRepoItem={permissionCanMoveRepoItem}
                        />
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
                          getValues={getValues}
                          formReducerState={formReducerStateRef.current}
                          readonly={!isEditing || !permissionCanEdit}
                          showOnlyRequiredFields={showOnlyRequiredFields}/>
                </FormContainer>
            </>
        )
    }

    function RepoItemDetails({ canMoveRepoItem }) {
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

        const relatedInstitute = props.repoItem?.relatedTo;
        const relatedInstituteTitle = relatedInstitute?.title ?? 'N/A';

        return <div className={`repo-item-details ${(props.repoItem.status === "Declined") ? "rejected" : ""}`}>

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

            {relatedInstitute &&
                <div className={"section"}>
                    <div className={"section-title"}>{t("publication.organisation")}</div>
                    <div className={"flex-row"}>
                        <div className={"date"}>{relatedInstituteTitle}</div>
                        {canMoveRepoItem && relatedInstitute?.id &&
                            <IconPencil
                                className={"pencil-icon"}
                                onClick={(e) => {
                                    e.stopPropagation()
                                    UpdateOrganisationPopup.show(relatedInstitute.id, (selectedInstitute) => {
                                        updateRepoItemWithNewInstitute(props.repoItem, selectedInstitute)
                                    })
                                }}
                            />
                        }
                    </div>
                </div>
            }

            <div className={"section"}>
                {(props.repoItem.status === "Declined" || props.repoItem.deletionHasBeenDeclined) ?
                    <div className={"decline-reason-text"}
                         onClick={() => VerificationPopup.show(props.repoItem.deletionHasBeenDeclined ? t("publication.delete_request_declined_popup.title") : t("publication.decline_reason_popup.title"), props.repoItem.declineReason, () => {
                         }, true, null, true)}>{props.repoItem.deletionHasBeenDeclined ? t("publication.decline_delete_reason") : t("publication.decline_reason")}</div>
                    :
                    <></>
                }
            </div>

            <div className={"section"}>
                {(props.repoItem.permissions.canEdit && props.repoItem.permissions.canDelete && !!props.repoItem.isRemoved) ?
                    <div className={"delete-reason-text"}
                         onClick={() => VerificationPopup.show(t("publication.delete_reason_popup.title"), props.repoItem.deleteReason, () => {
                         }, true, null, true)}>{t("publication.delete_reason")}</div>
                    :
                    <></>
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

    function handlePublish() {
        trigger().then(result => {
            if (result === false) {
                VerificationPopup.show(t("publication.validation_error.title"), t("publication.validation_error.subtitle"), () => {
                }, true)
            } else {
                setIsEditing(false);
                setFormSubmitActionType('publish');
            }
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
                            VerificationPopup.show(t("publication.validation_error.title"), t("publication.validation_error.subtitle"), () => {
                            }, true)
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
            <ReactRouterPrompt when={isDirty}>
                {({onConfirm, onCancel}) => {
                    VerificationPopup.show(
                        t("verification.unsaved_changes.title"),
                        t("verification.unsaved_changes.subtitle"),
                        onConfirm,
                        onCancel
                    );
                    return null;
                }}
            </ReactRouterPrompt>
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
                          archivePublication={() => archivePublication()}
                          restorePublicationFromArchive={() => restorePublicationFromArchive()}
                          requestDeletePublication={(reason) => requestDeletePublication(reason)}
                          publicationHeaderRef={publicationHeaderRef}
                          showOnlyRequiredFields={showOnlyRequiredFields}
                          setShowOnlyRequiredFields={setShowOnlyRequiredFields}
                      />
                  ]}
                  contentRef={contentRef}
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

            {isReviewing &&
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
                        if (hasUncompletedReviewTasks) {
                            patchTask(repoItem.tasks[0].id, TASK_ACTION.DECLINE, declineReason);
                        } else {
                            patchRepoItem(repoItem, null, "Declined");
                        }
                    }}
                />
            }
        </>
    );

    function requestDeletePublication(reason) {
        requestDeleteRepoItem(props.repoItem.id, reason, navigate, () => {
            reset();
            Toaster.showToaster({type: "success", message: i18n.t("toast.repo_item.delete_request_success")})
            navigate("/publications");
        }, (error) => {
            Toaster.showServerError(error);
        })
    }

    function deletePublication() {
        const title = isProject ? t("projects.delete_popup.title") : t("publication.delete_popup.title")
        const subtitle = isProject ? t("projects.delete_popup.subtitle") : t("publication.delete_popup.subtitle")

        const deleteFunction = () => deleteRepoItem(props.repoItem.id, navigate, (responseData) => {
            reset();
            GlobalPageMethods.setFullScreenLoading(false)
            if (isProject) {
                navigate('/projects')
            } else {
                navigate('/publications')
            }
        }, (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error);
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

    function archivePublication() {
        const title = isProject ? t("projects.archive_popup.title") : t("publication.archive_popup.title")
        const subtitle = isProject ? t("projects.archive_popup.subtitle") : t("publication.archive_popup.subtitle")

        const archiveFunction = () => archiveRepoItem(props.repoItem.id, navigate, (responseData) => {
            reset();
            GlobalPageMethods.setFullScreenLoading(false)
            if (isProject) {
                navigate('/projects')
            } else {
                navigate('/publications')
            }
        }, (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error);
        })


        VerificationPopup.show(title, subtitle, () => {
            GlobalPageMethods.setFullScreenLoading(true)
            archiveFunction()
        })
    }

    function restorePublicationFromArchive() {
        const title = t("publication.restore_from_archive_popup.title")
        const subtitle = t("publication.restore_from_archive_popup.subtitle")

        VerificationPopup.show(title, subtitle, () => {
            GlobalPageMethods.setFullScreenLoading(true)

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
                        "status": "Draft"
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
                Toaster.showServerError(error)
            }

            GlobalPageMethods.setFullScreenLoading(true)
            Api.patch(`repoItems/${props.repoItem.id}`, onValidate, onSuccess, onFailure, onFailure, config, patchData);
        })
    }

    function restoreRepoItem() {
        const sourceFromUrl = queryParams.get('source');
        const isFromTask = sourceFromUrl === 'tasks' || props.repoItem?.tasks?.length > 0;
        const task = props.repoItem.recoverTasks[0];

        if (isFromTask && task) {
            // If we're restoring from a task, use patchTask
            const confirmAction = (reason) => {
                patchTask(task.id, TASK_ACTION.DECLINE, reason);
            };
            RestorePublicationPopup.show(confirmAction, 'tasks');
        } else {
            // Original restore logic for non-task cases
            const confirmAction = (reason) => {
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
                            "isRemoved": false,
                            ...(reason && {restoreReason: reason})
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
                    Toaster.showServerError(error)
                }

                GlobalPageMethods.setFullScreenLoading(true)
                Api.patch(`repoItems/${props.repoItem.id}`, onValidate, onSuccess, onFailure, onFailure, config, patchData);
            };
            RestorePublicationPopup.show(confirmAction, null);
        }
    }

    function saveForm(formData) {
        const currentRepoItem = props.repoItem;
        GlobalPageMethods.setFullScreenLoading(true)
        patchRepoItem(currentRepoItem, formData, props.repoItem.status);

        /** Used to reset the dirtfields **/
        reset(getValues(), {keepDirty: false, keepValues: true});
    }

    function patchFormToDraft() {
        GlobalPageMethods.setFullScreenLoading(true)
        patchRepoItem(props.repoItem, null, "Draft", () => {
        }, true);
    }

    function patchFormToRevising() {
        GlobalPageMethods.setFullScreenLoading(true)
        patchRepoItem(props.repoItem, null, "Revising", () => {
        }, true);
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
        for (let i = 0; i < (count); i++) {
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
                    try {
                        fieldValuesAsArray = JSON.parse(fieldValuesAsString).map(ri => ri.summary.repoItem ? ri.summary.repoItem.id : ri.id)
                    } catch (e) {
                        console.error('Error parsing field values:', e);
                        fieldValuesAsArray = [];
                    }
                }

                if (fieldType === 'attachment') {
                    createExtraRepoItems(repoTypeMap[fieldType], props.repoItem.relatedTo.id, action.value.length).then(axios.spread((...responses) => {
                        const repoItemList = responses.map((response) => {
                            return Api.dataFormatter.deserialize(response.data);
                        })

                        showRepoItemPopup(
                            formReducerStateRef.current,
                            field,
                            props.repoItem.id,
                            action.value,
                            (newValues) => {
                                props.dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: newValues});
                            },
                            props.repoItem,
                            fieldValuesAsArray,
                            null,
                            repoItemList
                        );
                    })).catch(error => {
                        SwalRepoItemPopup.close()
                        Toaster.showServerError(error)
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
                            createRelatedRepoItem(repoTypeMap[fieldType], props.repoItem.relatedTo.id, navigate, onSuccess, onFailure)
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

    function patchRepoItem(currentRepoItem, formData, status, onSuccessCallback = () => {
    }, disableToaster = false) {
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

        const errorCallback = (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        };

        function onServerFailure(error) {
            errorCallback(error);
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            errorCallback(error);
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
            navigate(0)
        }

        const errorCallback = (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            if (error?.response?.status === 404) {
                Toaster.showToaster({type: "info", message: t("dashboard.tasks.not_found")})
            } else {
                Toaster.showServerError(error)
            }
        };

        function onServerFailure(error) {
            errorCallback(error);
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            errorCallback(error);
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

    function updateRepoItemWithNewInstitute(currentRepoItem, selectedInstitute){
        GlobalPageMethods.setFullScreenLoading(true)

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            navigate(0)
        }

        const errorCallback = (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            if (error?.response?.status === 404) {
                Toaster.showToaster({type: "info", message: t("dashboard.tasks.not_found")})
            } else {
                Toaster.showServerError(error)
            }
        };

        function onServerFailure(error) {
            errorCallback(error);
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            errorCallback(error);
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const patchData = {
            "data": {
                "type": "institute",
                "id": selectedInstitute
            }
        };

        const url = "repoItems/" + currentRepoItem.id + "/relationships/relatedTo"
        Api.patch(url, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData)
    }
}

const Spacer = styled.div`
    width: 242px;

    @media only screen and (max-width: ${mobileTabletMaxWidth}px) {
        width: 0;
    }
`;

const FormContainer = styled.div`
    margin-top: ${props => `${props.marginTop}px`};
    padding-left: 80px;
    padding-right: 80px;
`;

export default DefaultPublicationView;
