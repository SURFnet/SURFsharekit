import React, {useEffect, useState} from "react";
import './sidemenu.scss'
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import ApiRequests from "../util/api/ApiRequests";
import {useTranslation} from "react-i18next";
import Constants from "../sass/theme/_constants.scss";
import {ProfileBanner} from "../components/profilebanner/ProfileBanner";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {
    faBuilding,
    faChartPie,
    faFileInvoice,
    faHome,
    faInfoCircle,
    faPowerOff,
    faTools,
    faTrash, faUsers
} from "@fortawesome/free-solid-svg-icons";
import Toaster from "../util/toaster/Toaster";
import {version} from "../appversion.json"
import {HelperFunctions} from "../util/HelperFunctions";
import {useHistory} from "react-router-dom";

function SideMenu(props) {
    const [user, setUser] = useAppStorageState(StorageKey.USER);
    const [userPermissions, setUserPermissions] = useAppStorageState(StorageKey.USER_PERMISSIONS);
    const [canViewOrganisation, setCanViewOrganisation] = useAppStorageState(StorageKey.USER_CAN_VIEW_ORGANISATION);
    const [canViewTemplates, setCanViewTemplates] = useAppStorageState(StorageKey.USER_CAN_VIEW_TEMPLATES);
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const [userInstitute, setUserInstitute] = useAppStorageState(StorageKey.USER_INSTITUTE);
    const canViewPersons = true
    const history = useHistory()
    const [defaultInstituteImageUrl, setDefaultInstituteImageUrl] = useState((userInstitute?.image?.url) ?? require('../resources/images/surf-sharekit-logo.png'))
    const userHasExtendedAccess = userRoles ? userRoles.find(c => c !== 'Student' && c !== 'Default Member') : false;
    const canViewReports = userRoles ? userRoles.find(c => c !== 'Student' && c !== 'Default Member' && c !== 'Staff') : false;
    const {t} = useTranslation();

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

    if (!user) {
        return null;
    }

    const navigationItems = [
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
            canView: true
        },
        {
            id: "organisation",
            title: t('side_menu.organisation'),
            link: '/organisation',
            icon: faBuilding,
            canView: canViewOrganisation && userHasExtendedAccess
        },
        {
            id: "profiles",
            title: t('side_menu.profiles'),
            link: '/profiles',
            icon: faUsers,
            canView: canViewPersons && userHasExtendedAccess
        },
        {
            id: "templates",
            title: t('side_menu.templates'),
            link: '/templates',
            icon: faTools,
            canView: canViewTemplates && userHasExtendedAccess
        },
        {
            id: "reports",
            title: t('side_menu.reports'),
            link: '/reports',
            icon: faChartPie,
            canView: canViewReports && userHasExtendedAccess
        }
    ];

    const secondaryNavigationItems = [
        {
            id: "trashcan",
            title: t('side_menu.trash_can'),
            link: '/trashcan',
            icon: faTrash,
            canView: true
        }
    ];

    function MenuItem(menuItemProps) {
        return <div className={'menu-item ' + (menuItemProps.isActive && 'active')} onClick={menuItemProps.onClick}>
            <div className={"bar " + (!menuItemProps.isActive && 'hidden')}/>
            {menuItemProps.icon && <FontAwesomeIcon icon={menuItemProps.icon}/>}
            <div className="title">{menuItemProps.title}</div>
        </div>
    }

    return (
        <>
            <div id="side-menu-overlay" className={props.showOnMobile ? "" : "hidden"} onClick={props.toggleMenu}/>
            <div id="side-menu" className={!props.showOnMobile ? "hidden-on-mobile-tablet" : ""}>
                <img alt="Surf" id="logo" src={defaultInstituteImageUrl} onClick={()=>{navigateTo("/dashboard")}}/>
                <div className="menu-section navigation">
                    {
                        navigationItems.map((menuItem) =>
                            menuItem.canView &&
                            <MenuItem key={menuItem.id}
                                      title={menuItem.title}
                                      icon={menuItem.icon}
                                      isActive={props.activeMenuItem === menuItem.id}
                                      onClick={() => navigateTo(menuItem.link)}/>
                        )
                    }
                </div>
                <div className="menu-section single-item">
                    {
                        secondaryNavigationItems.map((menuItem) =>
                            menuItem.canView &&
                            <MenuItem key={menuItem.id}
                                      title={menuItem.title}
                                      icon={menuItem.icon}
                                      isActive={props.activeMenuItem === menuItem.id}
                                      onClick={() => navigateTo(menuItem.link)}/>
                        )
                    }
                </div>
                <div className="flex-grow"/>

                <div className="menu-section single-item profile">
                    <div className={'menu-item ' + (props.isActive && 'active')} onClick={() => navigateTo('/profile')}>
                        <ProfileBanner id={1}
                                       imageUrl={undefined}
                                       name={user.name}/>
                    </div>
                </div>

                <div className="menu-section single-item logout">
                    <MenuItem isActive={false}
                              title={t('side_menu.logout')}
                              icon={faPowerOff}
                              onClick={() => logout()}/>
                </div>

                <div className="menu-section single-item help">
                    <MenuItem isActive={false}
                              title={t('side_menu.help')}
                              icon={faInfoCircle}
                              onClick={() => window.open('https://wiki.surfnet.nl/display/SSK/SURFsharekit', '__blank')}/>
                </div>
                <div className={'app-version'}>{version}</div>
            </div>
            <i className="fas fa-bars menu-button hidden-on-desktop"
               style={{color: props.showOnMobile ? Constants.majorelle : props.menuButtonColor}}
               onClick={props.toggleMenu}/>
        </>
    );

    function navigateTo(link) {
        if (link) {
            props.history.push(link);
        }
    }

    function logout() {
        setUser(null);
        setUserInstitute(null);
        setUserRoles(null);
        setUserPermissions(null);
        setCanViewTemplates(null);
        setCanViewOrganisation(null);
        history.replace('/login')
    }

    function setSideMenuPermissions(permissions) {
        let canViewOrganisationTemp = false;
        let canViewTemplatesTemp = false;
        if (permissions && permissions.length > 0) {
            permissions.forEach((codeMatrix) => {
                const canViewInstituteSameLevel = codeMatrix.INSTITUTE_SAMELEVEL.VIEW.isSet
                const canViewInstituteLowerLevel = codeMatrix.INSTITUTE_LOWERLEVEL.VIEW.isSet
                if (!canViewOrganisationTemp && (canViewInstituteSameLevel || canViewInstituteLowerLevel)) {
                    canViewOrganisationTemp = true
                }
                const canViewTemplates = codeMatrix.TEMPLATE_TEMPLATE.VIEW.isSet
                if (canViewTemplates) {
                    canViewTemplatesTemp = true
                }
            })
        }
        setCanViewOrganisation(canViewOrganisationTemp)
        setCanViewTemplates(canViewTemplatesTemp)
    }

    function setSideMenuLogoAndUserInstitute(personInformationData) {
        const defaultInstitute = HelperFunctions.getMemberDefaultInstitute(personInformationData)
        if(defaultInstitute) {
            setUserInstitute(defaultInstitute)
            const newDefaultInstituteImageUrl = defaultInstitute.image?.url
            if(newDefaultInstituteImageUrl) {
                setDefaultInstituteImageUrl(newDefaultInstituteImageUrl)
            }
        }
    }
}


export default SideMenu;