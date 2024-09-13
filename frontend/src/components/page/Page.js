import React, {useEffect, useLayoutEffect, useState} from "react";
import TopMenu from "../topmenu/TopMenu";
import Constants from '../../sass/theme/_constants.scss'
import SideMenu from "../../sidemenu/SideMenu";
import LoadingIndicator from "../loadingindicator/LoadingIndicator";
import {faChevronDown, faChevronUp} from "@fortawesome/free-solid-svg-icons";
import PersonsToMergeFooter from "../../styled-components/footer/PersonsToMergeFooter";
import AppStorage, {StorageKey, useAppStorageState} from "../../util/AppStorage";
import SURFButton from "../../styled-components/buttons/SURFButton";
import {
    desktopSideMenuWidth,
    desktopTopMenuHeight,
    greyDark,
    greyLight,
    majorelle,
    majorelleLight,
    spaceCadet,
    white
} from "../../Mixins";
import {useHistory, useLocation} from "react-router-dom";
import MergeProfilePopup from "../../profile/mergeprofilespopup/MergeProfilesPopup";
import ProfileCard from "../../styled-components/footer/ProfileCard";
import {useTranslation} from "react-i18next";
import styled from "styled-components";
import {useGlobalState} from "../../util/GlobalState";
import {CollapsePersonMergeFooterEvent} from "../../util/events/Events";
import Logout, {logout} from "../../util/authUtils";

export class GlobalPageMethods {
    static setFullScreenLoading = {}
}

