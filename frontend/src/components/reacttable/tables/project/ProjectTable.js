import React, {useEffect, useRef, useState} from "react";
import '../../../field/formfield.scss'
import {useNavigate} from "react-router-dom";
import Api from "../../../../util/api/Api";
import Toaster from "../../../../util/toaster/Toaster";
import {StorageKey, useAppStorageState} from "../../../../util/AppStorage";
import {HelperFunctions} from "../../../../util/HelperFunctions";
import {useTranslation} from "react-i18next";
import {
    ReactTable,
    ReactTableEmptyState,
    ReactTableLoadingIndicator,
    ReactTableSortIcon
} from "../../reacttable/ReactTable";
import {ReactTableHelper} from "../../../../util/ReactTableHelper";
import {ReactStatusTableCell} from "../../cells/ReactStatusTableCell";
import RepoItemHelper from "../../../../util/RepoItemHelper";
import VerificationPopup from "../../../../verification/VerificationPopup";
import {GlobalPageMethods} from "../../../page/Page";
import {ReactTableSearchInput} from "../../filterrow/ReacTableFilterItems";
import {copyRepoItem, deleteRepoItem} from "../publication/ReactPublicationTable";
import {useNavigation} from "../../../../providers/NavigationProvider";

function ProjectTable(props) {
    const [user] = useAppStorageState(StorageKey.USER);
    const [projects, setProjects] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [searchOutOfScope, setSearchOutOfScope] = useState(props.searchOutOfScope ?? false);
    const [query, setQuery] = useState('');
    const debouncedQueryChange = HelperFunctions.debounce(setQuery)
    const [currentSortBy, setCurrentSortBy] = useState([]);
    const navigate = useNavigation()
    const {t} = useTranslation();
    const paginationCountRef = useRef();
    const tableRows = projects;
    const cancelToken = useRef();
    const columns = React.useMemo(
        () => [
            {
                Header: '',
                accessor: 'icon',
                disableSortBy: true,
                style: {
                    width: "45px"
                },
                Cell: () => {
                    return (
                        <div className={"document-icon-cell"}>
                            <i className="fas fa-file-invoice document-icon"/>
                        </div>
                    )
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('projects.table.title')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'title'}/>]
                },
                accessor: 'title',
                className: 'bold-text'
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('projects.table.organisation')}</span>]
                },
                accessor: 'extra.organisations',
                disableSortBy: true,
                style: {
                    width: "18%"
                },
                Cell: (tableInfo) => {
                    const list = Array.isArray(tableInfo.cell.value) ? tableInfo.cell.value : [tableInfo.cell.value]
                    const institute = tableInfo.row.original.instituteName
                    const publisher = tableInfo.row.original.extra.publisher

                    let finalList = [];

                    if (institute) {
                        list.unshift(institute)
                    }

                    if (publisher) {
                        if (Array.isArray(publisher)) {
                            // Filter out the institute if given
                            finalList.push(...publisher.filter(pub => pub !== institute));
                        } else {
                            if (!institute || !publisher.includes(institute)) {
                                finalList.push(publisher);
                            }
                        }
                    }

                    list.forEach(item => {
                        if (finalList.indexOf(item) === -1) {
                            finalList.push(item);
                        }
                    });

                    return <div>{ReactTableHelper.concatenateCellValue(finalList)}</div>
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('projects.table.participants')}</span>]
                },
                accessor: 'extra.authors',
                disableSortBy: true,
                style: {
                    width: "18%"
                },
                Cell: (tableInfo) => {
                    return <div>{ReactTableHelper.concatenateCellValue(tableInfo.cell.value)}</div>
                },
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('projects.table.status')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'status'}/>]
                },
                accessor: 'status',
                style: {
                    width: "15%"
                },
                Cell: cellProps => {
                    return <ReactStatusTableCell props={cellProps.row.original}/>
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('projects.table.last_edited')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'lastEdited'}/>]
                },
                accessor: 'lastEdited',
                style: {
                    width: "12%"
                },
                Cell: cellProps => {
                    return RepoItemHelper.getLastEditedDate(cellProps.row.original.lastEdited)
                }
            },
            {
                Header: '',
                accessor: 'action-items',
                disableSortBy: true,
                disableLink: true,
                style: {
                    width: "100px"
                },
                Cell: (cellProps) => {
                    return (
                        <div className="cell action-cell">
                            {
                                cellProps.row.original.permissions.canCopy &&
                                <i className="fas fa-copy copy-icon" onClick={(e) => {
                                    e.stopPropagation()
                                    onClickCopyRepoItem(cellProps.row.original)
                                }}/>
                            }
                            {/*{*/}
                            {/*    cellProps.row.original.permissions.canDelete &&*/}
                            {/*    <i className="fas fa-trash delete-icon" onClick={(e) => {*/}
                            {/*        e.stopPropagation()*/}
                            {/*        onClickDeleteRepoItem(cellProps.row.original)*/}
                            {/*    }}/>*/}
                            {/*}*/}
                        </div>
                    )
                }
            }
        ],
        [projects, currentSortBy, t]
    )

    const handleReloadData = (sortBy, pageIndex) => {
        if (!isLoading) {
            setCurrentSortBy([...sortBy])
            getUserProjects(sortBy, pageIndex);
        }
    }

    const reactTableLoadingIndicator = <ReactTableLoadingIndicator loadingText={t('projects.loading')}/>;
    const reactTableEmptyState = <ReactTableEmptyState title={t('projects.project_empty')} />
    const onRowClick = (row) => {
        props.onClickEditProject(row.original)
    }

    function onClickDeleteRepoItem(repoItem) {
        if (repoItem.permissions.canDelete) {
            VerificationPopup.show(t("projects.delete_popup.title"), t("projects.delete_popup.subtitle"), () => {
                GlobalPageMethods.setFullScreenLoading(true)
                deleteRepoItem(repoItem.id, navigate, (responseData) => {
                    GlobalPageMethods.setFullScreenLoading(false)

                    const tempProjects = projects.filter((tempRepoItem) => {
                        return tempRepoItem.id !== responseData.data.id;
                    });
                    setProjects(tempProjects);
                }, (error) => {
                    GlobalPageMethods.setFullScreenLoading(false)
                    Toaster.showServerError(error);
                })
            })
        }
    }

    function onClickCopyRepoItem(repoItem) {
        if (repoItem.permissions.canCopy) {
            VerificationPopup.show(t("publication.copy_confirmation.title"), t("publication.copy_confirmation.subtitle"), () => {
                GlobalPageMethods.setFullScreenLoading(true)
                copyRepoItem(repoItem.id, navigate, (responseData) => {
                    getUserProjects(currentSortBy);
                    GlobalPageMethods.setFullScreenLoading(false)
                }, (error) => {
                    GlobalPageMethods.setFullScreenLoading(false)
                    Toaster.showServerError(error);
                })
            })
        }
    }

    useEffect(() => {
        getUserProjects(currentSortBy);
    }, [props.repoStatusFilter, query, searchOutOfScope]);

    if (!user) {
        return null
    }

    return <div>
        {props.allowSearch &&
        <div className={'form-field-container react-table-filter-row query-row'}>
            <ReactTableSearchInput placeholder={t('action.search')}
                                   onChange={(e) => {
                                       debouncedQueryChange(e.target.value)
                                   }}
            />
            {props.allowOutsideScope === true &&
            <div className="checkbox">
                <div className="option">
                    <input
                        id={'search-outside-scope'}
                        // defaultChecked={false}
                        type="checkbox"
                        onChange={(e) => setSearchOutOfScope(e.target.checked)}/>
                    <label htmlFor={'search-outside-scope'}>
                        {t('repoitem.popup.searchoutsidescope')}
                    </label>
                </div>
            </div>
            }
        </div>}
        <ReactTable columns={columns}
                    tableRows={tableRows}
                    enablePagination={props.enablePagination ?? false}
                    pageCount={paginationCountRef.current ?? -1}
                    onReloadData={handleReloadData}
                    loadingIndicator={reactTableLoadingIndicator}
                    emptyState={reactTableEmptyState}
                    isLoading={isLoading}
                    onRowClick={onRowClick}
        />
    </div>

    function getUserProjects(sortBy = [], pageIndex = 0) {
        setProjects([])
        setIsLoading(true)

        const errorCallback = (error) => {
            setIsLoading(false)
            Toaster.showServerError(error)
        }

        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsLoading(false)
            paginationCountRef.current = parseInt(new URLSearchParams(response.links.last).get("page[number]"))
            setProjects(response.data);

            if (props.onTableFiltered) {
                props.onTableFiltered({
                    query: query,
                    count: response.meta.totalCount
                })
            }
        }

        function onServerFailure(error) {
            errorCallback(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            errorCallback(error)
        }

        const config = {
            params: {
                'filter[isRemoved]': false,
                'filter[repoType]': "Project",
                'filter[scope]': searchOutOfScope ? "off" : "on",
            }
        };
        if (props.enablePagination === true) {
            config.params['page[size]'] = 10
            config.params['page[number]'] = pageIndex + 1
        }
        if (props.filterOnUserId) {
            config.params['filter[authorID]'] = props.filterOnUserId
        }
        if (props.repoStatusFilter) {
            config.params['filter[status]'] = props.repoStatusFilter
        }
        if ((props.allowSearch && query.length > 0) || props.searchAddition) {
            let totalSearch = ""
            if (props.searchAddition) {
                totalSearch += props.searchAddition
            }
            if (props.allowSearch && query.length > 0) {
                totalSearch += " ";
                totalSearch += query;
            }
            config.params['filter[search]'] = totalSearch
        }

        if (sortBy.length > 0) {
            config.params.sort = sortBy.map(sort => {
                let sortKey;
                switch (sort.id) {
                    case "extra.organisations":
                        sortKey = "institute"
                        break
                    case "type":
                        sortKey = "repoType"
                        break;
                    default:
                        sortKey = sort.id
                        break;
                }
                return (sort.desc ? '-' : '') + sortKey
            }).join(',');
        }
        config.cancelToken = cancelToken.current;
        console.log(config.cancelToken);
        cancelToken.current = Api.jsonApiGet('repoItemSummaries', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

export default ProjectTable;
