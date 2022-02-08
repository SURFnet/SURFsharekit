import React, {useEffect, useRef, useState} from "react";
import './profile.scss'
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import Page, {GlobalPageMethods} from "../components/page/Page";
import {Redirect} from "react-router-dom";
import {useTranslation} from "react-i18next";
import HorizontalTabList from "../components/horizontaltablist/HorizontalTabList";
import ProfileContent from "./profilecontent/ProfileContent";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import {createAndNavigateToRepoItem, goToEditPublication} from "../publications/Publications";
import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import ProfileRolesRightsContent from "./profilerolesrights/ProfileRolesRightsContent";
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import {faPlus} from "@fortawesome/free-solid-svg-icons";
import ReactPublicationTable from "../components/reacttable/tables/ReactPublicationTable";

export default function Profile(props) {
    const [user] = useAppStorageState(StorageKey.USER);
    const [profileData, setProfileData] = useState(null);
    const tabSelectedIndexRef = useRef();
    const paramUserId = props.match.params.id;

    useEffect(() => {
        if (user !== null) {
            getProfile();
        }
    }, [props.location] /* This will make sure user id in url or own user id will be used */);

    if (user === null) {
        return <Redirect to={'unauthorized?redirect=profile'}/>
    }

    let content;
    if (profileData) {
        content = <div>
            <div className={"user-header"}>
                <h1>{profileData.name}</h1>
            </div>

            <TabContent {...props}
                        profileData={profileData}
                        setProfileData={setProfileData}
                        tabSelectedIndexRef={tabSelectedIndexRef}
                        onClickEditPublication={onClickEditPublication}
                        paramUserId={paramUserId}
                        user={user}
            />
        </div>;
    } else {
        content = <LoadingIndicator/>;
    }

    return <Page id="profile"
                 history={props.history}
                 showBackButton={true}
                 breadcrumbs={[
                     {
                         path: '/dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         title: 'profile.tab_profile'
                     }
                 ]}
                 content={content}/>

    function onClickEditPublication(itemProps) {
        goToEditPublication(props, itemProps)
    }

    function getProfile() {
        const userId = paramUserId ?? user.id

        const config = {
            params: {
                'include': "groups.partOf",
                'fields[groups]': 'partOf,title',
                'fields[institutes]': 'title,level,type'
            }
        };

        Api.jsonApiGet('persons/' + userId, onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            if (response.data.isRemoved) {
                props.history.replace('/removed');
            } else {
                setProfileData(response.data);
            }
        }

        function onServerFailure(error) {
            console.log(error);
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                props.history.push('/login?redirect=' + window.location.pathname);
            } else if (error && error.response && (error.response.status === 404 || error.response.status === 400)) { //The object to access does not exist
                props.history.replace('/notfound');
            } else if (error && error.response && (error.response.status === 423)) { //The object is inaccesible
                props.history.replace('/removed');
            } else if (error && error.response && error.response.status === 403) { //The object to access is forbidden to view
                props.history.replace('/forbidden');
            } else { //The object to access does not exist
                props.history.replace('/unauthorized');
            }
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
            console.log(error);
        }
    }
}

function TabContent(props) {
    const {t} = useTranslation();
    const [selectedIndex, setSelectedIndex] = useState((props.tabSelectedIndexRef.current) ? props.tabSelectedIndexRef.current : 0);

    useEffect(() => {
        props.tabSelectedIndexRef.current = selectedIndex;
    }, [selectedIndex]);

    if (props.profileData.permissions.canEdit === true) {
        return <div>
            <HorizontalTabList
                tabsTitles={[t("profile.tab_profile"), t('profile.tab_owned_publications'), t('profile.tab_mentioned_publications'), t('profile.tab_roles_rights')]}
                selectedIndex={selectedIndex} onTabClick={setSelectedIndex}/>
            <Visibility visible={selectedIndex === 0}
                        content={
                            <ProfileContent key={props.profileData.id} {...props}/>
                        }/>
            {<Visibility visible={selectedIndex === 1} content={
                <OwnedPublicationsContent key={props.profileData.id} {...props}/>}
            />}
            {<Visibility visible={selectedIndex === 2} content={<MentionedPublicationsContent key={props.profileData.id} {...props}/>}/>}
            {<Visibility visible={selectedIndex === 3}
                         content={<ProfileRolesRightsContent key={props.profileData.id} groups={props.profileData.groups}/>}/>}
        </div>
    } else {
        return <div>
            <HorizontalTabList
                tabsTitles={[t("profile.tab_profile"), t('profile.tab_owned_publications'), t('profile.tab_mentioned_publications')]}
                selectedIndex={selectedIndex}
                onTabClick={setSelectedIndex}/>
            <Visibility visible={selectedIndex === 0}
                        content={<ProfileContent {...props}/>}/>
            {<Visibility visible={selectedIndex === 1} content={
                <OwnedPublicationsContent {...props}/>}
            />}
            {<Visibility visible={selectedIndex === 2} content={<MentionedPublicationsContent {...props}/>}/>}
        </div>
    }
}

