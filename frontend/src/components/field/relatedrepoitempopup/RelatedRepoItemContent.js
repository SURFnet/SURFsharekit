import React, {useEffect, useReducer, useRef, useState} from "react"
import "./repoitempopupcontent.scss"
import RepoItemApiRequests from "../../../util/api/RepoItemApiRequests";
import LoadingIndicator from "../../loadingindicator/LoadingIndicator";
import {faTimes, faTimesCircle} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import FormFieldHelper from "../../../util/FormFieldHelper";
import RepoItemHelper from "../../../util/RepoItemHelper";
import styled from "styled-components";
import {useTranslation} from "react-i18next";
import {IndependentForm} from "../FormField";
import FormFieldAnswerValues from "../../../util/formfield/FormFieldAnswerValues";
import ButtonText from "../../buttons/buttontext/ButtonText";
import FormFieldFiles from "../../../util/formfield/FormFieldFiles";
import Toaster from "../../../util/toaster/Toaster";
import Api from "../../../util/api/Api";
import SearchAndSelectPersonTable, {
    Tag,
    TagButton,
    TagContainer,
    TagName
} from "../../searchandselectpersontable/SearchAndSelectPersonTable";
import HorizontalTabList from "../../horizontaltablist/HorizontalTabList";
import CreatePersonForm from "../../createpersonform/CreatePersonForm";
import SearchRepoItemTable from "../../searchrepoitemtable/SearchRepoItemTable";
import {useHistory} from "react-router-dom";
import {calculateChunkSize, finishUpload, uploadParts} from "./AWSUploader";
import ProgressBar from "../../progressbar/ProgressBar";
import {
    cultured,
    flameLight,
    greyLight,
    openSans,
    openSansBold,
    spaceCadetLight,
    SURFShapeLeft,
    textColor
} from "../../../Mixins";
import axios from "axios";
import SURFButton from "../../../styled-components/buttons/SURFButton";

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

