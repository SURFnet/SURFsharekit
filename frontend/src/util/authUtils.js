import React, {useEffect} from "react";
import { useHistory } from 'react-router-dom';
import {StorageKey, useAppStorageState} from "./AppStorage";

function Logout() {
    const history = useHistory();
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

    useEffect(() => {
        setUser(null);
        setUserInstitute(null);
        setUserRoles(null);
        setUserPermissions(null);
        setCanViewTemplates(null);
        setCanViewOrganisations(null);
        setCanViewPublications(null);
        setCanViewProfiles(null);
        setCanViewProjects(null);
        setCanViewReports(null);
        setCanViewBin(null);
        history.replace('/login');
    }, []);
}

export default Logout;