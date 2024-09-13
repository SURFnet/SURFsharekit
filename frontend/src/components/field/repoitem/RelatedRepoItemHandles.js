import ValidationError from "../../../util/ValidationError";
import Toaster from "../../../util/toaster/Toaster";
import Api from "../../../util/api/Api";
import RepoItemPopup from "../relatedrepoitempopup/RelatedRepoItemPopup";
import {useHistory} from "react-router-dom";
import {useEffect} from "react";

export function showRepoItemPopup(currentFormReducerStateRef, field, repoItemToShowId, files, onSuccessCallback, baseRepoItem, repoItemIdsToExcludeInSearch = [], getRepoItemFunction = null, repoItems = []) {
    function onSuccessfulSaveOfRelatedRepoItem(savedRelatedRepoItems) {
        let currentFieldAnswers = null;
        Object.keys(currentFormReducerStateRef).forEach(function (key, value) {
            if (key === field.key) {
                currentFieldAnswers = currentFormReducerStateRef[key];
            }
        });

        if (!currentFieldAnswers) {
            currentFieldAnswers = []
        }

        let newRepoItemFieldAnswers = {}
        savedRelatedRepoItems.forEach(savedRelatedRepoItem => {
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

                // update current fieldAnswers so the newRepoItemFieldAnswers don't overwrite each other
                currentFieldAnswers.push(
                    {
                        id: savedRelatedRepoItem.id,
                        summary: savedRelatedRepoItem.summary
                    }
                )

            }
        })

        const newFormReducerState = {
            ...currentFormReducerStateRef,
            ...newRepoItemFieldAnswers
        };


        onSuccessCallback(newFormReducerState)
    }

    function onCancel() {
        console.log("Cancel Related RepoItem popup pressed")
    }

    RepoItemPopup.show(repoItemToShowId, files, onSuccessfulSaveOfRelatedRepoItem, onCancel, baseRepoItem, repoItemIdsToExcludeInSearch, getRepoItemFunction, repoItems)
}

export function createRelatedRepoItem(repoType, instituteId, history, successCallback, failureCallback) {

    function validator(response) {
        const repoItemData = response.data ? response.data.data : null;
        if (!(repoItemData && repoItemData.id && repoItemData.attributes)) {
            throw new ValidationError("The received repo item data is invalid")
        }
    }

    function onSuccess(response) {
        successCallback(Api.dataFormatter.deserialize(response.data))
    }

    function onLocalFailure(error) {
        Toaster.showDefaultRequestError()
        failureCallback(error)
    }

    function onServerFailure(error) {
        Toaster.showServerError(error)
        failureCallback(error)
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
        [field.key]: [...currentFieldAnswers,  ...value]
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