function RelatedRepoItemContent(props) {
    const [formReducerState, dispatch] = useReducer(reducer, initialState);
    const formReducerStateRef = useRef();
    const {t} = useTranslation();
    formReducerStateRef.current = formReducerState;

    const [repoItems, setRepoItems] = useState(props.repoItems ?? []);
    const [file, setFile] = useState(props.file ?? []);
    const [serverFile, setServerFile] = useState(null);
    const [selectedRepoItems, setSelectedRepoItems] = useState([]);
    const [currentStepIndex, setCurrentStepIndex] = useState(0);
    const [isLoading, setIsLoading] = useState(false);
    const [progress, setProgress] = useState(null);
    const repoItemApiRequests = new RepoItemApiRequests();
    const formFieldHelper = new FormFieldHelper();
    const history = useHistory()
    const onSubmit = submitFormData;
    const formSubmitButton = useRef([]);
    let answerValues = null;

    const [submitValues, setSubmitValues] = useState([])
    const [isSettingSubmitValues, setIsSettingSubmitValues] = useState(null)

    const [successfulFileUploads, setSuccessfulFileUploads] = useState([])
    const [failedFileUploads, setFailedFileUploads] = useState([])
    const [fileUploadCompleted, setFileUploadCompleted] = useState(false)

    const repoItemsIsNotNullOrEmpty = repoItems && repoItems.length > 0
    const selectedRepoItemsNotNullOrEmpty = selectedRepoItems && selectedRepoItems.length > 0

    const cancelToken = useRef(props.cancelToken)

    useEffect(() => {
        // make a list of formData to send in case of adding multiple repoItems
        if(isSettingSubmitValues) {
            if(submitValues.length < repoItems.length) {
                formSubmitButton.current[submitValues.length].click()
            } else {
                if (submitValues[0]["repoItem"]["repoType"] === "RepoItemRepoItemFile") {
                    // make async request when all formData is collected
                    submitValuesAndUploadFiles(submitValues)
                } else {
                    batchPatchRepoItems()
                }
            }
        }
    }, [submitValues])

    useEffect(() => {
        if (fileUploadCompleted && failedFileUploads.length === 0) {
            const repoItemsToSave = [];
            const idsToSave = successfulFileUploads.map((upload) => {
                return upload.repoItemId
            })
            repoItems.forEach((repoItem) => {
                if (idsToSave.includes(repoItem.id)) {
                    repoItemsToSave.push(repoItem)
                }
            })
            props.onSuccessfulSave(repoItemsToSave)
        }
    }, [fileUploadCompleted])

    useEffect(() => {
        if (props.repoItemId === null && props.getRepoItemFunction !== null) {
            props.getRepoItemFunction((repoItemData) => {
                setupStateFromRepoItemData({data: repoItemData})
            }, (error) => {
                console.log("getRepoItemFunction failed", error)
            })
        } else if (repoItems && repoItems.length > 0) {
            repoItems.forEach((repoItem) => {
                setupStateFromRepoItemData({data: repoItem}, false)
            })
        } else {
            getRepoItem(props.repoItemId)
        }
    }, [])

    let popupContent;
    if (progress !== null) {
        popupContent = <PopupProgressBarHolder>
            <ProgressTitle>{t('attachment_popup.uploading')}</ProgressTitle>
            <ProgressBar height={'15px'} progress={progress}/>
            {failedFileUploads && failedFileUploads.length > 0 && (
                <>
                    <FailedUploadList>
                        <FailedUploadListTitle>{t("attachment_popup.failed_uploads")}</FailedUploadListTitle>
                        {failedFileUploads.map((failedUpload) => {
                            let errorMessage = "";
                            switch (failedUpload.errorCode) {
                                case "UFAC_2":
                                    errorMessage = t("error_message.UFAC_2")
                                    break;
                                default:
                                    errorMessage = t("error_message.failed_upload")
                                    break;
                            }
                            return (
                                <FailedUpload>{failedUpload.fileName} - {errorMessage}</FailedUpload>
                            );
                        })}

                    </FailedUploadList>
                </>
            )}
            {fileUploadCompleted && (
                <UploadButtonContainer>
                    <SURFButton
                        highlightColor={spaceCadetLight}
                        width={"130px"}
                        text={t("action.continue")}
                        onClick={() => {
                            if (successfulFileUploads.length > 0) {
                                const repoItemsToSave = [];
                                const idsToSave = successfulFileUploads.map((upload) => {
                                    return upload.repoItemId
                                })
                                repoItems.forEach((repoItem) => {
                                    if (idsToSave.includes(repoItem.id)) {
                                        repoItemsToSave.push(repoItem)
                                    }
                                })
                                props.onSuccessfulSave(repoItemsToSave)
                            } else {
                                props.onCancel()
                            }
                        }}
                    />
                </UploadButtonContainer>
            )}
        </PopupProgressBarHolder>
    } else if (!repoItemsIsNotNullOrEmpty || isLoading) {
        popupContent = <LoadingIndicator/>
    } else {
        let stepList = undefined;
        if (RepoItemHelper.repoItemIsPersonInvolved(repoItems[0])) {
            stepList = (
                <div className='flex-row form-step-list'>
                    <FormStep
                        active={currentStepIndex === 0}
                        number={1}
                        title={t('repoitem.popup.selectperson')}/>
                    <div className='form-step-divider'/>
                    <FormStep
                        active={currentStepIndex === 1}
                        number={2}
                        title={t('repoitem.popup.details')}/>
                </div>
            )
        } else if (RepoItemHelper.repoItemIsRepoItemLearningObject(repoItems[0])) {
            stepList = (
                <div className='flex-row form-step-list'>
                    <FormStep
                        active={currentStepIndex === 0}
                        number={1}
                        title={t('repoitem.popup.selectlearningobject')}/>
                    <div className='form-step-divider'/>
                    <FormStep
                        active={currentStepIndex === 1}
                        number={2}
                        title={t('repoitem.popup.details')}/>
                </div>
            )
        } else if (RepoItemHelper.repoItemIsRepoItemResearchObject(repoItems[0])) {
            stepList = (
                <div className='flex-row form-step-list'>
                    <FormStep
                        active={currentStepIndex === 0}
                        number={1}
                        title={t('repoitem.popup.selectresearchobject')}/>
                    <div className='form-step-divider'/>
                    <FormStep
                        active={currentStepIndex === 1}
                        number={2}
                        title={t('repoitem.popup.details')}/>
                </div>
            )
        }

        if (RepoItemHelper.repoItemIsPersonInvolved(repoItems[0]) && ((!repoItemsIsNotNullOrEmpty || !selectedRepoItemsNotNullOrEmpty) || currentStepIndex === 0)) {
            popupContent = (
                <SelectPersonStep
                    stepList={stepList}
                    repoType={repoItems[0].repoType}
                    repoItemId={repoItems[0].relatedTo.id}
                    selectedPersons={selectedRepoItems}
                    onPersonSelect={(person) => onRepoItemSelected(person)}
                    setAdditionalRepoItems={(additionalRepoItemList) => {
                        setRepoItems([...repoItems, ...additionalRepoItemList])
                        setCurrentStepIndex(1)
                    }}
                    selectNextStep={() => setCurrentStepIndex(1)}/>
            )
        } else if (RepoItemHelper.repoItemIsRepoItemResearchObject(repoItems[0]) && ((!repoItemsIsNotNullOrEmpty && !selectedRepoItemsNotNullOrEmpty) || currentStepIndex === 0)) {
            popupContent = (
                <SelectResearchObjectStep
                    repoItemIdFilter={props.baseRepoItem.id}
                    repoType={repoItems[0].repoType}
                    repoItemId={repoItems[0].relatedTo.id}
                    stepList={stepList}
                    repoItemIdsToExcludeInSearch={props.repoItemIdsToExcludeInSearch}
                    onRepoItemSelect={(repoItemSelected) => {
                        setSelectedRepoItems([...selectedRepoItems, repoItemSelected])
                        setCurrentStepIndex(1)
                    }}
                    setAdditionalRepoItems={(additionalRepoItemList) => {
                        setRepoItems([...repoItems, ...additionalRepoItemList])
                        setCurrentStepIndex(1)
                    }}
                    selectNextStep={() => setCurrentStepIndex(1)}
                />
            )
        } else if (RepoItemHelper.repoItemIsRepoItemLearningObject(repoItems[0]) && ((!repoItemsIsNotNullOrEmpty && !selectedRepoItemsNotNullOrEmpty) || currentStepIndex === 0)) {
            popupContent = (
                <SelectLearningObjectStep
                    repoItemIdFilter={props.baseRepoItem.id}
                    repoType={repoItems[0].repoType}
                    repoItemId={repoItems[0].relatedTo.id}
                    stepList={stepList}
                    repoItemIdsToExcludeInSearch={props.repoItemIdsToExcludeInSearch}
                    selectedRepoItems={selectedRepoItems}
                    onRepoItemSelect={(learningObject) => onRepoItemSelected(learningObject)}
                    setAdditionalRepoItems={(additionalRepoItemList) => {
                        setRepoItems([...repoItems, ...additionalRepoItemList])
                        setCurrentStepIndex(1)
                    }}
                    selectNextStep={() => setCurrentStepIndex(1)}
                />
            )
        } else {
            let title = repoItems[0].title
            if (RepoItemHelper.repoItemIsPersonInvolved(repoItems[0])) {
                title = t('repoitem.personinvolved_field.add')
            } else if (RepoItemHelper.repoItemIsRepoItemLearningObject(repoItems[0])) {
                title = t('repoitem.learningobject_field.add')
            } else if (RepoItemHelper.repoItemIsRepoItemResearchObject(repoItems[0])) {
                title = t('repoitem.researchobject_field.add')
            }

            answerValues = FormFieldAnswerValues.getAnswersDictionary(repoItems[0].answers)

            let canSave = true;
            if (RepoItemHelper.repoItemIsAttachment(repoItems[0])) {
                if (!(file || serverFile)) {
                    canSave = false;
                }
            }

            popupContent = [
                <h3 key={"repoitem-form-popup-title"}
                    className='repoitem-form-popup-title'>{title}</h3>,
                stepList,
                repoItemsIsNotNullOrEmpty && !RepoItemHelper.repoItemIsAttachment(repoItems[0]) && !RepoItemHelper.repoItemIsRepoItemLinkObject(repoItems[0]) ?
                    selectedRepoItems.map((selectedRepoItem, index) => {
                        return <div key={selectedRepoItem.id}>
                            <RepoItemPopupItemWrapper>
                                <IndependentForm key={"repoitem-popup-form-id-" + selectedRepoItem.id}
                                                 formId={"repoitem-popup-form-id-" + selectedRepoItem.id}
                                                 repoItem={repoItems[index]}
                                                 showSectionHeaders={false}
                                                 onValueChanged={formFieldValueChanged}
                                                 onSubmit={(formData) => {
                                                     let formDataHolder = {}
                                                     formDataHolder["repoItem"] = repoItems[index]
                                                     formDataHolder["formData"] = formData
                                                     setIsSettingSubmitValues(true)
                                                     setSubmitValues([...submitValues, formDataHolder])
                                                 }}
                                                 onSubmitError={() => {
                                                     setIsSettingSubmitValues(false)
                                                     setSubmitValues([])
                                                 }}
                                                 file={[]}
                                                 person={selectedRepoItem}
                                                 relatedRepoItem={selectedRepoItem}
                                                 formReducerState={formReducerState}
                                />

                                <button type="submit"
                                        form={"surf-form-repoitem-popup-form-id-" + selectedRepoItem.id}
                                        ref={button => {
                                            formSubmitButton.current[index] = button
                                        }}
                                        style={{display: "none"}}/>
                            </RepoItemPopupItemWrapper>
                        </div>
                    })

                    // For files
                : repoItemsIsNotNullOrEmpty && RepoItemHelper.repoItemIsAttachment(repoItems[0]) ?
                        repoItems.map((repoItem, index) => (
                            <RepoItemAttachmentWrapper key={repoItem.id}>
                                <RepoItemAttachmentTitle key={`repoitem-popup-form-title-${index}`}>{ t('attachment_popup.file') } { (index + 1) }</RepoItemAttachmentTitle>
                                <IndependentForm
                                    key={"repoitem-popup-form-id-" + repoItem.id}
                                    formId={"repoitem-popup-form-id-" + repoItem.id}
                                    repoItem={repoItems[index]}
                                    showSectionHeaders={false}
                                    onValueChanged={formFieldValueChanged}
                                    onSubmit={(formData) => {
                                        let formDataHolder = {};
                                        formDataHolder["repoItem"] = repoItems[index];
                                        formDataHolder["formData"] = formData;
                                        setIsSettingSubmitValues(true);
                                        setSubmitValues([...submitValues, formDataHolder]);
                                    }}
                                    onSubmitError={() => {
                                        setIsSettingSubmitValues(false);
                                        setSubmitValues([]);
                                    }}
                                    file={file !== null && file.length > 0 ? file[index] : (serverFile ? serverFile : null)}
                                    person={null}
                                    relatedRepoItem={repoItem}
                                    formReducerState={formReducerState}
                                    index={index}
                                    repoItemCount={repoItems.length}
                                />

                                <button
                                    type="submit"
                                    form={`surf-form-repoitem-popup-form-id-${repoItem.id}`}
                                    ref={(button) => {
                                        formSubmitButton.current[index] = button;
                                    }}
                                    style={{ display: "none" }}
                                />
                            </RepoItemAttachmentWrapper>
                        ))
        :
                    <RepoItemLinkWrapper>
                        <RepoItemLinkTitle>{t("link_popup.title")}</RepoItemLinkTitle>
                        <IndependentForm key={"repoitem-popup-form-id-" + repoItems[0].id}
                                         formId={"repoitem-popup-form-id-" + repoItems[0].id}
                                         repoItem={repoItems[0]}
                                         showSectionHeaders={false}
                                         onValueChanged={formFieldValueChanged}
                                         onSubmit={(formData) => {
                                             onSubmit(formData, repoItems[0])
                                         }}
                                         file={file && file.length > 0 ? file[0] : []}
                                         formReducerState={formReducerState}/>
                    </RepoItemLinkWrapper>
                ,
                <div key={"repo-item-popup-save-button-wrapper"}
                     className={"save-button-wrapper"}>
                    {repoItemsIsNotNullOrEmpty && (RepoItemHelper.repoItemIsAttachment(repoItems[0]) || RepoItemHelper.repoItemIsRepoItemLinkObject(repoItems[0]))  && (
                        <button type="submit"
                                form={"surf-form-repoitem-popup-form-id-" + repoItems[0].id}
                                ref={button => {formSubmitButton.current[0] = button}}
                                style={{display: "none"}}/>
                    )}
                    <ButtonText text={t('repoitem.popup.save')}
                                buttonType={"callToAction"}
                                disabled={!canSave}
                                onClick={() => {
                                    if(submitValues.length === 0) {
                                        formSubmitButton.current[0].click()
                                    }
                                }}/>
                </div>
            ]
        }
    }

    return (
        <div className={"repoitem-popup-content-wrapper"}>
            <div className={"repoitem-popup-content"}>
                <div className={"close-button-container"}
                     onClick={props.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                {currentStepIndex === 1 && repoItems.length !== selectedRepoItems.length ? <LoadingIndicator/> : popupContent}
            </div>
        </div>
    )

    function formFieldValueChanged(field, changedValue) {
        const fieldType = formFieldHelper.getFieldType(field.fieldType)

        if (fieldType === 'file') {
            setFile(changedValue)
            setServerFile(null) //Real file changed, so continue using file
        }
    }

    function setupStateFromRepoItemData(response, addToState = true) {
        const receivedRepoItem = response.data;

        if (addToState === true) {
            setRepoItems([...repoItems, receivedRepoItem]);
        }

        let formFieldValueState = {};

        const receivedRepoItemFields = RepoItemHelper.getAllFields(receivedRepoItem)
        receivedRepoItemFields.forEach((field) => {
            let fieldAnswer = formFieldHelper.getFieldAnswer(receivedRepoItem, field)
            formFieldValueState = {
                ...formFieldValueState,
                [field.key]: fieldAnswer
            }
        });

        dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: formFieldValueState});

        if (RepoItemHelper.repoItemIsAttachment(receivedRepoItem)) {
            const fileField = receivedRepoItemFields.find((item) => {
                return item.fieldType === "File"
            })
            const fileFieldAnswerArray = receivedRepoItem.answers.find((findItem) => {
                return findItem.fieldKey === fileField.key
            })
            if (fileFieldAnswerArray && fileFieldAnswerArray.values && fileFieldAnswerArray.values.length > 0 && fileFieldAnswerArray.values[0]) {
                const tempServerFile = fileFieldAnswerArray.values[0]
                if (tempServerFile.summary.title) {
                    setServerFile(tempServerFile)
                }
            }
        } else if (RepoItemHelper.repoItemIsPersonInvolved(receivedRepoItem)) {
            const personField = receivedRepoItemFields.find((item) => {
                return item.fieldType === "Person"
            })
            const personSelectedArray = receivedRepoItem.answers.find((findItem) => {
                return findItem.fieldKey === personField.key
            })
            if (personSelectedArray && personSelectedArray.values && personSelectedArray.values.length > 0 && personSelectedArray.values[0]) {
                const person = personSelectedArray.values[0]
                if (person.summary.name) {
                    setSelectedRepoItems([...selectedRepoItems, person.summary])
                    setCurrentStepIndex(1)
                }
            }
        } else if (RepoItemHelper.repoItemIsRepoItemLearningObject(receivedRepoItem) || RepoItemHelper.repoItemIsRepoItemResearchObject(receivedRepoItem)) {
            const repoItemField = receivedRepoItemFields.find((item) => {
                return item.fieldType === "RepoItem"
            })
            const repoItemSelectedArray = receivedRepoItem.answers.find((findItem) => {
                return findItem.fieldKey === repoItemField.key
            })
            if (repoItemSelectedArray && repoItemSelectedArray.values && repoItemSelectedArray.values.length > 0 && repoItemSelectedArray.values[0]) {
                const tempRepoItem = repoItemSelectedArray.values[0]
                if (tempRepoItem.summary.title) {
                    setSelectedRepoItems([...selectedRepoItems, tempRepoItem.summary])
                    setCurrentStepIndex(1)
                }
            }
        }
    }

    function onRepoItemSelected(repoItemSelected) {
        let repoItemIsSelected = selectedRepoItems.find(selectedRepoItem => selectedRepoItem.id === repoItemSelected.id)
        if (repoItemIsSelected) {
            deselectRepoItem(repoItemSelected)
        } else {
            selectRepoItem(repoItemSelected)
        }
    }

    function selectRepoItem(repoItem) {
        let newSelectedRepoItems = [...selectedRepoItems, repoItem]
        setSelectedRepoItems(newSelectedRepoItems)
    }

    function deselectRepoItem(repoItem) {
        let newSelectedRepoItems = selectedRepoItems.filter((selectedRepoItem) => {
            return selectedRepoItem.id !== repoItem.id
        })
        setSelectedRepoItems(newSelectedRepoItems)
    }

    function getRepoItem(repoItemId) {

        function onValidate(response) {
        }

        function onServerFailure(error) {
            Toaster.showDefaultRequestError();
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError();
        }

        repoItemApiRequests.getRepoItem(repoItemId, onValidate, setupStateFromRepoItemData, onLocalFailure, onServerFailure)
    }

    function submitFormData(formData, repoItem) {
        const status = "Draft";

        const fileToUpload = FormFieldFiles.getFormFilesToUpload(repoItem, formData)[0];
        formData = FormFieldFiles.setFormFileValues(repoItem, formData, answerValues);

        if (RepoItemHelper.repoItemIsAttachment(repoItem)) {
            if (!fileToUpload && serverFile) { //Existing file on server
                const fileField = RepoItemHelper.getAllFields(repoItem).find((item) => {
                    return item.fieldType === "File"
                })
                formData[fileField.key] = serverFile.repoItemFileID
            }
        }

        if (fileToUpload) {
            uploadFile(fileToUpload, (repoItemFileId) => {
                formData[fileToUpload.fieldKey] = repoItemFileId;
                patchRepoItemAfterUploadingFiles(repoItem, formData, status)
            })
        } else {
            patchRepoItemAfterUploadingFiles(repoItem, formData, status)
        }
    }

    async function submitValuesAndUploadFiles(submitValues) {
        const successfulUploads = [];
        const failedUploads = [];

        let totalChunks = 0;
        let totalCompletedChunks = 0;

        const uploadRequests = [];

        setProgress(0)
        for (const [index, submitValue] of submitValues.entries()) {
            const repoItem = submitValue["repoItem"];
            let formData = submitValue["formData"];

            const fileToUpload = FormFieldFiles.getFormFilesToUpload(repoItem, formData)[0];
            formData = FormFieldFiles.setFormFileValues(repoItem, formData, answerValues);

            if (RepoItemHelper.repoItemIsAttachment(repoItem)) {
                if (!fileToUpload && serverFile) {
                    const fileField = RepoItemHelper.getAllFields(repoItem).find((item) => {
                        return item.fieldType === "File";
                    });
                    formData[fileField.key] = serverFile.repoItemFileID;
                }
            }

            if (fileToUpload) {
                let fileCompletedChunks = 0
                const chunkSize = calculateChunkSize(fileToUpload.size)
                let chunksToAdd = Math.floor(fileToUpload.size / chunkSize)
                if (chunksToAdd === 0) {
                    chunksToAdd = 1;
                }
                totalChunks += chunksToAdd

                const promise = new Promise((resolve, reject) => {
                    uploadFile(fileToUpload, (repoItemFileId) => {
                        formData[fileToUpload.fieldKey] = repoItemFileId;
                        successfulUploads.push({repoItemId: repoItem.id, fileName: fileToUpload.name})
                        resolve();
                    }, (reason, errorCode) => {
                        failedUploads.push({ fileName: fileToUpload.name, reason: reason, errorCode: errorCode })
                        reject(reason)
                    }, () => {
                        totalCompletedChunks += 1
                        fileCompletedChunks += 1
                        setProgress(Math.min(100, Math.floor(100 * (totalCompletedChunks / totalChunks))))
                    }, cancelToken);
                }).then(() => {
                    console.log("Successfully uploaded " + fileToUpload.name)
                }).catch((error) => {
                    totalCompletedChunks += (chunksToAdd - fileCompletedChunks)
                    setProgress(Math.min(100, Math.floor(100 * (totalCompletedChunks / totalChunks))))
                });

                uploadRequests.push(promise)
            } else {
                // No file to upload, just patch the RepoItemRepoItemFile
                patchRepoItemAfterUploadingFiles(repoItem, formData)
            }
        }

        await Promise.allSettled(uploadRequests)
            .then( () => {
                setFailedFileUploads(failedUploads)
                setSuccessfulFileUploads(successfulUploads)
            }).catch( () => {
                console.log ("SOMETHING WENT HORRIBLY WRONG")
            });

        if (successfulUploads.length > 0) {
            // Only patch the RepoItems that were successfully uploaded
            batchPatchRepoItems(true, successfulUploads);
        } else {
            // Not a single file was uploaded successfully. Just set the state to show the continue button
            setFileUploadCompleted(true)
        }
    }

    /**
     * @param fileToUpload
     * @param successCallback
     * @param onFailCallback
     * @param chunkCompletedCallback
     * @param cancelToken
     * @returns {Promise<void>}
     */
    async function uploadFile(fileToUpload, successCallback, onFailCallback, chunkCompletedCallback, cancelToken) {
        if (fileToUpload) {

            function onPartCompleted() {
                chunkCompletedCallback(); // Notify the total progress
            }

            function onValidate(response) {
            }

            function onLocalFailure(error) {
                setProgress(null);
                console.log(error);
                Toaster.showDefaultRequestError();
            }

            function onServerFailure(error) {
                setProgress(null);
                Toaster.showServerError(error);
                if (error.response.status === 401) {
                    // history.push('/login?redirect=' + window.location.pathname);
                }
            }


            const chunkSize = calculateChunkSize(fileToUpload.size)
            const postData = {
                "partCount": Math.ceil(fileToUpload.size / chunkSize),
                "fileName": fileToUpload.name,
                "fileSize": fileToUpload.size
            };

            // Tell the SilverStripe back-end that we want to upload a file to S3
            Api.post('upload/startUpload', onValidate, await uploadToAWS, onLocalFailure, onServerFailure, {}, postData);

            // This function is meant to be passed as a success callback to /startUpload
            // When /startUpload returns a successful response we create a request for each file chunk (PUT directly to S3)
            async function uploadToAWS(response) {
                await uploadParts(fileToUpload, chunkSize, response.data.parts, onPartCompleted, cancelToken).then(etagsPerParts => {
                    const postData = {
                        "parts": etagsPerParts,
                        "uploadId": response.data.uploadId,
                        "fileName": response.data.fileName,
                        "repoItemUuid": props.repoItemId,
                    }

                    // finishUpload() calls /closeUpload in SilverStripe. A RepoItemFile is then created, because at this point we know the
                    // upload to S3 has been successful. The uuid of this RepoItemFile is then returned so we can then patch the RepoItemRepoItemFile
                    // With the provided RepoItemFile uuid as answer for the FileField.
                    finishUpload(postData, (response) => {
                        //  when uploadParts resolves,
                        successCallback(response.data.id);
                    }, onLocalFailure);
                }).catch(error => {
                    console.log(error)
                    let errorCode = "Unknown"
                    try {
                        errorCode = response.data.errors[0].code
                    } catch (error) {}
                    onFailCallback(error.message, errorCode)
                });
            }
        }
    }


    function patchRepoItemAfterUploadingFiles(currentRepoItem, formData, status) {
        if (!isLoading) {
            setIsLoading(true)
        }
        const answers = formFieldHelper.getAllFormAnswersForRepoItem(currentRepoItem, formData)

        function onValidate(response) {
            setIsLoading(false)
        }

        function onSuccess(response) {
            props.onSuccessfulSave([Api.dataFormatter.deserialize(response.data)])
            setIsLoading(false)
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
            setIsLoading(false)
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError();
            setIsLoading(false)
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const patchData = {
            "data": {
                "type": "repoItem",
                "id": currentRepoItem.id,
                "attributes": {
                    "status": status ? status : currentRepoItem.status,
                    "repoType": currentRepoItem.repoType,
                    "answers": answers
                }
            }
        };

        const url = "repoItems/" + currentRepoItem.id
        Api.patch(url, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData)
    }

    function batchPatchRepoItems(isFileUpload = false, successfulUploads) {
        setIsLoading(true)
        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const requestList = []
        for(let i = 0; i < (submitValues.length); i++) {
            const currentRepoItem = submitValues[i]["repoItem"]
            const answers = formFieldHelper.getAllFormAnswersForRepoItem(currentRepoItem, submitValues[i]["formData"])
            const patchData = {
                "data": {
                    "type": "repoItem",
                    "id": currentRepoItem.id,
                    "attributes": {
                        "status": "Draft",
                        "repoType": currentRepoItem.repoType,
                        "answers": answers
                    }
                }
            };

            if (isFileUpload) {
                successfulUploads.forEach((upload) => {
                    if (submitValues[i]["repoItem"].id === upload.repoItemId) {
                        requestList.push(axios.patch("repoItems/" + currentRepoItem.id, patchData, Api.getRequestConfig(config)))
                    }
                })
            } else {
                requestList.push(axios.patch("repoItems/" + currentRepoItem.id, patchData, Api.getRequestConfig(config)))
            }
        }

        Promise.all(requestList).then(axios.spread((...responses) => {
            const list = responses.map((response) => {
                return Api.dataFormatter.deserialize(response.data);
            })
            if (isFileUpload) {
                setRepoItems(list)
                setFileUploadCompleted(true)
            } else {
                props.onSuccessfulSave(list)
            }
            setIsLoading(false)
            setIsSettingSubmitValues(false)
            setSubmitValues([])
        })).catch(errors => {
            if (isFileUpload) {
                setFileUploadCompleted(true)
            }
            setIsLoading(false)
            setIsSettingSubmitValues(false)
            setSubmitValues([])
        })
    }
}

