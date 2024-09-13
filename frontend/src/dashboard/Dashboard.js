import React, {useEffect, useState} from "react";
import './dashboard.scss';
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {Redirect, useHistory, useLocation} from "react-router-dom";
import {useTranslation} from 'react-i18next';
import Page, {GlobalPageMethods} from "../components/page/Page";
import {createAndNavigateToRepoItem, goToEditPublication} from "../publications/Publications";
import ApiRequests from "../util/api/ApiRequests";
import {faChevronDown, faChevronUp, faFileInvoice, faUser} from "@fortawesome/free-solid-svg-icons";
import IconButton from "../components/buttons/iconbutton/IconButton";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import {UserPermissions} from "../util/UserPermissions";
import DashboardHeader from "../styled-components/dashboard/DashboardHeader";
import ConceptContinuation from "./components/ConceptContinuation";
import ConceptCard from "../styled-components/dashboard/ConceptCard";
import VerificationPopup from "../verification/VerificationPopup";
import IconTasks from "../resources/icons/ic-tasks.svg"
import styled from "styled-components";
import useDocumentTitle from "../util/useDocumentTitle";
import {copyRepoItem, deleteRepoItem} from "./components/DashboardPublicationTable";
import DashboardUserPublicationTable from "./components/DashboardUserPublicationTable";
import {majorelle, SURFShapeLeft, white} from "../Mixins";
import ReactTaskTable from "./components/tasktable/ReactTaskTable";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {useGlobalState} from "../util/GlobalState";
import {PublicationTableSort} from "../components/reacttable/tables/publication/ReactPublicationTable";
import DashboardFilters from "./components/filters/DashboardFilters";

