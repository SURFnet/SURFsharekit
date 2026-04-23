import React, {useState} from "react";
import './publications.scss'
import AppStorage, {StorageKey, useAppStorageState} from "../util/AppStorage";
import Page, {GlobalPageMethods} from "../components/page/Page";
import {Navigate, useNavigate} from "react-router-dom";
import {useTranslation} from "react-i18next";
import {faPlus} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import Api from "../util/api/Api";
import ValidationError from "../util/ValidationError";
import Toaster from "../util/toaster/Toaster";
import ReactPublicationTable, {copyRepoItem} from "../components/reacttable/tables/publication/ReactPublicationTable";
import AddPublicationPopup from "./addpublicationpopup/AddPublicationPopup";
import {UserPermissions} from "../util/UserPermissions";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import RemediatePopup from "../remediate/RemediatePopup";
import useDocumentTitle from "../util/useDocumentTitle";
import {useNavigation} from "../providers/NavigationProvider";

function Publications(props) {
    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);
    const [userPermissions] = useAppStorageState(StorageKey.USER_PERMISSIONS);
    const [searchCount, setSearchCount] = useState(0);
    const navigate = useNavigation()

    useDocumentTitle('Publications')

    if (user === null) {
        return <Navigate to={'login?redirect=publications'}/>
    }

    function onClickEditPublication(itemProps) {
        goToEditPublication(navigate, itemProps)
    }

    function onTableFiltered(itemProps) {
        setSearchCount(itemProps.count)
    }

    const content = <div>
        <div className={"title-row"}>
            <h1>{t("my_publications.title")}</h1>
            <div className={"search-count"}>{searchCount}</div>
        </div>
        <div className={"actions-row"}>
            {
                UserPermissions.canRemediate(userPermissions) &&
                <div className={"remediate-button-wrapper"}>
                    <ButtonText text={t('action.remediate')}
                                buttonType={"primary"}
                                onClick={() => {
                                    RemediatePopup.show();
                                }}/>
                </div>
            }
            {
                UserPermissions.canCreateRepoItem(userPermissions) &&
                <IconButtonText faIcon={faPlus}
                                buttonText={t("my_publications.add_publication")}
                                onClick={() => {
                                    GlobalPageMethods.setFullScreenLoading(true)
                                    createAndNavigateToRepoItem(navigate, props, () => {
                                            GlobalPageMethods.setFullScreenLoading(false)
                                        },
                                        () => {
                                            GlobalPageMethods.setFullScreenLoading(false)
                                        })
                                }}/>
            }
        </div>
        <ReactPublicationTable
            enablePagination={true}
            allowSearch={true}
            allowOutsideScope={true}
            onTableFiltered={onTableFiltered}
            onClickEditPublication={onClickEditPublication}
        />
    </div>;

    return <Page id="publications"
                 breadcrumbs={[
                     {
                         path: './dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         path: './publications',
                         title: 'side_menu.my_publications'
                     }
                 ]}
                 showBackButton={true}
                 activeMenuItem={"publications"}
                 content={content}/>;
}

