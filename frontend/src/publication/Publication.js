import React, {useEffect, useReducer, useState} from 'react';
import {useTranslation} from "react-i18next";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {Navigate, useParams} from "react-router-dom";
import RepoItemHelper from "../util/RepoItemHelper";
import Toaster from "../util/toaster/Toaster";
import RepoItemApiRequests from "../util/api/RepoItemApiRequests";
import PublishPublicationView from "../publish-publication-view/PublishPublicationView";
import DefaultPublicationView from "../default-publication-view/DefaultPublicationView";
import FormFieldHelper from "../util/FormFieldHelper";
import PublishPublicationCompletion from "../publish-publication-view/PublishPublicationCompletion";
import {useNavigation} from "../providers/NavigationProvider";

export const PublicationViewMode = {
    DEFAULT: "DEFAULT",
    PUBLISH: "PUBLISH",
    PUBLISH_COMPLETED: "PUBLISH_COMPLETED",
}

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

function Publication(props) {

    const {t} = useTranslation();
    const navigate = useNavigation();
    const params = useParams()
    const [user] = useAppStorageState(StorageKey.USER);
    const [repoItem, setRepoItem] = useState(null);
    const [viewMode, setViewMode] = useState(PublicationViewMode.DEFAULT);

    const [formReducerState, dispatch] = useReducer(reducer, initialState);

    const repoItemApiRequests = new RepoItemApiRequests();

    const repoItemIsDraft = repoItem?.status?.toLowerCase() === "draft"
    const userIsRepoItemOwner = repoItem?.creator?.id === user?.id // unused for now
    const repoItemHasNeverBeenPublished = repoItem && !repoItem.isHistoricallyPublished // unused for now

    useEffect(() => {
        if (!user) {
            return
        }
        getRepoItem();
    }, [])

    useEffect(() => {
        // Fetch RepoItem when id param of this route changes
        if (!user) {
            return
        }
        if (params.id) {
            getRepoItem()
        }
    }, [params.id])

    useEffect(() => {
        setPublicationViewMode()
    }, [repoItem])

    if (!user) {
        return <Navigate to={'/unauthorized?redirect=publications/' + params.id}/>
    }

    function getPageContent() {
        if (viewMode === PublicationViewMode.PUBLISH_COMPLETED) {
            return <PublishPublicationCompletion
                repoItem={repoItem}
                setViewMode={(newViewMode) => setViewMode(newViewMode)}
            />
        } else {
            if (repoItem && ((viewMode === PublicationViewMode.PUBLISH && !repoItem.isHistoricallyPublished && !repoItem.isRemoved && !repoItem.needsToBeFinished && !repoItem.uploadedFromApi))) {
                return (
                    <PublishPublicationView
                        repoItem={repoItem}
                        formReducerState={formReducerState}
                        dispatch={dispatch}
                        onRepoItemChanged={(updateRepoItem) => setRepoItem(updateRepoItem)}
                        setViewMode={(newViewMode) => setViewMode(newViewMode)}
                    />
                )
            } else {
                return (
                    <DefaultPublicationView
                        repoItem={repoItem}
                        formReducerState={formReducerState}
                        dispatch={dispatch}
                        onRepoItemChanged={(updateRepoItem) => setRepoItem(updateRepoItem)}
                        setViewMode={(newViewMode) => setViewMode(newViewMode)}
                    />
                )
            }
        }
    }

    return (
       getPageContent()
    );

    function setPublicationViewMode() {
        if (!repoItem) {
            return;
        }
        
        if (repoItemIsDraft && !repoItem.isHistoricallyPublished) {
            setViewMode(PublicationViewMode.PUBLISH)
        } else if (viewMode === PublicationViewMode.PUBLISH && repoItem?.status && (repoItem.status === "Submitted" || repoItem.status === "Published")) {
            setViewMode(PublicationViewMode.PUBLISH_COMPLETED)
        } else {
            setViewMode(PublicationViewMode.DEFAULT)
        }
    }

    function setInitialFormState(repoItem) {
        const formFieldHelper = new FormFieldHelper();
        let formFieldValueState = {};

        RepoItemHelper.getAllFields(repoItem).forEach((field) => {
            let fieldAnswer = formFieldHelper.getFieldAnswer(repoItem, field)
            formFieldValueState = {
                ...formFieldValueState,
                [field.key]: fieldAnswer
            }
        });

        dispatch({type: 'FORM_FIELD_ANSWERS', formFieldAnswers: formFieldValueState});
    }

    function getRepoItem() {

        function onValidate(response) {
        }

        function onSuccess(response) {
            setInitialFormState(response.data)
            setRepoItem(response.data);

        }

        function onServerFailure(error) {
            if (!error?.response) {
                Toaster.showServerError(error);
                return;
            }

            const status = error.response.status;
            
            if (status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            } else if (status === 403) { //We're not authorized to see this page
                navigate('/forbidden', {replace: true})
            } else if (status === 423) { //The object is inaccesible
                navigate('/removed', {replace: true});
            } else {
                Toaster.showServerError(error)
            }
        }

        function onLocalFailure(error) {
            Toaster.showServerError(error);
        }

        repoItemApiRequests.getRepoItem(params.id, onValidate, onSuccess, onLocalFailure, onServerFailure, ['relatedTo.lastEditor', 'creator', "tasks"])
    }
}

export default Publication;