function Dashboard(props) {
    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const [userPermissions] = useAppStorageState(StorageKey.USER_PERMISSIONS);
    const [userPublications, setUserPublications] = useState([]);
    const [viewCompletedTasks, setViewCompletedTasks] = useState(false);
    const [tasksCount, setTasksCount] = useState(0);
    const [filteredTaskCount, setFilteredTaskCount] = useState(null)
    const [taskFilters, setTaskFilters] = useState({})
    const [taskFilterStates, setTaskFilterStates] = useState({})
    const [completedTasksCount, setCompletedTasksCount] = useState(0);
    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);
    const history = useHistory();
    const location = useLocation();
    const userIsSiteAdminOrSupporter = userRoles ? userRoles.find(role => role === 'Supporter' || role === 'Siteadmin') : false;

    useDocumentTitle('Dashboard')

    useEffect(() => {
        if(typeof (user) !== 'undefined' && user != null) {
            getUserPublications();
        }
    }, [])

    useEffect(() => {
        if (typeof (user) !== 'undefined' && user != null) {
            ApiRequests.getExtendedPersonInformation(user, history,
                (data) => {
                    setUserRoles(data.groups.map(group => group.roleCode).filter(role => !!role && role !== 'null'))
                }, () => {}
            );
        }
    }, [])

    const filtersSet = () => {
        const appliedFilters = {};
        Object.entries(taskFilterStates).filter(([, value]) => value !== null).forEach(([key, value]) => (appliedFilters[key] = value))

        console.log(Object.entries(taskFilterStates))

        return Object.entries(appliedFilters).length > 0;
    }

    function handleRepoItemFileDownload(id) {
        GlobalPageMethods.setFullScreenLoading(true)

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            Api.downloadFileWithAccessTokenAndPopup(response.data.data.attributes.url, response.data.data.attributes.title)
        }

        function onLocalFailure(error) {
            console.log(error);
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            console.log(error);
            GlobalPageMethods.setFullScreenLoading(false)
            if (error.response.status === 403) {
                history.replace('/forbiddenfile');
            } else {
                Toaster.showServerError(error)
            }
        }

        Api.get('repoItemFiles/' + id, onValidate, onSuccess, onLocalFailure, onServerFailure);
    }


    useEffect(() => {
        if (typeof (user) !== 'undefined' && user != null) {
            if (props.match.params.type === 'publicationfiles' && props.match.params.id !== undefined && props.match.params.id !== '') {
                handleRepoItemFileDownload(props.match.params.id)
            }
        }
    }, [])

    if (user === null) {
        if (props.match.params.type && props.match.params.id) {
            return <Redirect to={'/login?redirect=' + window.location.pathname}/>
        } else if (location.search) {
            return <Redirect to={'/login' + location.search}/>
        } else {
            return <Redirect to={'/login'}/>
        }
    }

    function createRepoItem() {
        GlobalPageMethods.setFullScreenLoading(true)
        createAndNavigateToRepoItem(props,
            () => {
                GlobalPageMethods.setFullScreenLoading(false)
            },
            () => {
                GlobalPageMethods.setFullScreenLoading(false)
            })
    }


    function onClickEditPublication(itemProps) {
        goToEditPublication(props, itemProps)
    }

    function getStudentHeaderContent() {
        return (
            <StudentHeader className={"flex-row"}>
                {
                    UserPermissions.canCreateRepoItem(userPermissions) &&
                    <IconButton className={"icon-button-publications"}
                                icon={faFileInvoice}
                                text={t("dashboard.button.new_publication")}
                                onClick={() => {
                                    createRepoItem()
                                }}
                    />
                }
                <IconButton className={"icon-button-profile"}
                            icon={faFileInvoice}
                            text={t("dashboard.button.my_publications")}
                            onClick={() => {
                                history.push('/profile#owner')
                            }}
                />
            </StudentHeader>
        )
    }


    function getSiteAdminContent() {
        return (
            <SiteAdminHeader className={"flex-row"}>
                {
                    UserPermissions.canCreateRepoItem(userPermissions) &&
                    <IconButton className={"icon-button-publications"}
                                icon={faFileInvoice}
                                text={t("dashboard.button.new_publication")}
                                onClick={() => {
                                    createRepoItem()
                                }}
                    />
                }
                <IconButton className={"icon-button-publications"}
                            icon={faUser}
                            text={t("dashboard.button.manage_groups")}
                            onClick={() => {
                                history.push('/organisation#groups')
                            }}
                />
                <IconButton className={"icon-button-publications"}
                            icon={faFileInvoice}
                            text={t("dashboard.button.my_publications")}
                            onClick={() => {
                                history.push('/profile#owner')
                            }}
                />
                <IconButton className={"icon-button-profile"}
                            icon={faFileInvoice}
                            text={t("dashboard.button.draft")}
                            onClick={() => {
                                history.push({
                                    pathname: "/publications",
                                    state: {detail: [PublicationTableSort.STATUS_CONCEPT]}
                                })
                            }}
                />
            </SiteAdminHeader>
        )
    }


    function LatestConcepts() {
        if (userPublications) {
            return userPublications.slice(0, 3).map((element, index) => {
                return element.status === "Draft" &&
                    <ConceptCard key={index}
                                 props={element}
                                 status={element.status}
                                 lastEdited={element.lastEdited}
                                 title={element.title}
                                 subtitle={element.repoType}
                                 onCopyClick={(e) => {e.stopPropagation();onClickCopyRepoItem(element)}}
                                 onDeleteClick={(e) => {e.stopPropagation();onClickDeleteRepoItem(element)}}
                                 onEditClick={(e) => {e.stopPropagation();onClickEditPublication(element)}}
                />
            })
        }
    }


    function onClickCopyRepoItem(repoItem) {
        if (repoItem.permissions.canCopy) {
            VerificationPopup.show(t("publication.copy_confirmation.title"), t("publication.copy_confirmation.subtitle"), () => {
                GlobalPageMethods.setFullScreenLoading(true)
                copyRepoItem(repoItem.id, history, (responseData) => {
                    GlobalPageMethods.setFullScreenLoading(false)
                }, () => {
                    GlobalPageMethods.setFullScreenLoading(false)
                })
            })
        }
    }


    function onClickDeleteRepoItem(repoItem) {
        if (repoItem.permissions.canDelete) {
            VerificationPopup.show(t("publication.delete_popup.title"), t("publication.delete_popup.subtitle"), () => {
                GlobalPageMethods.setFullScreenLoading(true)
                deleteRepoItem(repoItem.id, history, (responseData) => {
                    GlobalPageMethods.setFullScreenLoading(false)
                    const tempUserPublications = userPublications.filter((tempRepoItem) => {
                        return tempRepoItem.id !== responseData.data.id;
                    });
                    setUserPublications(tempUserPublications);
                }, () => {
                    GlobalPageMethods.setFullScreenLoading(false)
                    Toaster.showDefaultRequestError();
                })
            })
        }
    }

    function getUserPublications() {
        const config = {
            params: {
                'filter[isRemoved]': false,
                'fields[repoItems]': 'title,lastEdited,status,isArchived',
                'filter[status]': "Draft",
                'filter[scope]': "on",
                'filter[authorID]': user.id,
                'page[size]': 3,
                'page[number]': 1,
            }
        };

        Api.jsonApiGet('repoItemSummaries', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            setUserPublications(response.data);
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

    const content =
        <>
            <DashboardHeader
                isSideMenuCollapsed={isSideMenuCollapsed}
                className={"row with-margin"}
                firstName={user.name}
                content={ userIsSiteAdminOrSupporter ? getSiteAdminContent() : getStudentHeaderContent()}
            />

            <DashboardContainer className={"row with-margin"}>
                {userIsSiteAdminOrSupporter ?
                    <>
                        <div className={"title-row secondary"}>
                            <div className={"title"}>
                                <TaskHeaderIcon>
                                    <Icon src={IconTasks}/>
                                </TaskHeaderIcon>
                                <h2> {t("dashboard.tasks.table.title")}</h2>
                                {tasksCount !== 0 && <div className={"search-count"}>
                                    { filteredTaskCount !== null && <span>{ filteredTaskCount }/</span> }
                                    {tasksCount}
                                </div>}
                            </div>
                            <div className={'filter-row'}>
                                <DashboardFilters filters={taskFilters} onFilterChange={(filters) => setTaskFilterStates(filters)}/>
                            </div>
                        </div>
                        <ReactTaskTable
                            history={history}
                            filters={taskFilterStates}
                            onTableFiltered={(metaObject) => {
                                if (metaObject && metaObject.count) {
                                    if (filtersSet()) {
                                        setFilteredTaskCount(metaObject.count)
                                    } else {
                                        setTasksCount(metaObject.count)
                                        setFilteredTaskCount(null)
                                    }
                                } else {
                                    setTasksCount(0)
                                    setFilteredTaskCount(null)
                                }
                            }}
                            enablePagination={true}
                            onFiltersLoad={(filters) => setTaskFilters(filters)}
                        />

                        <CompletedTasksTitle onClick={() => setViewCompletedTasks(!viewCompletedTasks)}>
                            <FontAwesomeIcon icon={ viewCompletedTasks ? faChevronUp : faChevronDown}  style={{cursor: "pointer"}}/>
                            <p>{viewCompletedTasks ? t("dashboard.hide_tasks_title") : t("dashboard.show_tasks_title")} ({completedTasksCount})</p>
                        </CompletedTasksTitle>
                        <CompletedTaskContainer viewCompletedTasks={viewCompletedTasks}>
                            <ReactTaskTable
                                isDone={true}
                                history={history}
                                onTableFiltered={(metaObject) => {
                                    if (metaObject && metaObject.count) {
                                        setCompletedTasksCount(metaObject.count)
                                    } else {
                                        setCompletedTasksCount(0)
                                    }
                                }}
                                enablePagination={true}
                            />
                        </CompletedTaskContainer>
                    </>

                    :

                    <> { userPublications && userPublications.length !== 0 &&
                            <>
                                <ConceptContinuation
                                    content={LatestConcepts()}
                                />
                                <StudentDashboardDivider/>
                            </>
                        }

                        <DashboardUserPublicationTable
                            userId={user.id}
                            history={history}
                            onClickEditPublication={() => onClickEditPublication}
                        />
                    </>

                }
            </DashboardContainer>
        </>;

    return <Page id="dashboard"
                 history={history}
                 content={content}
                 menuButtonColor='white'
                 showDarkGradientOverlay={false}
                 showHalfPageGradient={false}
                 activeMenuItem={"dashboard"}
                 showNavigationBarGradient={false}/>;
}

const StudentHeader = styled.div `
    gap: 10px;
`;

const StudentDashboardDivider = styled.hr `
    width: 100%;
    border-color: black;
    border: 0.7px solid #D2D2D2;
    margin-top: 37px;
`;

const SiteAdminHeader = styled.div `
    gap: 10px;
`;

const CompletedTasksTitle = styled.div `
    display: flex; 
    align-items: center;
    gap: 9px;
    color: ${majorelle};
    font-size: 12px;
    cursor: pointer;
    width: fit-content;
`;

const CompletedTaskContainer = styled.div `
    visibility: ${props => props.viewCompletedTasks === true ? 'visible' : 'hidden'}
`;

const DashboardContainer = styled.div `
    padding-top: 30px; 
    padding-bottom: 30px;
`;

export const TaskHeaderIcon = styled.div`
    background: ${white};
    ${SURFShapeLeft};
    width: 47px;
    height: 47px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    margin-right: 22px;
`;

export const Icon = styled.img`
    width: 18px;
`;

export default Dashboard;