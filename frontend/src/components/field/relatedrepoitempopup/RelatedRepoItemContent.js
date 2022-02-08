import React, {useEffect, useReducer, useRef, useState} from "react"
import "./repoitempopupcontent.scss"
import RepoItemApiRequests from "../../../util/api/RepoItemApiRequests";
import LoadingIndicator from "../../loadingindicator/LoadingIndicator";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import FormFieldHelper from "../../../util/FormFieldHelper";
import {useForm} from "react-hook-form";
import RepoItemHelper from "../../../util/RepoItemHelper";
import {useTranslation} from "react-i18next";
import {Form} from "../FormField";
import FormFieldAnswerValues from "../../../util/formfield/FormFieldAnswerValues";
import ButtonText from "../../buttons/buttontext/ButtonText";
import FormFieldFiles from "../../../util/formfield/FormFieldFiles";
import Toaster from "../../../util/toaster/Toaster";
import ValidationError from "../../../util/ValidationError";
import Api from "../../../util/api/Api";
import SearchAndSelectPersonTable from "../../searchandselectpersontable/SearchAndSelectPersonTable";
import HorizontalTabList from "../../horizontaltablist/HorizontalTabList";
import CreatePersonForm from "../../createpersonform/CreatePersonForm";
import SearchRepoItemTable from "../../searchrepoitemtable/SearchRepoItemTable";
import {useHistory} from "react-router-dom";
import PersonTableWithSearch from "../../persontablewithsearch/PersonTableWithSearch";

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

    const [repoItem, setRepoItem] = useState(null);
    const [file, setFile] = useState(props.file);
    const [serverFile, setServerFile] = useState(null);
    const [selectedPerson, setSelectedPerson] = useState(null);
    const [serverPerson, setServerPerson] = useState(null);
    const [selectedRepoItem, setSelectedRepoItem] = useState(null);
    const [serverRepoItem, setServerRepoItem] = useState(null);
    const [currentStepIndex, setCurrentStepIndex] = useState(0);
    const [isLoading, setIsLoading] = useState(false);
    const repoItemApiRequests = new RepoItemApiRequests();
    const formFieldHelper = new FormFieldHelper();
    const {register, handleSubmit, errors, setValue, getValues, trigger} = useForm();
    const history = useHistory()
    const onSubmit = submitFormData;
    const formSubmitButton = useRef();
    let answerValues = null;

    useEffect(() => {
        getRepoItem()
    }, [])

    let popupContent;
    if (repoItem === null || isLoading) {
        popupContent = <LoadingIndicator/>
    } else {
        let stepList = undefined;
        if (RepoItemHelper.repoItemIsPersonInvolved(repoItem)) {
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
        } else if (RepoItemHelper.repoItemIsRepoItemLearningObject(repoItem)) {
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
        }

        if (RepoItemHelper.repoItemIsPersonInvolved(repoItem) && ((serverPerson == null && selectedPerson == null) || currentStepIndex === 0)) {
            popupContent = (
                <SelectPersonStep stepList={stepList}
                                  onPersonSelect={(person) => {
                                      setSelectedPerson(person)
                                      setCurrentStepIndex(1)
                                  }}/>
            )
        } else if (RepoItemHelper.repoItemIsRepoItemLearningObject(repoItem) && ((serverRepoItem == null && selectedRepoItem == null) || currentStepIndex === 0)) {
            popupContent = (
                <SelectLearningObjectStep repoItemIdFilter={props.baseRepoItem.id}
                                          stepList={stepList}
                                          repoItemIdsToExcludeInSearch={props.repoItemIdsToExcludeInSearch}
                                          onRepoItemSelect={(repoItemSelected) => {
                                              setSelectedRepoItem(repoItemSelected)
                                              setCurrentStepIndex(1)
                                          }}/>
            )
        } else {
            let title = repoItem.title
            if (RepoItemHelper.repoItemIsPersonInvolved(repoItem)) {
                title = t('repoitem.personinvolvedadd')
            } else if (RepoItemHelper.repoItemIsRepoItemLearningObject(repoItem)) {
                title = t('repoitem.addlearningobject')
            }

            answerValues = FormFieldAnswerValues.getAnswersDictionary(repoItem.answers)

            let canSave = true;
            if (RepoItemHelper.repoItemIsAttachment(repoItem)) {
                if (!(file || serverFile)) {
                    canSave = false;
                }
            }

            popupContent = [
                <h3 key={"repoitem-form-popup-title"}
                    className='repoitem-form-popup-title'>{title}</h3>,
                stepList,
                <Form key={"repoitem-popup-form-id"}
                      formId={"repoitem-popup-form-id"}
                      errors={errors}
                      repoItem={repoItem}
                      showSectionHeaders={false}
                      onValueChanged={formFieldValueChanged}
                      onSubmit={handleSubmit(onSubmit)}
                      register={register}
                      setValue={setValue}
                      file={file}
                      person={selectedPerson}
                      relatedRepoItem={selectedRepoItem}
                      formReducerState={formReducerState}/>,

                <div key={"repo-item-popup-save-button-wrapper"}
                     className={"save-button-wrapper"}>
                    <button type="submit"
                            form="surf-form-repoitem-popup-form-id"
                            ref={formSubmitButton}
                            style={{display: "none"}}/>
                    <ButtonText text={t('repoitem.popup.save')}
                                buttonType={"callToAction"}
                                disabled={!canSave}
                                onClick={() => {
                                    formSubmitButton.current.click();
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
                {popupContent}
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

    function submitFormData(formData) {
        const status = "Draft";
        const currentRepoItem = repoItem

        const fileToUpload = FormFieldFiles.getFormFilesToUpload(currentRepoItem, formData)[0];
        formData = FormFieldFiles.setFormFileValues(currentRepoItem, formData, answerValues);

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
                patchRepoItemAfterUploadingFiles(currentRepoItem, formData, status)
            })
        } else {
            patchRepoItemAfterUploadingFiles(currentRepoItem, formData, status)
        }
    }

    function getRepoItem() {

        function onValidate(response) {
        }

        function onSuccess(response) {

            const receivedRepoItem = response.data;
            setRepoItem(receivedRepoItem);

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
                    const tempPerson = personSelectedArray.values[0]
                    if (tempPerson.summary.name) {
                        setServerPerson(tempPerson)
                        setCurrentStepIndex(1)
                    }
                }
            } else if (RepoItemHelper.repoItemIsRepoItemLearningObject(receivedRepoItem)) {
                const repoItemField = receivedRepoItemFields.find((item) => {
                    return item.fieldType === "RepoItem"
                })
                const repoItemSelectedArray = receivedRepoItem.answers.find((findItem) => {
                    return findItem.fieldKey === repoItemField.key
                })
                if (repoItemSelectedArray && repoItemSelectedArray.values && repoItemSelectedArray.values.length > 0 && repoItemSelectedArray.values[0]) {
                    const tempRepoItem = repoItemSelectedArray.values[0]
                    if (tempRepoItem.summary.title) {
                        setServerRepoItem(tempRepoItem)
                        setCurrentStepIndex(1)
                    }
                }
            }
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

        repoItemApiRequests.getRepoItem(props.repoItemId, onValidate, onSuccess, onLocalFailure, onServerFailure)
    }

    function uploadFile(fileToUpload, successCallback) {
        if (fileToUpload) {
            setIsLoading(true)

            function onValidate(response) {
                if (!(response.data && response.data.data)) {
                    setIsLoading(false)
                    throw new ValidationError("Invalid upload file response data");
                }
            }

            function onSuccess(response) {
                const repoItemId = response.data.data.id
                successCallback(repoItemId)
            }

            function onServerFailure(error) {
                setIsLoading(false)
                Toaster.showServerError(error)
                if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                    history.push('/login?redirect=' + window.location.pathname);
                }
            }

            function onLocalFailure(error) {
                setIsLoading(false)
                Toaster.showDefaultRequestError();
            }

            const config = {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            };

            const fileInputFormData = new FormData();
            fileInputFormData.append('file', fileToUpload);

            Api.post('upload/repoItemFiles', onValidate, onSuccess, onLocalFailure, onServerFailure, config, fileInputFormData)
        }
    }

    function patchRepoItemAfterUploadingFiles(currentRepoItem, formData, status) {
        if (!isLoading) {
            setIsLoading(true)
        }
        const answers = formFieldHelper.getAllFormAnswersForRepoItem(repoItem, formData)

        function onValidate(response) {
            setIsLoading(false)
        }

        function onSuccess(response) {
            props.onSuccessfulSave(Api.dataFormatter.deserialize(response.data))
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
                "id": repoItem.id,
                "attributes": {
                    "status": status,
                    "repoType": repoItem.repoType,
                    "answers": answers
                }
            }
        };

        const url = "repoItems/" + currentRepoItem.id
        Api.patch(url, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData)
    }
}

