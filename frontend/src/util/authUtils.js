import React, {useEffect} from "react";
import { useNavigate } from 'react-router-dom';
import {StorageKey, useAppStorageState} from "./AppStorage";
import {useNavigation} from "../providers/NavigationProvider";
import Api from "./api/Api";

function Logout() {
    const navigate = useNavigation();
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
        const clearUserState = () => {
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
            navigate('/login', {replace: true});
        };

        const onValidate = () => {};
        const onSuccess = () => { clearUserState(); };
        const onLocalFailure = () => { clearUserState(); };
        const onServerFailure = () => { clearUserState(); };

        // Use Api.post and ensure cookies are sent
        Api.post(
            'logout',
            onValidate,
            onSuccess,
            onLocalFailure,
            onServerFailure,
            { withCredentials: true }
        );
    }, []);
}

export function logoutUser(setters, navigate) {
    const {
        setUser, setUserInstitute, setUserRoles, setUserPermissions,
        setCanViewTemplates, setCanViewOrganisations, setCanViewPublications,
        setCanViewProfiles, setCanViewProjects, setCanViewReports, setCanViewBin
    } = setters;

    const clearUserState = () => {
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
        navigate('/login', {replace: true});
    };

    Api.post(
        'logout',
        () => {}, // onValidate
        clearUserState, // onSuccess
        clearUserState, // onLocalFailure
        clearUserState, // onServerFailure
        { withCredentials: true }
    );
}

export default Logout;