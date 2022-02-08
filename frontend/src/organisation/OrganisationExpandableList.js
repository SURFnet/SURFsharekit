import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import React, {useEffect, useState} from "react";
import {useTranslation} from "react-i18next";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {ExpandableList} from "./ExpandableList";
import {HelperFunctions} from "../util/HelperFunctions";
import {useHistory} from "react-router-dom";

export function OrganisationExpandableList(props) {
    const [isLoading, setIsLoading] = useState(false)
    const [institutes, setInstitutes] = useState(null)
    const history = useHistory();
    const user = useAppStorageState(StorageKey.USER)[0];
    const {t} = useTranslation();

    useEffect(() => {
        setIsLoading(true)
        getMember(user.id, history,(member) => {
            setIsLoading(false)
            setInstitutes(HelperFunctions.getMemberRootInstitutes(member))
        }, () => {
            setIsLoading(false)
        })
    }, [user.id]);

    return (
        <ExpandableList data={institutes}
                        loadingText={t("organisation.loading")}
                        isLoading={isLoading}
                        onClickExpand={(instituteParentId) => {
                            console.log(instituteParentId);
                        }}
        />
    )
}

export function getMember(userId, history, successCallback, errorCallback = null) {

    function onValidate(response) {
    }

    function onSuccess(response) {
        console.log("Success fetching root institutes: ", response);
        successCallback(response.data)
    }

    function onLocalFailure(error) {
        errorCallback()
        Toaster.showDefaultRequestError();
        console.log(error);
    }

    function onServerFailure(error) {
        console.log(error);
        Toaster.showServerError(error)
        if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
            history.push('/login?redirect=' + window.location.pathname);
        }
        errorCallback()
    }
    const config = {
        params: {
            'fields[institutes]': 'title,permissions,isRemoved,level,abbreviation,summary,type,childrenInstitutesCount,isBaseScopeForUser',
            'fields[groups]': 'partOf'
        }
    };
    Api.jsonApiGet(`persons/${userId}?include=groups.partOf`, onValidate, onSuccess, onLocalFailure, onServerFailure, config);
}