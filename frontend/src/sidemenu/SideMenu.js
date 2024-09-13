import React, {useEffect, useState} from "react";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import ApiRequests from "../util/api/ApiRequests";
import {useTranslation} from "react-i18next";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {
    faBuilding,
    faChartPie,
    faFileInvoice,
    faHome, faProjectDiagram,
    faTools,
    faTrash,
    faUsers
} from "@fortawesome/free-solid-svg-icons";
import collapseLeft from "../resources/icons/collapse-left.svg";
import collapseRight from "../resources/icons/collapse-right.svg";
import Toaster from "../util/toaster/Toaster";
import {HelperFunctions} from "../util/HelperFunctions";
import {Link, NavLink, useHistory, useLocation} from "react-router-dom";
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import styled from "styled-components";
import {useGlobalState} from "../util/GlobalState";
import {
    cultured,
    desktopSideMenuWidth, desktopTopMenuHeight, majorelle, majorelleLight,
    mobileTabletMaxWidth, openSans, openSansBold, spaceCadet,
    SURFShapeLeft,
    white
} from "../Mixins";


function SideMenu(props) {
    const [user, setUser] = useAppStorageState(StorageKey.USER);
    const [userPermissions, setUserPermissions] = useAppStorageState(StorageKey.USER_PERMISSIONS);
    const [canViewPublications, setCanViewPublications] = useAppStorageState(StorageKey.USER_CAN_VIEW_PUBLICATIONS);
    const [canViewProfiles, setCanViewProfiles] = useAppStorageState(StorageKey.USER_CAN_VIEW_PROFILES);
    const [canViewProjects, setCanViewProjects] = useAppStorageState(StorageKey.USER_CAN_VIEW_PROJECTS);
    const [canViewReports, setCanViewReports] = useAppStorageState(StorageKey.USER_CAN_VIEW_REPORTS);
    const [canViewOrganisations, setCanViewOrganisations] = useAppStorageState(StorageKey.USER_CAN_VIEW_ORGANISATION);
    const [canViewTemplates, setCanViewTemplates] = useAppStorageState(StorageKey.USER_CAN_VIEW_TEMPLATES);
    const [canViewBin, setCanViewBin] = useAppStorageState(StorageKey.USER_CAN_VIEW_BIN);
    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const [userInstitute, setUserInstitute] = useAppStorageState(StorageKey.USER_INSTITUTE);
    const history = useHistory()
    const [defaultInstituteImageUrl, setDefaultInstituteImageUrl] = useState((userInstitute?.image?.url) ?? require('../resources/images/surf-sharekit-logo-new.svg'))

    const [isEnvironmentBannerVisible, setIsEnvironmentBannerVisible] = useGlobalState("isEnvironmentBannerVisible", false);
    const {t} = useTranslation();
    const location = useLocation();


    useEffect(() => {
        if (user) {
            ApiRequests.getExtendedPersonInformation(user, history,
                (personInformationData) => {
                    setUserRoles(personInformationData.groups.map(group => group.roleCode).filter(roleCode => !!roleCode && roleCode !== 'null'))
                    const permissions = personInformationData.groups.map((group) => {
                        return group.codeMatrix
                    })
                    setSideMenuPermissions(permissions)
                    setUserPermissions(permissions)
                    setSideMenuLogoAndUserInstitute(personInformationData);
                }, () => {
                    Toaster.showDefaultRequestError()
                });
        } else {
            setSideMenuPermissions(userPermissions)
        }
    }, [user]);

    useEffect(() => {
        window.addEventListener('resize', handleWindowResizeEvent)
        return () => window.removeEventListener('resize', handleWindowResizeEvent);
    }, [])

    if (!user) {
        return null;
    }

    function handleWindowResizeEvent() {
        let newWidth = window.innerWidth;
        if (newWidth <= mobileTabletMaxWidth && !isSideMenuCollapsed) {
            setIsSideMenuCollapsed(true)
        }
    }

    let navigationItems = [
        {
            id: "dashboard",
            title: t('side_menu.dashboard'),
            link: '/dashboard',
            icon: faHome,
            canView: true
        },
        {
            id: "publications",
            title: t('side_menu.my_publications'),
            link: '/publications',
            icon: faFileInvoice,
            canView: canViewPublications
        },
        {
            id: "profiles",
            title: t('side_menu.profiles'),
            link: '/profiles',
            icon: faUsers,
            canView: canViewProfiles
        },
        {
            id: "organisation",
            title: t('side_menu.organisation'),
            link: '/organisation',
            icon: faBuilding,
            canView: canViewOrganisations
        },
        {
            id: "templates",
            title: t('side_menu.templates'),
            link: '/templates',
            icon: faTools,
            canView: canViewTemplates
        },
        {
            id: "projects",
            title: t('side_menu.projects'),
            link: '/projects',
            icon: faProjectDiagram,
            canView: canViewProjects
        },
        {
            id: "reports",
            title: t('side_menu.reports'),
            link: '/reports',
            icon: faChartPie,
            canView: canViewReports
        }
    ];

    function MenuItem(menuItemProps) {
        return (
            <MenuItemRoot
                to={menuItemProps.link}
                isActive={menuItemProps.isActive}
                disableIndicator={menuItemProps.disableIndicator}
            >
                {!menuItemProps.disableIndicator &&
                    <SelectionIndicator isVisible={menuItemProps.isActive || props.disableIndicator}/>}
                {menuItemProps.icon && <MenuItemIcon icon={menuItemProps.icon}/>}
                <MenuItemTitle>{menuItemProps.title}</MenuItemTitle>
            </MenuItemRoot>
        )
    }

    function TextPageItem(menuItemProps) {
        return (
            <MenuItemRoot
                to={menuItemProps.link}
                isActive={menuItemProps.isActive}
            >
                {menuItemProps.icon && <MenuItemIcon icon={menuItemProps.icon}/>}
                <MenuItemTitle>{menuItemProps.title}</MenuItemTitle>
            </MenuItemRoot>
        )
    }

    return (
        <SideMenuRoot isEnvironmentBannerVisible={isEnvironmentBannerVisible} id="side-menu"
                      isCollapsed={isSideMenuCollapsed}>

            <CollapseMenuButton isSideMenuCollapsed={isSideMenuCollapsed}>
                <IconButtonText
                    icon={isSideMenuCollapsed ? collapseRight : collapseLeft}
                    onClick={() => {
                        setIsSideMenuCollapsed(!isSideMenuCollapsed)
                    }}
                />
            </CollapseMenuButton>
            <MenuContentWrapper>
                <MenuNavigation>
                    <a href={"/dashboard"}>
                        <SSKLogo
                            alt="Surf"
                            src={defaultInstituteImageUrl}
                        />
                    </a>

                    <MenuSection>
                        {
                            navigationItems.map((menuItem) =>
                                menuItem.canView &&
                                <MenuItem key={menuItem.id}
                                          title={menuItem.title}
                                          icon={menuItem.icon}
                                          isActive={props.activeMenuItem === menuItem.id}
                                          link={menuItem.link}
                                />
                            )
                        }
                    </MenuSection>

                    <MenuSectionSingle className="menu-section single-item">
                        <MenuItem
                            link={"/trashcan"}
                            isActive={location.pathname === '/trashcan'}
                            title={t('side_menu.trash_can')}
                            icon={faTrash}
                        />
                    </MenuSectionSingle>
                </MenuNavigation>

                <MenuTextPages>
                    <MenuItem
                        link={"/privacy"}
                        title={t('side_menu.privacy')}
                        isActive={location.pathname === '/privacy'}
                        disableIndicator={true}
                    />
                    {/*<MenuItem*/}
                    {/*    link={"/cookies"}*/}
                    {/*    title={t('side_menu.cookies')}*/}
                    {/*    isActive={location.pathname === '/cookies'}*/}
                    {/*    disableIndicator={true}*/}
                    {/*/>*/}
                </MenuTextPages>
            </MenuContentWrapper>
        </SideMenuRoot>
    );

    function setSideMenuPermissions(permissions) {
        let canViewOrganisationTemp = false;
        let canViewPublicationsTemp = false;
        let canViewProfilesTemp = false;
        let canViewProjectsTemp = false;
        let canViewReportsTemp = false;
        let canViewTemplatesTemp = false;
        let canViewBinTemp = false;
        if (permissions && permissions.length > 0) {
            permissions.forEach((codeMatrix) => {
                if (codeMatrix.FRONTEND_PUBLICATIONS.VIEW.isSet) {
                    canViewPublicationsTemp = true
                }
                if (codeMatrix.FRONTEND_INSTITUTES.VIEW.isSet) {
                    canViewOrganisationTemp = true
                }
                if (codeMatrix.FRONTEND_PROFILES.VIEW.isSet) {
                    canViewProfilesTemp = true
                }
                if (codeMatrix.FRONTEND_PROJECTS.VIEW.isSet) {
                    canViewProjectsTemp = true
                }
                if (codeMatrix.FRONTEND_REPORTS.VIEW.isSet) {
                    canViewReportsTemp = true
                }
                if (codeMatrix.FRONTEND_TEMPLATES.VIEW.isSet) {
                    canViewTemplatesTemp = true
                }
                if (codeMatrix.FRONTEND_BIN.VIEW.isSet) {
                    canViewBinTemp = true
                }
            })
        }
        setCanViewPublications(canViewPublicationsTemp)
        setCanViewOrganisations(canViewOrganisationTemp)
        setCanViewProfiles(canViewProfilesTemp)
        setCanViewProjects(canViewProjectsTemp)
        setCanViewReports(canViewReportsTemp)
        setCanViewTemplates(canViewTemplatesTemp)
        setCanViewBin(canViewBinTemp)
    }

    function setSideMenuLogoAndUserInstitute(personInformationData) {
        const defaultInstitute = HelperFunctions.getMemberDefaultInstitute(personInformationData)
        if (defaultInstitute) {
            setUserInstitute(defaultInstitute)
            const newDefaultInstituteImageUrl = defaultInstitute.image?.url
            if (newDefaultInstituteImageUrl) {
                setDefaultInstituteImageUrl(newDefaultInstituteImageUrl)
            }
        }
    }
}

