import React, {useEffect, useLayoutEffect, useRef, useState} from "react";
import './profile.scss'
import AppStorage, {StorageKey, useAppStorageState} from "../util/AppStorage";
import Page, {GlobalPageMethods} from "../components/page/Page";
import {Redirect, useHistory, useLocation} from "react-router-dom";
import {useTranslation} from "react-i18next";
import HorizontalTabList from "../components/horizontaltablist/HorizontalTabList";
import ProfileContent from "./profilecontent/ProfileContent";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import {createAndNavigateToRepoItem, goToEditPublication} from "../publications/Publications";
import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import ProfileRolesRightsContent from "./profilerolesrights/ProfileRolesRightsContent";
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import {faChevronDown, faPlus} from "@fortawesome/free-solid-svg-icons";
import ReactPublicationTable from "../components/reacttable/tables/publication/ReactPublicationTable";
import PageHeader from "../styled-components/PageHeader";
import MarginWrapper from "../styled-components/MarginWrapper";
import SURFButton from "../styled-components/buttons/SURFButton";
import styled from "styled-components";
import {Tooltip} from "../components/field/FormField";
import {ProfileNotificationsContent} from "./notifications/ProfileNotificationsContent";
import {spaceCadetLight, SURFShapeLeft, SURFShapeRight, white} from "../Mixins";
import VerificationPopup from "../verification/VerificationPopup";
import Dropdown from "../components/dropdown/Dropdown";
import ModalButton, {
    MODAL_ALIGNMENT as DropdownListShape,
    MODAL_ALIGNMENT, MODAL_VERTICAL_ALIGNMENT
} from "../styled-components/buttons/ModalButton";
import BasicDropdownList from "../styled-components/dropdowns/dropdown-lists/BasicDropdownList";
import DropdownItemWithIcon from "../styled-components/dropdowns/dropdown-items/DropdownItemWithIcon";
import LayersIcon from "../resources/icons/ic-layers.svg";
import SaveIcon from "../resources/icons/ic-save.svg";
import EyeIcon from "../resources/icons/ic-eye.svg";
import TrashIcon from "../resources/icons/ic-trash.svg";
import ProfileDetailsPopup from "./profiledetailspopup/ProfileDetailsPopup";