function Page(props) {

    const [isLoading, setIsLoading] = useState(props.showFullscreenLoader ?? false);
    const [isEnvironmentBannerVisible, setIsEnvironmentBannerVisible] = useGlobalState("isEnvironmentBannerVisible",false);
    const [isMergePersonFooterCollapsed, setIsMergePersonFooterCollapsed] = useState(false);

    GlobalPageMethods.setFullScreenLoading = setIsLoading
    const [personsToMerge] = useAppStorageState(StorageKey.PERSONS_TO_MERGE);
    const {t} = useTranslation()
    const history = useHistory();
    const location = useLocation()
    const pageIsDashboard = location.pathname === "/dashboard" || location.pathname === "/" || location.pathname.startsWith("/publicationfiles")

    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);
    const [isTopMenuVisible, setTopMenuVisible] = useGlobalState('isTopMenuVisible', true);
    const loginDisabled = (process.env.REACT_APP_DISABLE_LOGIN === "true")
    loginDisabled && Logout();

    const personsToMergeExistsAndNotEmpty = personsToMerge && personsToMerge.length > 0

    useEffect(() => {
        setTopMenuVisible(true)
    },[])

    useEffect(() => {
        window.addEventListener("CollapsePersonMergeFooterEvent", handleCollapseEvent);
        return () => window.removeEventListener("CollapsePersonMergeFooterEvent", handleCollapseEvent);
    }, []);

    const profileConjugation = () => {
        if (personsToMergeExistsAndNotEmpty && personsToMerge.length === 1) {
            return t("merge_footer.selection_singular");
        } else {
            return t("merge_footer.selection_plural");
        }
    }

    function removeProfileDataToMergeList(element) {
        const personsToNotMerge = AppStorage.get(StorageKey.PERSONS_TO_MERGE)
        const newArray = personsToNotMerge.filter(person => person.id !== element.id)
        AppStorage.set(StorageKey.PERSONS_TO_MERGE, newArray);
    }

    useEffect(() => {
        if (!(window.location.href.indexOf("publications/:id"))){
            setIsSideMenuCollapsed(true)
        } else {
            setIsSideMenuCollapsed(false)
        }
    }, [])

    function handleCollapseEvent(event) {
        setIsMergePersonFooterCollapsed(event.data.value)
    }

    function EnvironmentBanner() {
        if (process.env.REACT_APP_ENVIRONMENT_TYPE === 'dev') {
            return <div className={'environment-banner development'}>
                DEVELOPMENT ENVIRONMENT
            </div>
        } else if (process.env.REACT_APP_ENVIRONMENT_TYPE === 'test') {
            return <div className={'environment-banner test'}>
                TEST ENVIRONMENT
            </div>
        } else if (process.env.REACT_APP_ENVIRONMENT_TYPE === 'staging') {

            return <div className={'environment-banner staging'}>
                STAGING ENVIRONMENT
            </div>
        } else if (process.env.REACT_APP_ENVIRONMENT_TYPE === 'acceptance') {
            return <div className={'environment-banner acceptance'}>
                ACCEPTANCE ENVIRONMENT
            </div>
        } else {
            return ''
        }
    }

    return (
        <PageRoot
            id={props.id}
            style={props.style}
            isDashboard={true}
        >
            <SideMenu history={props.history}
                      activeMenuItem={props.activeMenuItem}
                      menuButtonColor={props.menuButtonColor ?? Constants.majorelle}
            />

            {props.showDarkGradientOverlay && <div className={"dark-gradient-overlay"}/>}
            {props.showHalfPageGradient && <div className={"half-page-gradient"}/>}

            <FixedElementWrapper>
                <EnvironmentBanner/>
                <TopMenu breadcrumbs={props.breadcrumbs}/>
                {props.fixedElements}
            </FixedElementWrapper>

            <PageContent
                isEnvironmentBannerVisible={isEnvironmentBannerVisible}
                isTopMenuVisible={isTopMenuVisible}
                isSideMenuCollapsed={isSideMenuCollapsed}
                className={"page-content-container" + (isTopMenuVisible ? " page-content-top-menu-padding" : "")}
            >
                <PageWrapper
                    isDashboard={pageIsDashboard}
                    onScroll={props.onScroll}
                    ref={props.contentRef}
                >

                    {pageIsDashboard  ?
                        (

                            <div>
                                {props.content}
                            </div>

                        )
                        :
                        (
                            <ContentWrapper
                                className={"page-content row with-margin"}
                                mergeFooterActive={personsToMergeExistsAndNotEmpty && !isMergePersonFooterCollapsed}
                            >
                                {props.content}
                            </ContentWrapper>
                        )}
                    <LoadingIndicator
                        isLoading={isLoading ?? false}
                        isFullscreen={true}/>
                </PageWrapper>
            </PageContent>

            { personsToMergeExistsAndNotEmpty && !isMergePersonFooterCollapsed &&
               <PersonsToMergeFooter
                   totalPersons={personsToMergeExistsAndNotEmpty && personsToMerge.length}
                   text={profileConjugation()}
                   stopButton={
                       <SURFButton
                           text={t("merge_footer.stop")}
                           backgroundColor={spaceCadet}
                           highlightColor={majorelleLight}
                           border={"2px solid white"}
                           onClick={() => {AppStorage.remove(StorageKey.PERSONS_TO_MERGE)}}
                       />
                   }
                   continueButton={
                       <SURFButton
                           text={t("merge_footer.continue")}
                           backgroundColor={personsToMergeExistsAndNotEmpty && personsToMerge.length > 1 ? majorelle : greyLight}
                           highlightColor={personsToMergeExistsAndNotEmpty && personsToMerge.length > 1 ? majorelleLight : undefined}
                           textColor={personsToMergeExistsAndNotEmpty && personsToMerge.length > 1 ? white : greyDark }
                           onClick={() =>personsToMergeExistsAndNotEmpty && personsToMerge.length > 1 && MergeProfilePopup.show(history)}
                       />
                   }
                   profileList={
                       <div className={"flex-row"}>
                           {personsToMergeExistsAndNotEmpty && personsToMerge.map((element, index) => {
                               return <ProfileCard key={index} name={element.name} goToProfile={() => history.push('/profile/' + element.id)} onClick={() => removeProfileDataToMergeList(element)}/>
                           })}
                       </div>
                   }
               />
            }
        </PageRoot>
    );
}

const PageRoot = styled.div`
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: row;
`;

const PageWrapper = styled.div`
    position: relative;
    width: 100%;
    height: 100%;
    flex: 1 1 0;
    -webkit-overflow-scrolling: touch;
    top: ${props => props.isDashboard ? "-15px" : "0"};
`;

const PageContent = styled.div`
    margin-left: ${props => props.isSideMenuCollapsed ? '0' : desktopSideMenuWidth};
    transition: margin 0.2s ease;
    
    margin-top: ${props => {
        let margin = 0;
        if (props.isEnvironmentBannerVisible) {
            margin += 50
        }
        if(props.isTopMenuVisible) {
            margin += 60
        }
        return `${margin}px`;
    }}
`;

const ContentWrapper = styled.div`
    padding-bottom: ${props => props.mergeFooterActive ? '100px !important' : '30px'};
`;

const FixedElementWrapper = styled.div`
    position: fixed;
    width: 100%;
    z-index: 90;
`;

export default Page;