export function FormStep(props) {
    const {t} = useTranslation();

    return  <div className={'form-step'} onClick={props.onClick}>
                <div className={'flex-row'}>
                    <div className={'form-step-circle flex-center ' + (props.active && 'active') + (props.completed ? ' completed' : '')}>{props.number}</div>
                    <div className={'flex-column'}>
                        <div className={'form-step-number ' + (props.stepDisabled && 'form-step-disabled')}>
                            {t('popup.step') + ' ' + props.number}
                        </div>
                    </div>
                </div>
            </div>
}

function SelectPersonStep(props) {
    const [currentMode, setCurrentMode] = useState(0)
    const {t} = useTranslation()

    return (
        <div>
            <h3 className='repoitem-form-popup-title'>
                {t('repoitem.personinvolved_field.add')}
            </h3>
            {props.stepList}
            <HorizontalTabList tabsTitles={[t('repoitem.popup.search'), t('repoitem.popup.manual')]}
                               selectedIndex={currentMode}
                               onTabClick={setCurrentMode}/>
            <TagContainer>
                { props.selectedPersons && props.selectedPersons.length > 0 && props.selectedPersons.map((person) => {
                    return  <Tag key={person.id}>
                        <TagName>
                            {person.name}
                        </TagName>
                        <TagButton
                            icon={faTimesCircle}
                            onClick={() => props.onPersonSelect(person)}
                        />
                    </Tag>
                })}
            </TagContainer>
            <div className='repoitem-form-person-select'>
                {currentMode === 0 && <SearchAndSelectPersonTable
                    repoType={props.repoType}
                    onPersonSelect={(person) => props.onPersonSelect(person)}
                    selectedPersons={props.selectedPersons}
                    personClicked={id => window.open(`../../profile/${id}`)}
                    selectNextStep={props.selectNextStep}
                    multiSelect={true}
                    repoItemId={props.repoItemId}
                    setAdditionalRepoItems={(additionalRepoItemList) => props.setAdditionalRepoItems(additionalRepoItemList)}
                />}
                {currentMode === 1 && <CreatePersonForm
                    onPersonSelect={props.onPersonSelect}
                    selectPreviousMode={() => setCurrentMode(0)}
                />}
            </div>
        </div>
    )
}


