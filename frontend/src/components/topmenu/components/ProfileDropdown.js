import React, {useEffect, useState} from "react";
import styled from "styled-components";
import {openSans, white} from "../../../Mixins";
import ChevronDown from "../../../resources/icons/ic-chevron-down.svg";
import {useTranslation} from "react-i18next";
import {StorageKey, useAppStorageState} from "../../../util/AppStorage";
import ModalButton, {
    MODAL_ALIGNMENT as DropdownListShape,
    MODAL_ALIGNMENT, MODAL_VERTICAL_ALIGNMENT
} from "../../../styled-components/buttons/ModalButton";
import BasicDropdownList from "../../../styled-components/dropdowns/dropdown-lists/BasicDropdownList";
import UserIcon from '../../../resources/icons/ic-user.svg';
import UserIconWhite from '../../../resources/icons/ic-user-white.svg';
import InfoIcon from '../../../resources/icons/ic-info.svg';
import SignOutIcon from '../../../resources/icons/ic-sign-out.svg';
import DropdownItemWithIcon from "../../../styled-components/dropdowns/dropdown-items/DropdownItemWithIcon";
import {useHistory, useLocation} from "react-router-dom";
import { redirect } from "react-router-dom";

function ProfileDropdown() {

    // states to reset on logout
    const [userPermissions, setUserPermissions] = useAppStorageState(StorageKey.USER_PERMISSIONS);
    const [canViewPublications, setCanViewPublications] = useAppStorageState(StorageKey.USER_CAN_VIEW_PUBLICATIONS);
    const [canViewProfiles, setCanViewProfiles] = useAppStorageState(StorageKey.USER_CAN_VIEW_PROFILES);
    const [canViewProjects, setCanViewProjects] = useAppStorageState(StorageKey.USER_CAN_VIEW_PROJECTS);
    const [canViewReports, setCanViewReports] = useAppStorageState(StorageKey.USER_CAN_VIEW_REPORTS);
    const [canViewOrganisations, setCanViewOrganisations] = useAppStorageState(StorageKey.USER_CAN_VIEW_ORGANISATION);
    const [canViewTemplates, setCanViewTemplates] = useAppStorageState(StorageKey.USER_CAN_VIEW_TEMPLATES);
    const [canViewBin, setCanViewBin] = useAppStorageState(StorageKey.USER_CAN_VIEW_BIN);
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const [userInstitute, setUserInstitute] = useAppStorageState(StorageKey.USER_INSTITUTE);
    const [user, setUser] = useAppStorageState(StorageKey.USER);

    const [isExpanded, setExpanded] = useState(false);
    const {t, i18n} = useTranslation();
    const history = useHistory();

    const dropdownItems = [
        new DropdownItemWithIcon(UserIcon, t("top_menu.profile_dropdown.my_profile"),() => window.location.href = "/profile"),
        new DropdownItemWithIcon(InfoIcon, t("top_menu.profile_dropdown.help"), () => window.open('https://servicedesk.surf.nl/wiki/display/WIKI/SURFsharekit', '__blank')),
        new DropdownItemWithIcon(SignOutIcon, t("top_menu.profile_dropdown.sign_out"),  () => logout()),
    ]

    return (

        <ModalButton
            onModalVisibilityChanged={(isModalVisible) => setExpanded(isModalVisible)}
            modalHorizontalAlignment={MODAL_ALIGNMENT.RIGHT}
            modalVerticalAlignment={MODAL_VERTICAL_ALIGNMENT.BOTTOM}
            modal={
                <BasicDropdownList
                    listShape={DropdownListShape.RIGHT}
                    itemHeight={'50px'}
                    listWidth={'207px'}
                    dropdownItems={dropdownItems}
                />
            }
            button={
                <ProfileDropdownRoot>
                    <ProfilePicture src={UserIconWhite}/>
                    <Username>{user.name}</Username>
                    <Icon dropdownIsOpen={isExpanded} src={ChevronDown}/>
                </ProfileDropdownRoot>
            }
        />
    )

    function logout() {
        setUser(null);
        setUserInstitute(null);
        setUserRoles(null);
        setUserPermissions(null);
        setCanViewTemplates(null);
        setCanViewOrganisations(null);
        setCanViewPublications(null)
        setCanViewProfiles(null);
        setCanViewProjects(null);
        setCanViewReports(null);
        setCanViewBin(null);
        history.replace('/login')
    }
}

const ProfileDropdownRoot = styled.div`
    height: 100%;
    display: flex;
    flex-direction: row;
    gap: 7px;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    user-select: none;
`;

const Username = styled.div`
    ${openSans};
    font-size: 12px;
    line-height: 16px;
    color: ${white};
`;

const ProfilePicture = styled.img`
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid ${white};
    object-fit: contain;
    object-position: 0 4px;
`;

const Icon = styled.img`
    transform: ${props => props.dropdownIsOpen ? 'rotate(180deg)' : 'none'};
    -webkit-user-drag: none;
    -khtml-user-drag: none;
    -moz-user-drag: none;
    -o-user-drag: none;
    user-drag: none;
`;

export default ProfileDropdown;