export function createAndNavigateToRepoItem(navigate, props, successCallback = () => {
}, errorCallback = () => {
}, isProject = false) {
    const user = AppStorage.get(StorageKey.USER);

    function onLocalFailure(error) {
        Toaster.showServerError(error)
        errorCallback(error)
    }

    function onServerFailure(error) {
        if (!error?.response) {
            Toaster.showServerError(error);
            errorCallback(error);
            return;
        }

        const status = error.response.status;
        if (status === 401) { //We're not logged, thus try to login and go back to the current url
            navigate('/login?redirect=' + window.location.pathname);
        }

        errorCallback(error)
    }

    function getUserWithGroups() {
        function personCallSuccess(response) {
            try {
                getInstitutes(response.data.groups)
            } catch (e) {
                onLocalFailure(e)
            }
        }

        const personCallConfig = {
            params: {
                'include': "groups.partOf",
                'filter[isRemoved]': "false",
                'fields[groups]': 'partOf,title,amountOfPersons,codeMatrix,userPermissions,permissions,labelNL,labelEN',
                'fields[institutes]': 'title,permissions,isRemoved,level,abbreviation,summary,type,childrenInstitutesCount'
            }
        };

        Api.jsonApiGet('persons/' + user.id, () => {
        }, personCallSuccess, onLocalFailure, onServerFailure, personCallConfig)
    }

    function getInstitutes(groups) {
        const institutes = []
        for (let i = 0; i < groups.length; i++) {
            const institute = groups[i].partOf
            if (instituteCanCreateRepoItem(institute)) {
                institutes.push(institute)
            }
        }

        if (institutes.length === 0) {
            onLocalFailure(new ValidationError("No groups with permissions to create repoItem"))
            return
        }

        const newInstitutes = []

        function institutesCallSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)

            const apiCallInstitutations = response.data
            for (let i = 0; i < apiCallInstitutations.length; i++) {
                const institute = apiCallInstitutations[i]
                if (instituteCanCreateRepoItem(institute)) {
                    newInstitutes.push(institute)
                }
            }

            showPublicationTypePopup(newInstitutes)
        }

        const institutesCallConfig = {
            params: {
                'filter[distinctTemplates]': "1",
                "filter[scope]": institutes.map(i => i.id).join(","),
                "fields[institutes]": "title,permissions,lmsEnabled"
            }
        };

        Api.jsonApiGet('institutes/',
            () => {
            },
            (response) => {
                institutesCallSuccess(response)
            },
            onLocalFailure,
            onServerFailure,
            institutesCallConfig)
    }

    function showPublicationTypePopup(institutes) {
        try {
            if (institutes.length > 0) {
                const firstInstitute = institutes[0]
                const createRepoItemPermissions = instituteCreateRepoItemPermissions(firstInstitute)
                if (institutes.length === 1 && isProject) {
                    //If there is only 1 institute when creating a new project, skip the popup
                    const createProjectPermission = createRepoItemPermissions.find(permission => permission === "canCreateProject")
                    if (createProjectPermission) {
                        createRepoItem(firstInstitute, instituteCreateRepoItemPermissionToRealType(createProjectPermission))
                    }
                } else if (institutes.length === 1 && createRepoItemPermissions.length === 1) {
                    //If there is only 1 institute with only 1 publication type option, skip the popup
                    createRepoItem(firstInstitute, instituteCreateRepoItemPermissionToRealType(createRepoItemPermissions[0]))
                } else {
                    AddPublicationPopup.show(institutes, (instituteAndType) => {
                        GlobalPageMethods.setFullScreenLoading(true)
                        createRepoItem(instituteAndType.institute, instituteAndType.selectedPublicationType)
                    }, (repoItemToCopy) => {
                        GlobalPageMethods.setFullScreenLoading(true)
                        copyRepoItem(repoItemToCopy.id, navigate, (response) => {
                            GlobalPageMethods.setFullScreenLoading(false)
                            navigate(`../publications/${response.data.id}`)
                            successCallback()
                        }, (error) => {
                            GlobalPageMethods.setFullScreenLoading(false)
                            Toaster.showServerError(error)
                        })
                    }, () => {
                        GlobalPageMethods.setFullScreenLoading(false)
                        navigate(`/dashboard`)
                    }, () => {
                        successCallback()
                    }, isProject)
                }
            } else {
                throw new ValidationError("No publication types possible in all groups")
            }
        } catch (e) {
            onLocalFailure(e)
        }
    }

    function createRepoItem(institute, repoType) {
        function validator(response) {
            const repoItemData = response.data ? response.data.data : null;
            if (!(repoItemData && repoItemData.id && repoItemData.attributes)) {
                GlobalPageMethods.setFullScreenLoading(false)
                errorCallback()
                throw new ValidationError("The received repo item data is invalid")
            }
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            const repoItemData = response.data.data
            isProject ? navigate(`../projects/${repoItemData.id}`, {isProject: true}) : navigate(`../publications/${repoItemData.id}`)
            successCallback()
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            },
            params: {
                'fields[repoItems]': ''
            }
        }

        const postData = {
            "data": {
                "type": "repoItem",
                "attributes": {
                    "repoType": repoType
                },
                "relationships": {
                    "relatedTo": {
                        "data": {
                            "type": "institute",
                            "id": institute.id
                        }
                    }
                }
            }
        };

        Api.post('repoItems', validator, onSuccess, onLocalFailure, onServerFailure, config, postData)
    }

    getUserWithGroups()
}

export function instituteCanCreateRepoItem(institute) {
    if (!institute) {
        return false;
    }
    return (institute.permissions.canCreateLearningObject === true
        || institute.permissions.canCreatePublicationRecord === true
        || institute.permissions.canCreateResearchObject === true
        || institute.permissions.canCreateDataset === true)
}

export function instituteCreateRepoItemPermissions(institute) {
    let permissions = []
    if (institute.permissions.canCreateLearningObject === true) {
        permissions.push("canCreateLearningObject")
    }
    if (institute.permissions.canCreatePublicationRecord === true) {
        permissions.push("canCreatePublicationRecord")
    }
    if (institute.permissions.canCreateResearchObject === true) {
        permissions.push("canCreateResearchObject")
    }
    if (institute.permissions.canCreateDataset === true) {
        permissions.push("canCreateDataset")
    }
    if (institute.permissions.canCreateDataset === true) {
        permissions.push("canCreateProject")
    }
    // if (institute.permissions.canRequestLmsItem === true) {
    //     permissions.push("canRequestLmsItem")
    // }
    return permissions
}

export function instituteCreateRepoItemPermissionToRealType(permission) {
    const permissionsMapped = {
        "canCreateLearningObject": "LearningObject",
        "canCreatePublicationRecord": "PublicationRecord",
        "canCreateResearchObject": "ResearchObject",
        "canCreateDataset": "Dataset",
        "canCreateProject": "Project"
    }
    return permissionsMapped[permission]
}

export function instituteCreateRepoItemPermissionToString(permission, translation) {
    const permissionsMapped = {
        "canCreateLearningObject": translation("publication.type.learning_object"),
        "canCreatePublicationRecord": translation("publication.type.publication_record"),
        "canCreateResearchObject": translation("publication.type.research_object"),
        "canCreateDataset": translation("publication.type.dataset"),
        "canCreateProject": "Project",
    }
    return permissionsMapped[permission]
}

export function goToEditPublication(navigate, itemProps) {
    navigate(`../publications/${itemProps.id}`)
}

export default Publications;
