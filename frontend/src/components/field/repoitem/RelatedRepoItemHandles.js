import ValidationError from "../../../util/ValidationError";
import Toaster from "../../../util/toaster/Toaster";
import Api from "../../../util/api/Api";
import RepoItemPopup from "../relatedrepoitempopup/RelatedRepoItemPopup";
import {useHistory} from "react-router-dom";

export function showRepoItemPopup(currentFormReducerStateRef, field, repoItemToShowId, file, onSuccessCallback, baseRepoItem, repoItemIdsToExcludeInSearch = []) {

    function onSuccessfulSaveOfRelatedRepoItem(savedRelatedRepoItem) {
        let currentFieldAnswers = null;
        Object.keys(currentFormReducerStateRef).forEach(function (key, value) {
            if (key === field.key) {
                currentFieldAnswers = currentFormReducerStateRef[key];
            }
        });

        if (!currentFieldAnswers) {
            currentFieldAnswers = []
        }

        let newRepoItemFieldAnswers
        const existingItem = currentFieldAnswers.find((findItem) => { return findItem.id === savedRelatedRepoItem.id })
        if(existingItem) {
            existingItem.summary = savedRelatedRepoItem.summary
            newRepoItemFieldAnswers = {
                [field.key]: [
                    ...currentFieldAnswers
                ]
            };
        } else {
            newRepoItemFieldAnswers = {
                [field.key]: [
                    ...currentFieldAnswers,
                    {
                        id: savedRelatedRepoItem.id,
                        summary: savedRelatedRepoItem.summary
                    }
                ]
            };
        }

        const newFormReducerState = {
            ...currentFormReducerStateRef,
            ...newRepoItemFieldAnswers
        };


        onSuccessCallback(newFormReducerState)
    }

    function onCancel() {
        console.log("Cancel Related RepoItem popup pressed")
    }

    RepoItemPopup.show(repoItemToShowId, file, onSuccessfulSaveOfRelatedRepoItem, onCancel, baseRepoItem, repoItemIdsToExcludeInSearch)
}

export function createRelatedRepoItem(repoType, instituteId, history, successCallback) {

    function validator(response) {
        const repoItemData = response.data ? response.data.data : null;
        if (!(repoItemData && repoItemData.id && repoItemData.attributes)) {
            throw new ValidationError("The received repo item data is invalid")
        }
    }

    function onSuccess(response) {
        const relatedRepoItem = response.data.data
        successCallback(relatedRepoItem)
    }

    function onLocalFailure(error) {
        Toaster.showDefaultRequestError()
    }

    function onServerFailure(error) {
        Toaster.showServerError(error)
        if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
            history.push('/login?redirect=' + window.location.pathname);
        }
    }

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
                        "id": instituteId
                    }
                }
            }
        }
    };

    Api.post('repoItems', validator, onSuccess, onLocalFailure, onServerFailure, config, postData)
}

export function removeRelatedRepoItem(currentFormReducerStateRef, field, repoItemID, successCallback) {
    let currentFieldAnswers = null;
    Object.keys(currentFormReducerStateRef).forEach(function (key, value) {
        if (key === field.key) {
            currentFieldAnswers = currentFormReducerStateRef[key];
        }
    });

    let filteredRepoItemFieldAnswers = currentFieldAnswers.filter(answerValue => {
        return answerValue.id !== repoItemID
    });

    const newRepoItemFieldAnswers = {
        [field.key]: [
            ...filteredRepoItemFieldAnswers
        ]
    }

    const newFormReducerState = {
        ...currentFormReducerStateRef,
        ...newRepoItemFieldAnswers
    }
    successCallback(newFormReducerState)
}

export function removeValues(currentFormReducerStateRef, field, value, successCallback) {
    let currentFieldAnswers = null;
    Object.keys(currentFormReducerStateRef).forEach(function (key, value) {
        if (key === field.key) {
            currentFieldAnswers = currentFormReducerStateRef[key];
        }
    });

    let filteredRepoItemFieldAnswers = currentFieldAnswers.filter(answerValue => {
        return answerValue !== value
    });

    const newRepoItemFieldAnswers = {
        [field.key]: [
            ...filteredRepoItemFieldAnswers
        ]
    }

    const newFormReducerState = {
        ...currentFormReducerStateRef,
        ...newRepoItemFieldAnswers
    }
    successCallback(newFormReducerState);
}

export function createValues(currentFormReducerStateRef, field, value, successCallback){
    console.log(`value to be added: ${value}`)
    let currentFieldAnswers = null;
    Object.keys(currentFormReducerStateRef).forEach(function (key, value) {
        if (key === field.key) {
            currentFieldAnswers = currentFormReducerStateRef[key];
        }
    });

    if(!currentFieldAnswers){
        currentFieldAnswers = [];
    }

    const newRepoItemFieldAnswers = {
        [field.key]: [...currentFieldAnswers, ...value]
    }

    const newFormReducerState = {
      ...currentFormReducerStateRef,
      ...newRepoItemFieldAnswers
    }
    successCallback(newFormReducerState);
}

export function setRelatedRepoItemOrder(currentFormReducerStateRef, field, value, successCallback) {
    const newRepoItemFieldAnswers = {
        [field.key]: [
            ...value
        ]
    }

    const newFormReducerState = {
        ...currentFormReducerStateRef,
        ...newRepoItemFieldAnswers
    }

    successCallback(newFormReducerState)
}