import React, {useState} from 'react';
import {UserPermissions} from "../util/UserPermissions";
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import {faPlus} from "@fortawesome/free-solid-svg-icons";
import PersonTable from "../components/reacttable/tables/person/PersonTable";
import {useTranslation} from "react-i18next";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {Redirect} from "react-router-dom";
import Page, {GlobalPageMethods} from "../components/page/Page";
import "./projects.scss"
import ProjectTable from "../components/reacttable/tables/project/ProjectTable";
import {createAndNavigateToRepoItem} from "../publications/Publications";

function Projects(props) {
    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const userHasExtendedAccess = userRoles ? userRoles.find(c => c !== 'Student' && c !== 'Default Member') : false;
    const [userPermissions] = useAppStorageState(StorageKey.USER_PERMISSIONS);
    const [searchCount, setSearchCount] = useState(0);

    if (user === null || !userHasExtendedAccess) {
        return <Redirect to={'login?redirect=profiles'}/>
    }

    function onTableFiltered(itemProps) {
        setSearchCount(itemProps.count)
    }

    const content = <div>
        <div className={"title-row"}>
            <h1>{t("projects.title")}</h1>
            <div className={"search-count"}>{searchCount}</div>
        </div>
        <div className={"actions-row"}>
            {
                UserPermissions.canCreateRepoItem(userPermissions) &&
                <IconButtonText faIcon={faPlus}
                                buttonText={t("projects.add_project")}
                                onClick={() => {
                                    GlobalPageMethods.setFullScreenLoading(true)
                                    createAndNavigateToRepoItem(props, () => {
                                            GlobalPageMethods.setFullScreenLoading(false)
                                        },
                                        () => {
                                            GlobalPageMethods.setFullScreenLoading(false)
                                        }, true)
                                }}/>
            }
        </div>
        <ProjectTable
            props={props}
            history={props.history}
            onTableFiltered={onTableFiltered}
            hideDelete={true}
            enablePagination={true}
            allowSearch={true}
            allowOutsideScope={true}
            onClickEditProject={onClickEditProject}
        />
    </div>;

    return <Page id="projects"
                 history={props.history}
                 breadcrumbs={[
                     {
                         path: './dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         path: './projects',
                         title: 'side_menu.projects'
                     }
                 ]}
                 showBackButton={true}
                 activeMenuItem={"projects"}
                 content={content}/>;

    function onClickEditProject(itemProps) {
        props.history.push(`../projects/${itemProps.id}`, {isProject: true})
    }
}

export default Projects