function PublicationsContent(props) {
    const {t} = useTranslation();

    return <div id={"tab-publications"} className={"tab-content-container"}>
        <h2 className={"tab-title"}>{t("profile.tab_publications")}</h2>
        <div className={"actions-row"}>
            {!props.paramUserId && <IconButtonText faIcon={faPlus}
                                                   buttonText={t("my_publications.add_publication")}
                                                   onClick={() => {
                                                       GlobalPageMethods.setFullScreenLoading(true)
                                                       createAndNavigateToRepoItem(props, () => {
                                                               GlobalPageMethods.setFullScreenLoading(false)
                                                           },
                                                           () => {
                                                               GlobalPageMethods.setFullScreenLoading(false)
                                                           })
                                                   }}/>}
        </div>
        <ReactPublicationTable
            onClickEditPublication={props.onClickEditPublication}
            history={props.history}
            filterOnUserId={props.paramUserId ?? props.user.id}
            hideEmptyStateButton={!!(props.paramUserId)}
            enablePagination={true}
            allowSearch={true}
        />
    </div>
}

function MentionedPublicationsContent(props) {
    const {t} = useTranslation();

    return <div id={"tab-publications"} className={"tab-content-container"}>
        <h2 className={"tab-title"}>{t("profile.tab_mentioned_publications")}</h2>
        <div className={"actions-row"}>
            {!props.paramUserId && <IconButtonText faIcon={faPlus}
                                                   buttonText={t("my_publications.add_publication")}
                                                   onClick={() => {
                                                       GlobalPageMethods.setFullScreenLoading(true)
                                                       createAndNavigateToRepoItem(props, () => {
                                                               GlobalPageMethods.setFullScreenLoading(false)
                                                           },
                                                           () => {
                                                               GlobalPageMethods.setFullScreenLoading(false)
                                                           })
                                                   }}/>}
        </div>
        <ReactPublicationTable
            onClickEditPublication={props.onClickEditPublication}
            history={props.history}
            searchOutOfScope={true}
            searchAddition={props.paramUserId ?? props.user.id}
            hideEmptyStateButton={!!(props.paramUserId)}
            enablePagination={true}
            allowSearch={true}
        />
    </div>
}

function OwnedPublicationsContent(props) {
    const {t} = useTranslation();

    return <div id={"tab-publications"} className={"tab-content-container"}>
        <h2 className={"tab-title"}>{t("profile.tab_owned_publications")}</h2>
        <div className={"actions-row"}>
            {!props.paramUserId && <IconButtonText faIcon={faPlus}
                                                   buttonText={t("my_publications.add_publication")}
                                                   onClick={() => {
                                                       GlobalPageMethods.setFullScreenLoading(true)
                                                       createAndNavigateToRepoItem(props, () => {
                                                               GlobalPageMethods.setFullScreenLoading(false)
                                                           },
                                                           () => {
                                                               GlobalPageMethods.setFullScreenLoading(false)
                                                           })
                                                   }}/>}
        </div>
        <ReactPublicationTable
            onClickEditPublication={props.onClickEditPublication}
            history={props.history}
            filterOnUserId={props.paramUserId ?? props.user.id}
            hideEmptyStateButton={!!(props.paramUserId)}
            enablePagination={true}
            allowSearch={true}
        />
    </div>
}

function Visibility(props) {
    return <div style={{display: props.visible ? 'block' : 'none'}}>
        {props.content}
    </div>
}