const SideMenuRoot = styled.div`
    ${SURFShapeLeft};
    position: fixed;
    top: ${props => props.isEnvironmentBannerVisible ? "50px" : "0"};
    background: ${white};
    z-index: 100;
    height: 100%;
    max-width: ${props => props.isCollapsed ? 0 : desktopSideMenuWidth};
    width: ${props => props.isCollapsed ? '0' : '100%'};
    padding: ${props => props.isCollapsed ? '50px 0 30px 0' : '50px 30px 30px 40px'};
    display: flex;
    flex-direction: column;
    align-items: stretch;
    width: ${props => props.isCollapsed ? '0' : '100%'};
    transition: all 0.2s ease;
    transition-property: width, max-width, padding;
`;

const SSKLogo = styled.img`
    max-width: 180px;
    max-height: 85px;
    margin-left: auto;
    margin-right: auto;
    cursor: pointer;
`;

const MenuSection = styled.div`
    background: ${cultured};
    ${SURFShapeLeft};
    flex: 0 0 auto;
    padding: 10px 0 10px 0;
    margin-top: 65px;
`;

const MenuItemTitle = styled.div``;

const SelectionIndicator = styled.div`
    background: ${majorelle};
    width: 6px;
    height: 45px;
    border-radius: 10px;
    margin-right: 19px;
    visibility: ${props => props.isVisible ? "visible" : "hidden"};
`;