export default function Profile(props) {
    const [user] = useAppStorageState(StorageKey.USER);
    const [isUserRefreshed, setIsUserRefreshed] = useState(false)
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const [personsToMerge, setPersonsToMerge] = useAppStorageState(StorageKey.PERSONS_TO_MERGE)
    const [profileData, setProfileData] = useState(null);
    const [notifications, setNotifications] = useState(null);
    const history = useHistory();

    const [tabSelectedIndex, setTabSelectedIndex] = useState()
    const paramUserId = props.match.params.id;
    const userIsSiteManager = userRoles ? userRoles.find(role => {return role === "Siteadmin"}) : false;
    const userCanSeeOwnInstitutes = userRoles ? userRoles.find(role => {return role === "Siteadmin" || role === "Staff"}) : false;
    const {t} = useTranslation()

    const notificationFormRef= useRef();
    const profileDataFormRef = useRef();
    const makingNewProfile = props.profileData === undefined

    useEffect(() => {
        if (!isUserRefreshed) {
            getProfile();
        }
        if (profileData && !notifications) {
            getNotifications();
        }
    }, [props.location, profileData] /* This will make sure user id in url or own user id will be used */);

    if (user === null) {
        return <Redirect to={'login?redirect=profile'}/>
    }

    let content;
    if (profileData) {
        content = <div>
            <MarginWrapper bottom={'50px'}>
                <PageHeader
                    title={profileData.name}
                    button={actions()}
                />
            </MarginWrapper>

            <TabContent {...props}
                        profileData={profileData}
                        setProfileData={setProfileData}
                        tabSelectedIndex={tabSelectedIndex}
                        setTabSelectedIndex={setTabSelectedIndex}
                        onClickEditPublication={onClickEditPublication}
                        paramUserId={paramUserId}
                        user={user}
                        userIsSiteManager={userIsSiteManager}
                        userCanSeeOwnInstitutes={userCanSeeOwnInstitutes}
                        notifications={notifications ?? null}
                        notificationFormRef={notificationFormRef}
                        profileDataFormRef={profileDataFormRef}
            />
        </div>;
    } else {
        content = <LoadingIndicator/>;
    }

    function actions() {
        const dropdownItems = [
            new DropdownItemWithIcon(LayersIcon, t("profile.profile_merge"), () => { addProfileDataToMergeList() }),
            new DropdownItemWithIcon(EyeIcon, t("profile.details"),  () => { ProfileDetailsPopup.show(profileData) }),
        ];

        if (userIsSiteManager && profileData.permissions.canDelete === true && paramUserId && user.id !== paramUserId) {
            dropdownItems.push(new DropdownItemWithIcon(TrashIcon, t("action.delete"),  () => { deleteProfile() }))
        }

        if ((tabSelectedIndex === 0 || tabSelectedIndex === 4) && (makingNewProfile || profileData.permissions.canEdit === true)) {
            dropdownItems.unshift(new DropdownItemWithIcon(SaveIcon, t("action.save"),() => {
                getSaveButtonRef().click()
            }))
        }

        return <ModalButton
            modalHorizontalAlignment={MODAL_ALIGNMENT.RIGHT}
            modalVerticalAlignment={MODAL_VERTICAL_ALIGNMENT.BOTTOM}
            modalButtonSpacing={4}
            modal={
                <BasicDropdownList
                    listShape={DropdownListShape.LEFT}
                    listWidth={'196px'}
                    dropdownItems={dropdownItems}
                />
            }
            button={
                <SURFButton
                    shape={SURFShapeRight}
                    highlightColor={spaceCadetLight}
                    text={"Opties"}
                    iconEnd={faChevronDown}
                    iconEndColor={'white'}
                    padding={'0px 24px 0px 32px'}
                />
            }
        />
    }

    function buttonRow() {
        return (
            <SURFButtonRow>
                {userIsSiteManager && user.id !== profileData.id && !profileData.hasLoggedIn && (
                    <SURFButtonContainer>
                        <SURFButton
                            padding={"0 40px 0 40px"}
                            text={t("profile.profile_merge")}
                            onClick={() => addProfileDataToMergeList()}
                        />
                        <Tooltip text={t("profile.profile_merge_tooltip")}/>
                    </SURFButtonContainer>
                )}

                {(tabSelectedIndex === 0 || tabSelectedIndex === 4) && (makingNewProfile || profileData.permissions.canEdit === true) && (
                    <SURFButton
                        highlightColor={spaceCadetLight}
                        width={"130px"}
                        text={t("action.save")}
                        onClick={() => getSaveButtonRef().click()}
                    />
                )}
            </SURFButtonRow>
        );
    }

    function getSaveButtonRef() {
        return tabSelectedIndex === 0 ? profileDataFormRef.current : notificationFormRef.current
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

    function addProfileDataToMergeList() {
        const personsToMerge = AppStorage.get(StorageKey.PERSONS_TO_MERGE)
        if (!personsToMerge) {
            AppStorage.set(StorageKey.PERSONS_TO_MERGE, [profileData])
        } else {
            const personAlreadyAdded = personsToMerge.find(profile => profileData.id === profile.id)
            if (personAlreadyAdded) {
                Toaster.showToaster({type: "info", message: t("profile.profile_merge_already_added_message")})
                return
            }
            setPersonsToMerge([...personsToMerge, profileData])
        }
    }

    function onClickEditPublication(itemProps) {
        goToEditPublication(props, itemProps)
    }

    function getProfile() {
        const userId = paramUserId ?? user.id

        const config = {
            params: {
                'include': "groups.partOf,config",
                'fields[groups]': 'partOf,title,labelNL,labelEN',
                'fields[institutes]': 'title,level,type',
            }
        };

        Api.jsonApiGet('persons/' + userId, onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsUserRefreshed(true)
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

    function getNotifications() {
        const config = {
            params: {
                'include': "notificationCategory,notificationSettings",
                "filter[notificationVersion][LE]": profileData.config.notificationVersion,
            }
        };

        Api.jsonApiGet('notifications', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            setNotifications(response.data)
        }

        function onServerFailure(error) {
            console.log(error);
            Toaster.showServerError(error)
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
            console.log(error);
        }
    }

    function deleteProfileWithCallback(profileId, successCallback, errorCallback) {
        function onValidate(response) {
        }

        function onSuccess(response) {
            successCallback(response.data);
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
            errorCallback()
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
            errorCallback()
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const patchData = {
            "data": {
                "type": "person",
                "id": profileId,
                "attributes": {
                    "isRemoved": true
                }
            }
        };

        Api.patch(`persons/${profileId}`, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData);
    }

    function deleteProfile() {
        return VerificationPopup.show(t("verification.profile.delete.title"), t("verification.profile.delete.subtitle"), () => {
            deleteProfileWithCallback(profileData.id, (responseData) => {
                GlobalPageMethods.setFullScreenLoading(false)
                history.push("/profiles");
            }, () => {
                GlobalPageMethods.setFullScreenLoading(false)
                Toaster.showDefaultRequestError();
            })
        })
    }
}

function TabContent(props) {
    const {t} = useTranslation();
    const location = useLocation();

    useEffect(() => {
        if (location.hash === "#owner"){
            props.setTabSelectedIndex(1)
        } else if (location.hash === "#author"){
            props.setTabSelectedIndex(2)
        } else if (location.hash === "#groups"){
            props.setTabSelectedIndex(3)
        } else if (location.hash === "#notifications"){
            props.setTabSelectedIndex(4)
        } else {
            props.setTabSelectedIndex(0)
        }
    }, [location])

    useLayoutEffect(() => {
        if (props.tabSelectedIndex === 0) {
            window.location.hash = '';
            document.title = props.user.name
        } else if (props.tabSelectedIndex === 1){
            window.location.hash = '#owner';
            document.title = "Owner of"
        } else if (props.tabSelectedIndex === 2){
            window.location.hash = '#author';
            document.title = "Author of"
        } else if (props.tabSelectedIndex === 3){
            window.location.hash = '#groups';
            document.title = "Groups"
        } else if (props.tabSelectedIndex === 4){
            window.location.hash = '#notifications';
            document.title = "Notifications"
        }
    }, [props.tabSelectedIndex])

    if (props.profileData.permissions.canEdit === true) {
        return <div>
            <HorizontalTabList
                tabsTitles={[t("profile.tab_profile"), t('profile.tab_owned_publications'), t('profile.tab_mentioned_publications'), t('profile.tab_roles_rights'), t('profile.tab_notifications')]}
                selectedIndex={props.tabSelectedIndex} onTabClick={props.setTabSelectedIndex}/>
            <Visibility visible={props.tabSelectedIndex === 0}
                        content={
                            <ProfileContent key={props.profileData.id} {...props}/>
                        }/>
            {<Visibility visible={props.tabSelectedIndex === 1} content={
                <OwnedPublicationsContent key={props.profileData.id} {...props}/>}
            />}
            {<Visibility visible={props.tabSelectedIndex === 2} content={<MentionedPublicationsContent key={props.profileData.id} {...props}/>}/>}
            {<Visibility visible={props.tabSelectedIndex === 3}
                         content={<ProfileRolesRightsContent key={props.profileData.id} groups={props.profileData.groups}/>}/>}
            {<Visibility visible={props.tabSelectedIndex === 4}
                         content={<ProfileNotificationsContent key={props.profileData.id} {...props}/>}/>}
        </div>
    } else {
        return <div>
            <HorizontalTabList
                tabsTitles={[t("profile.tab_profile"), t('profile.tab_owned_publications'), t('profile.tab_mentioned_publications'), t('profile.tab_notifications')]}
                selectedIndex={props.tabSelectedIndex}
                onTabClick={props.setTabSelectedIndex}/>
            <Visibility visible={props.tabSelectedIndex === 0}
                        content={<ProfileContent {...props}/>}/>
            {<Visibility visible={props.tabSelectedIndex === 1} content={
                <OwnedPublicationsContent {...props}/>}
            />}
            {<Visibility visible={props.tabSelectedIndex === 2} content={<MentionedPublicationsContent {...props}/>}/>}
            {<Visibility visible={props.tabSelectedIndex === 4} content={<ProfileNotificationsContent {...props}/>}/>}
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

const Modal = styled.div`
    display: flex;
    flex-direction: column;
    min-width: 196px;
    gap: 5px;
    background: ${white};
    padding: 10px;
    ${SURFShapeLeft};
    box-shadow: 0px 4px 10px rgba(45, 54, 79, 0.2);
`;

function Visibility(props) {
    return <div style={{display: props.visible ? 'block' : 'none'}}>
        {props.content}
    </div>
}

const SURFButtonContainer = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
    
`;

const SURFButtonRow = styled.div`
    display: flex;
    flex-direction: row;
    gap: 24px;
    align-items: center;
`;