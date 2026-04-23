import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import React, {useEffect, useState} from "react";
import {useTranslation} from "react-i18next";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {ExpandableList} from "./ExpandableList";
import {HelperFunctions} from "../util/HelperFunctions";
import {useNavigate} from "react-router-dom";
import {useNavigation} from "../providers/NavigationProvider";

export function OrganisationExpandableList(props) {
    const [isLoading, setIsLoading] = useState(false)
    const [institutes, setInstitutes] = useState(null)
    const navigate = useNavigation();
    const user = useAppStorageState(StorageKey.USER)[0];
    const {t} = useTranslation();

    useEffect(() => {
        setIsLoading(true)
        getMember(user.id, navigate, props.showInactive, (member) => {
            setIsLoading(false)
            setInstitutes(HelperFunctions.getMemberRootInstitutes(member))
        }, (error) => {
            setIsLoading(false)
            Toaster.showServerError(error);
        })
    }, [user.id, props.showInactive]);

    useEffect(() => {
        console.log(institutes)
    }, [institutes]);

    return (
        <ExpandableList data={institutes}
                        loadingText={t("organisation.loading")}
                        isLoading={isLoading}
                        showInactive={props.showInactive}
                        onClickExpand={(instituteParentId) => {
                            console.log(instituteParentId);
                        }}
        />
    )
}

export function getMember(userId, navigate, showInactive, successCallback, errorCallback = () => {
}) {
    function onValidate(response) {
    }

    function onSuccess(response) {
        console.log("Success fetching root institutes: ", response);
        successCallback(response.data)
    }

    function onLocalFailure(error) {
        console.log(error);
        errorCallback(error)
    }

    function onServerFailure(error) {
        console.log(error);
        if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
            navigate('/login?redirect=' + window.location.pathname);
        }
        errorCallback(error)
    }

    const config = {
        params: {
            'fields[institutes]': 'title,permissions,isRemoved,isHidden,level,abbreviation,summary,type,childrenInstitutesCount,totalPublicationsCount,isBaseScopeForUser',
            'fields[groups]': 'partOf',
            'filter[isHidden]': '0'
        }
    };

    if (!showInactive) {
        config.params['filter[inactive]'] = '0';
    }

    Api.jsonApiGet(`persons/${userId}?include=groups.partOf`, onValidate, onSuccess, onLocalFailure, onServerFailure, config);
}