function SelectLearningObjectStep(props) {
    const {t} = useTranslation()

    return (
        <div>
            <h3 className='repoitem-form-popup-title'>
                {t('repoitem.learningobject_field.add')}
            </h3>
            {props.stepList}
            <div className='repoitem-form-person-select'>
                <TagContainer>
                    {props.selectedRepoItems && props.selectedRepoItems.length > 0 && props.selectedRepoItems.map((repoItem) => {
                        return  <Tag key={repoItem.id}>
                            <TagName>
                                {repoItem.title}
                            </TagName>
                            <TagButton
                                icon={faTimesCircle}
                                onClick={() => props.onRepoItemSelect(repoItem)}
                            />
                        </Tag>
                    })}
                </TagContainer>
                <SearchRepoItemTable
                    repoType={props.repoType}
                    repoItemId={props.repoItemId}
                    onRepoItemSelect={(repoItem) => props.onRepoItemSelect(repoItem)}
                    selectNextStep={props.selectNextStep}
                    selectedRepoItems={props.selectedRepoItems}
                    setAdditionalRepoItems={(additionalRepoItemList) => props.setAdditionalRepoItems(additionalRepoItemList)}
                    multiSelect={true}
                    filters={
                        {
                            'filter[isRemoved]': false,
                            'filter[repoType]': 'LearningObject',
                            'filter[id][NEQ]': props.repoItemIdFilter + ',' + props.repoItemIdsToExcludeInSearch.join(',')
                        }
                    }
                />
            </div>
        </div>
    )
}