export function FormStep(props) {
    const {t} = useTranslation();

    return <div className={'form-step'} onClick={props.onClick}>
        <div className='flex-row'>
            <div className={'form-step-circle flex-center ' + (props.active && 'active') + (props.completed ? ' completed' : '')}>{props.number}</div>
            <div className='flex-column'>
                <div className='form-step-number'>
                    {t('popup.step') + ' ' + props.number}
                </div>
                <div className={'form-step-title ' + (props.active && 'active')}>
                    {props.title}
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
                {t('repoitem.personinvolvedadd')}
            </h3>
            {props.stepList}
            <HorizontalTabList tabsTitles={[t('repoitem.popup.search'), t('repoitem.popup.manual')]}
                               selectedIndex={currentMode}
                               onTabClick={setCurrentMode}/>
            <div className='repoitem-form-person-select'>
                {currentMode === 0 && <SearchAndSelectPersonTable
                    setSelectedPerson={props.onPersonSelect}
                    personClicked={id => window.open(`../../profile/${id}`)}
                />}
                {currentMode === 1 && <CreatePersonForm setSelectedPerson={props.onPersonSelect}/>}
            </div>
        </div>
    )
}


function SelectLearningObjectStep(props) {
    const {t} = useTranslation()

    return (
        <div>
            <h3 className='repoitem-form-popup-title'>
                {t('repoitem.addlearningobject')}
            </h3>
            {props.stepList}
            <div className='repoitem-form-person-select'>
                <SearchRepoItemTable setSelectedRepoItem={props.onRepoItemSelect}
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


export default RelatedRepoItemContent