import React, {useEffect, useRef, useState} from "react";
import {useTranslation} from "react-i18next";
import {faCheck, faChevronDown, faChevronRight} from "@fortawesome/free-solid-svg-icons";
import {ReactComponent as IconLanguage} from "../../resources/icons/ic-language.svg";
import moment from 'moment'
import {StorageKey, useAppStorageState} from "../../util/AppStorage";
import TasksIcon from '../../resources/icons/ic-tasks-white.svg';
import NotificationIcon from '../../resources/icons/ic-notification.svg';
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import styled from "styled-components";
import {
    desktopSideMenuWidth,
    majorelle,
    mobileTabletMaxWidth,
    oceanGreen,
    openSans,
    spaceCadet,
    white
} from "../../Mixins";
import TopMenuSearch from "./components/TopMenuSearch";
import TopMenuButton from "./components/TopMenuButton";
import LanguageSwitch from "./components/LanguageSwitch";
import ProfileDropdown from "./components/ProfileDropdown";
import {useHistory, useLocation} from "react-router-dom";
import {useGlobalState} from "../../util/GlobalState";
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import {UpdateTaskCountEvent} from "../../util/events/Events";

function TopMenu(props) {
    const {t} = useTranslation();
    const location = useLocation();
    const history = useHistory();
    const cancelToken = useRef();

    const [user, setUser] = useAppStorageState(StorageKey.USER);
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);
    const userIsSiteAdminOrSupporter = userRoles ? userRoles.find(role => role === 'Supporter' || role === 'Siteadmin') : false;
    const [isTopMenuVisible, setTopMenuVisible] = useGlobalState('isTopMenuVisible', true);
    const [isEnvironmentBannerVisible, setIsEnvironmentBannerVisible] = useGlobalState("isEnvironmentBannerVisible",false);

    const [isFetchingTasks, setIsFetchingTasks] = useState(false);
    const [taskCount, setTaskCount] = useState(0);

    useEffect(() => {
        if (user && isTopMenuVisible) {
            getTaskCount()
        }
    }, [location])

    useEffect(() => {
        window.addEventListener("UpdateTaskCountEvent", handleUpdateTaskCountEvent);
        return () => window.removeEventListener("UpdateTaskCountEvent", handleUpdateTaskCountEvent);
    }, []);

    function handleUpdateTaskCountEvent (event) {
        setTaskCount(event.data.count)
    }

    return (
        <>
            {user && isTopMenuVisible &&
            <TopMenuRoot disableBorderRadius={window.location.pathname === '/'}>
                <MenuContentContainer sideMenuCollapsed={isSideMenuCollapsed}>
                    <LeftContentContainer>
                        {props.breadcrumbs && getBreadCrumbs()}
                    </LeftContentContainer>

                    <RightContentContainer>
                        <TopMenuSearch
                            placeholder={t("top_menu.search.placeholder")}
                        />

                        {userIsSiteAdminOrSupporter &&
                            <TopMenuButton
                                icon={TasksIcon}
                                onClick={() => {
                                    history.push("/dashboard")
                                }}
                                count={taskCount}
                            />
                        }

                        {/* TODO: Yet to be implemented*/}
                        {/*<TopMenuButton*/}
                        {/*    icon={NotificationIcon}*/}
                        {/*    onClick={() => {*/}
                        {/*    }}*/}
                        {/*    count={0}*/}
                        {/*/>*/}

                        <LanguageSwitch/>

                        <ProfileDropdown/>

                    </RightContentContainer>
                </MenuContentContainer>
            </TopMenuRoot>
            }
        </>
    );

    function getBreadCrumbs() {
        const elements = [];
        props.breadcrumbs.forEach((breadcrumb, index) => {
            if (index !== props.breadcrumbs.length - 1) {
                elements.push(
                    <BreadCrumb isClickable={true} onClick={() => {
                        history.push(breadcrumb.path)
                    }}>
                        {t(breadcrumb.title)}
                    </BreadCrumb>
                )
            } else {
                elements.push(<BreadCrumb isClickable={false}>{t(breadcrumb.title)}</BreadCrumb>)
            }
            if (index !== props.breadcrumbs.length - 1) {
                elements.push(<Chevron icon={faChevronRight}/>)
            }
        })
        return elements
    }

    function getTaskCount() {
        setIsFetchingTasks(true)

        const config = {
            params: {
                'filter[state][EQ]': 'INITIAL',
                'sort': 'created',
                'page[size]': 1,
                'page[number]': 1
            }
        };

        config.cancelToken = cancelToken.current;
        cancelToken.current = Api.jsonApiGet('tasks', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsFetchingTasks(false)
            setTaskCount(response.meta.totalCount);
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
        }
    }
}

const TopMenuRoot = styled.div`
    height: 60px;
    border-radius: ${props => props.disableBorderRadius ? '0' : '0px 0px 15px 15px'};
    background: ${majorelle};
`;

const MenuContentContainer = styled.div`
    width: 100%;
    height: 100%;
    padding: ${props => props.sideMenuCollapsed ? `0px 40px 0px 82px` : `0px 40px 0px calc(${desktopSideMenuWidth} + 82px)`};
`;

const RightContentContainer = styled.div`
    height: 100%;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 5px;
    float: right;
`;

const LeftContentContainer = styled.div`
    height: 100%;
    display: flex;
    flex-direction: row;
    flex: 1;
    gap: 10px;
    align-items: center;
    justify-content: flex-end;
    float: left;
    
    @media only screen and (max-width: ${mobileTabletMaxWidth}px) {
        display: none;
    }
`;

const BreadCrumb = styled.a`
    ${openSans};
    font-size: 12px;
    color: ${white};
    cursor: ${ props => props.isClickable ? 'pointer' : 'default'};
    text-decoration: ${ props => props.isClickable ? 'underline' : 'default'};
    &:hover {
        color: ${white};
        text-decoration: ${ props => props.isClickable ? 'underline' : 'default'};
    }
    
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100px;
`;

const Chevron = styled(FontAwesomeIcon)`
    font-size: 10px;
    color: ${white};
`;

export default TopMenu;