function SelectResearchObjectStep(props) {
    const {t} = useTranslation()

    return (
        <div>
            <h3 className='repoitem-form-popup-title'>
                {t('repoitem.researchobject_field.add')}
            </h3>
            {props.stepList}
            <div className='repoitem-form-person-select'>
                <SearchRepoItemTable
                    repoType={props.repoType}
                    repoItemId={props.repoItemId}
                    onRepoItemSelect={(repoItem) => props.onRepoItemSelect(repoItem)}
                    selectNextStep={props.selectNextStep}
                    selectedRepoItems={props.selectedRepoItems}
                    setAdditionalRepoItems={(additionalRepoItemList) => props.setAdditionalRepoItems(additionalRepoItemList)}
                    setSelectedRepoItem={props.onRepoItemSelect}
                    multiSelect={false}
                    filters={
                        {
                            'filter[isRemoved]': false,
                            'filter[repoType]': 'ResearchObject',
                            'filter[id][NEQ]': props.repoItemIdFilter + ',' + props.repoItemIdsToExcludeInSearch.join(',')
                        }
                    }
                />
            </div>
        </div>
    )
}

export const RepoItemAttachmentTitle = styled.h5`
    font-weight: 700;
    font-size: 16px;
    margin-bottom: 6px;
`

export const RepoItemPopupItemWrapper = styled.div`
    display: flex;
    flex-direction: column;
    form {
        background-color: ${cultured};
        border: 1px solid ${greyLight};
        margin-bottom: 12px;
        ${SURFShapeLeft};
    }
`


