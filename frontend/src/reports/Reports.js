import React, {useEffect, useRef, useState} from "react";
import './reports.scss'
import Page from "../components/page/Page";
import {useTranslation} from "react-i18next";
import IconButton from "../components/buttons/iconbutton/IconButton";
import {faDownload, faTrash} from "@fortawesome/free-solid-svg-icons";
import ExportPopup from "./ExportPopup";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {Navigate} from "react-router-dom";
import useDocumentTitle from "../util/useDocumentTitle";
import HorizontalTabList from "../components/horizontaltablist/HorizontalTabList";
import styled from "styled-components";
import IconExport from "../resources/icons/ic-export.svg";
import {Icon, TaskHeaderIcon} from "../dashboard/Dashboard";
import {
    ReactTable,
    ReactTableEmptyState,
    ReactTableLoadingIndicator
} from "../components/reacttable/reacttable/ReactTable";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import ReportsDashboard from "./dashboard/ReportsDashboard";
import {useNavigation} from "../providers/NavigationProvider";

const TabContainer = styled.div`
    padding-top: 50px;
`

export default function Reports(props) {
    const {t} = useTranslation()
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const userHasExtendedAccess = userRoles ? userRoles.find(c => c !== 'Student' && c !== 'Default Member') : false;
    const canViewReports = userRoles ? userRoles.find(c => c !== 'Student' && c !== 'Default Member' && c !== 'Staff') : false;
    const [tabSelectedIndex, setTabSelectedIndex] = useState(0)

    useDocumentTitle("Reports")

    const content = (
        <div className={"reports-content"}>
            <HorizontalTabList
                tabsTitles={[
                    t("report.tabs.dashboard.title"),
                    t('report.tabs.exports.title')
                ]}
                selectedIndex={tabSelectedIndex} onTabClick={setTabSelectedIndex}
            />
            <TabContainer>
                { tabSelectedIndex === 0 && <div>
                    <ReportsDashboard />
                </div>}
                { tabSelectedIndex === 1 && <div>
                    <ReportExports />
                </div>}
            </TabContainer>
        </div>
    )

    return (
        canViewReports && userHasExtendedAccess ? <Page id="reports"
                                                      activeMenuItem={"reports"}
                                                      showBackButton={true}
                                                      breadcrumbs={[
                                                          {
                                                              path: './dashboard',
                                                              title: 'side_menu.dashboard'
                                                          },
                                                          {
                                                              title: 'side_menu.reports'
                                                          }
                                                      ]}
                                                      content={content}/>
        : <Navigate to={"/forbidden"}/>
    )
}