const MenuItemRoot = styled(Link)`
    display: flex;
    flex-direction: row;
    align-items: center;
    cursor: pointer;
    color: ${props => props.isActive ? majorelle : (props.disableIndicator ? 'gray' : 'spaceCadet')} !important;
    ${props => props.isActive ? openSansBold : openSans};
    font-size: ${props => props.disableIndicator === true ? "12px" : "14px"};

    &:hover {
        color: ${majorelleLight} !important;
    }
`;

const MenuSectionSingle = styled.div`
    background: ${cultured};
    ${SURFShapeLeft};
    padding: 10px 0 10px 0;
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    flex: 1 0 60px;
    max-height: 70px;
`;

const MenuItemIcon = styled(FontAwesomeIcon)`
    min-width: 20px;
    margin-right: 15px;
`;

const CollapseMenuButton = styled.div`
    position: absolute;
    left: ${props => props.isSideMenuCollapsed ? '20px' : `calc(${desktopSideMenuWidth} - 20px)`};
    top: 12.75px;
    z-index: 105;
`;

const MenuContentWrapper = styled.div`
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
`;

const MenuTextPages = styled.div`
    height: 100px;
    width: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-top: 50px;
    margin-bottom: 50px;
`;

const MenuNavigation = styled.div`
`;


export default SideMenu;