export const RepoItemAttachmentWrapper = styled.div`
    display: flex;
    flex-direction: column;
    form {
        background-color: ${cultured};
        border: 1px solid ${greyLight};
        margin-bottom: 12px;
        ${SURFShapeLeft};
    }
`

export const RepoItemLinkTitle = styled.h5`
    font-weight: 700;
    font-size: 16px;
    margin-bottom: 6px;
`

export const RepoItemLinkWrapper = styled.div`
    display: flex;
    flex-direction: column;
    form {
        background-color: ${cultured};
        border: 1px solid ${greyLight};
        margin-bottom: 12px;
        ${SURFShapeLeft};
    }
`

export const ProgressTitle = styled.div`
    ${openSansBold()}
    font-size: 16px;
    line-height: 16px;
    color: #2D364F;
    text-align: center;
    width: 100%;
    margin-bottom: 12px;
`;

export const PopupProgressBarHolder = styled.div`
    max-height: 250px;
    width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    margin-top: 48px;
    margin-bottom: 72px;
`;

const FailedUploadList = styled.div`
  background: ${flameLight};
  margin-top: 48px;
  ${SURFShapeLeft};
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px;
`;

const FailedUploadListTitle = styled.div`
  ${openSansBold()};
  font-size: 16px;
  color: ${textColor};
  margin-bottom: 12px;
`;

const FailedUpload = styled.div`
  ${openSans()};
  font-size: 14px;
  color: ${textColor};
`;

const UploadButtonContainer = styled.div`
      position: absolute;
      bottom: 24px;
      right: 35px;
`;

export default RelatedRepoItemContent