const ReportExports = () => {
    const {t} = useTranslation();
    const [searchCount, setSearchCount] = useState(0);
    const [isLoading, setIsLoading] = useState(false);
    const [pageIndex, setPageIndex] = useState(0);
    const [exports, setExports] = useState([]);
    const navigate = useNavigation();
    const paginationCountRef = useRef();
    const cancelToken = useRef();
    const [pending, setPending] = useState(false);

    useEffect(() => {
        handleReloadData([], 0, true)
    }, []);

    function openExportPopup() {
        ExportPopup.show().finally(() => {
            handleReloadData([], 0 , true)
        })
    }

    const handleReloadData = (sortBy, pageIndex, force = false) => {
        if (!isLoading || force) {
            setPageIndex(pageIndex)
            getExports(sortBy, pageIndex)
        }
    }

    const getExports = (sortBy = [], pageIndex = 0) => {
        if (exports !== null) setExports([])

        setIsLoading(true)

        const config = {
            params: {
                'sort': '-created',
                'page[size]': 10,
                'page[number]': pageIndex + 1
            }
        }

        config.cancelToken = cancelToken.current;
        cancelToken.current = Api.jsonApiGet(`csv/exportItems`,
            () => {},
            (response) => {
                setIsLoading(false)
                setSearchCount(response.meta.totalCount);
                paginationCountRef.current = parseInt(new URLSearchParams(response.links.last).get("page[number]"))
                setExports(response.data)
            },
            (error) => {
                Toaster.showServerError(error)
            },
            (error) => {
                Toaster.showServerError(error)
                console.log(error)
                if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                    navigate('/login?redirect=' + window.location.pathname);
                }
            },
            config
        )
    }

    const columns = React.useMemo(
        () => [
            {
                Header: () => {
                    return [<span>{t("report.tabs.exports.table.status.title")}</span>]
                },
                accessor: 'status',
                disableSortBy: true,
                style: {
                    width: "15%"
                },
                Cell: cellProps => {
                    const getStatusText = () => {
                        switch (cellProps.value) {
                            case "PENDING":
                            case "IN PROGRESS":
                                return t(`report.tabs.exports.table.status.cell.IN_PROGRESS`)
                            case "FINISHED":
                                return t(`report.tabs.exports.table.status.cell.FINISHED`)
                            case "FAILED":
                            default:
                                return t(`report.tabs.exports.table.status.cell.FAILED`)
                        }
                    }

                    const getStatusColor = () => {
                        switch (cellProps.value) {
                            case "PENDING":
                            case "IN PROGRESS":
                                return '#F3BA5A'
                            case "FINISHED":
                                return '#64C3A5'
                            case "FAILED":
                            default:
                                return '#e87c5d'
                        }
                    }

                    return <div className={"status-label-wrapper"}>
                        <div className={"status-label-container"}>
                            <div className={"status-label-indicator"}
                                 style={{backgroundColor: getStatusColor()}}/>
                            <div className={"status-label-text"}>
                                { getStatusText() }
                            </div>
                        </div>
                    </div>


                },
            },
            {
                Header: () => {
                    return [<span className="border">{t("report.tabs.exports.table.repo_type.title")}</span>]
                },
                accessor: 'repoType',
                disableSortBy: true,
                style: {
                    width: "10%"
                },
                Cell: cellProps => {
                    if (!cellProps.value) {
                        return <div>{t(`repoitem.type.all`)}</div>
                    }
                    return <div>{t(`repoitem.type.${cellProps.value.toLowerCase()}`)}</div>
                },
            },
            {
                Header: () => {
                    return [<span className="border">{t("report.tabs.exports.table.institutes.title")}</span>]
                },
                accessor: 'institutes',
                disableSortBy: true,
                style: {
                    width: "15%"
                },
                Cell: cellProps => {
                    return <div>{ cellProps.value?.map(i => i.title).join(", ")}</div>
                },
            },
            {
                Header: () => {
                    return [<span className="border">{t("report.tabs.exports.table.created.title")}</span>]
                },
                accessor: 'created',
                disableSortBy: true,
                style: {
                    width: "10%"
                },
                Cell: cellProps => {
                    const date = new Date(cellProps.value)

                    const dateString = date.toLocaleDateString('nl-NL', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    })

                    return <div>{ dateString }</div>
                },
            },
            {
                Header: () => {
                    return [<span className="border">{t("report.tabs.exports.table.from.title")}</span>]
                },
                accessor: 'from',
                disableSortBy: true,
                style: {
                    width: "8%"
                },
                Cell: cellProps => {
                    const date = new Date(cellProps.value)

                    const dateString = date.toLocaleDateString('nl-NL', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    })

                    return <div>{ dateString }</div>
                },
            },
            {
                Header: () => {
                    return [<span className="border">{t("report.tabs.exports.table.until.title")}</span>]
                },
                accessor: 'until',
                disableSortBy: true,
                style: {
                    width: "8%"
                },
                Cell: cellProps => {
                    const date = new Date(cellProps.value)

                    const dateString = date.toLocaleDateString('nl-NL', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    })

                    return <div>{ dateString }</div>
                },
            },
            {
                Header: () => {
                    return [<span className="border">{t("report.tabs.exports.table.report_type.title")}</span>]
                },
                accessor: 'reportType',
                disableSortBy: true,
                style: {
                    width: "8%"
                },
                Cell: cellProps => {
                    return <div>{ t(`report.tabs.exports.table.report_type.cell.${cellProps.value}`) }</div>
                },
            },
            {
                Header: () => { return <></> },
                accessor: 'actions',
                disableSortBy: true,
                style: {
                    width: "8%"
                },
                Cell: cellProps => {
                    const id = cellProps.row.original.id
                    const actions = [
                        <button disabled={pending} onClick={() => deleteExport(id)}>
                            <FontAwesomeIcon key={`${id}-delete`} icon={faTrash} />
                        </button>
                    ];

                    const url = cellProps.row.original.url

                    if (url) {
                        actions.unshift(<a key={`${id}-download`} href={url} target="_blank"><FontAwesomeIcon icon={faDownload}/></a>)
                    }

                    return <div className={'table-row-actions'}>
                        { actions.map(a => a)}
                    </div>
                },
            }
        ]
    )

    const deleteExport = (id) => {
        setPending(true);
        Api.delete(`csv/exportItems/${id}`,
            () => {},
            () => {
                handleReloadData([], pageIndex, true)
                setPending(false);
            },
            (error) => {
                Toaster.showServerError(error)
                setPending(false);
            },
            (error) => {
                Toaster.showServerError(error)
                setPending(false);
            },
            {
                headers: {
                    "Content-Type": "application/vnd.api+json"
                },
                data: {}
            }
        )
    }

    return (
        <div>
            <div className={"reports-title-row"}>
                <div>
                    <TaskHeaderIcon width={"82px"} height={"82px"}>
                        <Icon src={IconExport} width={"26px"}/>
                    </TaskHeaderIcon>
                    <h1>{t("report.tabs.exports.title")}</h1>
                    <div className={"search-count"}>{searchCount}</div>
                </div>
                <div>
                    <IconButton className={"report-export-button"} icon={faDownload} text={t("report.export")} onClick={openExportPopup} />
                </div>
            </div>
            <p className={"description"}>
                {t("report.tabs.exports.description")}
            </p>
            <div>
                <ReactTable
                    columns={columns}
                    cellsAreLinks={false}
                    tableRows={exports}
                    isLoading={isLoading}
                    enablePagination={true}
                    pageCount={paginationCountRef.current ?? -1}
                    onReloadData={handleReloadData}
                    loadingIndicator={<ReactTableLoadingIndicator loadingText={t('report.tabs.exports.table.loading')} />}
                    emptyState={<ReactTableEmptyState title={t('report.tabs.exports.table.empty')} />}
                    separatedRows={true}
                />
            </div>
        </div>
    )
}