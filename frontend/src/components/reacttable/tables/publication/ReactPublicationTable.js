import React, {useEffect, useRef, useState} from "react";
import {StorageKey, useAppStorageState} from "../../../../util/AppStorage";
import {useTranslation} from "react-i18next";
import {
    ReactTable,
    ReactTableEmptyState,
    ReactTableLoadingIndicator,
    ReactTableSortIcon
} from "../../reacttable/ReactTable";
import {faPlus} from "@fortawesome/free-solid-svg-icons";
import {createAndNavigateToRepoItem} from "../../../../publications/Publications";
import {ReactStatusTableCell} from "../../cells/ReactStatusTableCell";
import RepoItemHelper from "../../../../util/RepoItemHelper";
import {ReactTypeTableCell} from "../../cells/ReactTypeTableCell";
import {GlobalPageMethods} from "../../../page/Page";
import {HelperFunctions} from "../../../../util/HelperFunctions";
import {ReactTableSearchInput} from "../../filterrow/ReacTableFilterItems";
import Toaster from "../../../../util/toaster/Toaster";
import Api from "../../../../util/api/Api";
import VerificationPopup from "../../../../verification/VerificationPopup";
import {useHistory, useLocation} from "react-router-dom";
import {ReactTableHelper} from "../../../../util/ReactTableHelper";

export const PublicationTableSort = {
    STATUS_CONCEPT: {
        desc: false,
        id: "status"
    }
}

function ReactPublicationTable(props) {
    const [user] = useAppStorageState(StorageKey.USER);
    const [userPublications, setUserPublications] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [searchOutOfScope, setSearchOutOfScope] = useState(props.searchOutOfScope ?? false);
    const [query, setQuery] = useState('');
    const debouncedQueryChange = HelperFunctions.debounce(setQuery)
    const [currentSortBy, setCurrentSortBy] = useState([]);
    const location = useLocation();
    const history = useHistory()
    const {t} = useTranslation();
    const paginationCountRef = useRef();
    const tableRows = userPublications;
    const cancelToken = useRef();
    const columns = React.useMemo(
        () => [
            {
                Header: () => {
                    return [<span className={"border"}>{t('my_publications.table.title')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'title'}/>]
                },
                accessor: 'title',
                className: 'bold-text',
                style: { width: "20%", maxWidth: "20%", minWidth: "140px", overflow: "hidden", wordBreak: "break-word"},
            },
            {
                Header: () => {
                    return [<span className={"border"}>
                        {t('my_publications.table.organisation')}
                    </span>]
                },
                accessor: 'extra.organisations',
                disableSortBy: true,
                style: { width: "12.5%"},
                Cell: (tableInfo) => {
                    let subOrgs = tableInfo.cell.value;
                    let publisher = tableInfo.row.original.extra.publisher
                    if (subOrgs) {
                        if (publisher) {
                            if (Array.isArray(subOrgs)) {
                                subOrgs = [publisher, ...subOrgs]
                            } else {
                                subOrgs = publisher + "\n" + subOrgs
                            }
                        }
                    } else if (publisher) {
                        subOrgs = [publisher]
                    }
                    return <div>{ReactTableHelper.concatenateCellValue(subOrgs)}</div>
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('my_publications.table.authors')}</span>]
                },
                accessor: 'extra.authors',
                disableSortBy: true,
                style: { width: "12.5%"},
                Cell: (tableInfo) => {
                    return <div>{ReactTableHelper.concatenateCellValue(tableInfo.cell.value)}</div>
                },
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('my_publications.table.type')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'type'}/>]
                },
                accessor: 'type',
                style: { width: "12.5%"},
                Cell: cellProps => {
                    return <ReactTypeTableCell props={cellProps.row.original}/>
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('my_publications.table.status')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'status'}/>]
                },
                accessor: 'status',
                style: { width: "12.5%"},
                Cell: cellProps => {
                    return <ReactStatusTableCell props={cellProps.row.original}/>
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('my_publications.table.creator')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'authorName'}/>]
                },
                accessor: 'authorName',
                style: { width: "12.5%"}
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('my_publications.table.last_edited')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'lastEdited'}/>]
                },
                accessor: 'lastEdited',
                style: { width: "12.5%"},
                Cell: cellProps => {
                    return RepoItemHelper.getLastEditedDate(cellProps.row.original.lastEdited)
                }
            },
            {
                Header: '',
                accessor: 'action-items',
                disableSortBy: true,
                disableLink: true,
                Cell: (cellProps) => {
                    return (
                        <div className="flex-row gap-4">
                            {
                                cellProps.row.original.permissions.canCopy &&
                                <i className="fas fa-copy copy-icon" onClick={(e) => {
                                    e.stopPropagation()
                                    onClickCopyRepoItem(cellProps.row.original)
                                }}/>
                            }
                            {
                                cellProps.row.original.permissions.canDelete && cellProps.row.original.status.toLowerCase() === 'draft' &&
                                <i className="fas fa-trash delete-icon" onClick={(e) => {
                                    e.stopPropagation()
                                    onClickDeleteRepoItem(cellProps.row.original)
                                }}/>
                            }
                        </div>
                    )
                }
            }
        ],
        [userPublications, currentSortBy, t]
    )

    const handleReloadData = (sortBy, pageIndex) => {
        if (!isLoading) {
            setCurrentSortBy([...sortBy])
            getUserPublications(sortBy, pageIndex);
        }
    }

    const reactTableLoadingIndicator = <ReactTableLoadingIndicator
        loadingText={t('dashboard.my_publications.loading')}/>;
    const reactTableEmptyState = <ReactTableEmptyState title={t('dashboard.my_publications.empty.title')} />
    const onRowClick = (row) => {
        props.onClickEditPublication(row.original)
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

    function onClickCopyRepoItem(repoItem) {
        if (repoItem.permissions.canCopy) {
            VerificationPopup.show(t("publication.copy_confirmation.title"), t("publication.copy_confirmation.subtitle"), () => {
                GlobalPageMethods.setFullScreenLoading(true)
                copyRepoItem(repoItem.id, history, (responseData) => {
                    getUserPublications(currentSortBy);
                    GlobalPageMethods.setFullScreenLoading(false)
                }, () => {
                    GlobalPageMethods.setFullScreenLoading(false)
                })
            })
        }
    }

    useEffect(() => {
        if (location.state !== undefined) {
            setCurrentSortBy([...location.state.detail])
            getUserPublications(location.state.detail);
        } else {
            getUserPublications(currentSortBy);
        }
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

    function getUserPublications(sortBy = [], pageIndex = 0) {
        setUserPublications([])
        setIsLoading(true)

        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsLoading(false)
            paginationCountRef.current = parseInt(new URLSearchParams(response.links.last).get("page[number]"))
            setUserPublications(response.data);

            if (props.onTableFiltered) {
                props.onTableFiltered({
                    query: query,
                    count: response.meta.totalCount
                })
            }
        }

        function onServerFailure(error) {
            setIsLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            setIsLoading(false);
            Toaster.showDefaultRequestError();
        }

        const config = {
            params: {
                'filter[isRemoved]': false,
                'filter[repoType][NEQ]': "Project",
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

export function requestDeleteRepoItem(repoItemId, history, successCallback, errorCallback = () => {}) {
    Api.post(
        'actions/requestdeleterepoitem/',
        () => {},
        (response) => {successCallback(response.data)},
        (error) => {
            Toaster.showDefaultRequestError()
            errorCallback()
        },
        (error) => {
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
            errorCallback()
        },
        {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        },
        {
            "data": {
                "type": "requestdeleterepoitem",
                "attributes": {
                    "repoItemId": repoItemId
                }
            }
        }
    )
}

export function deleteRepoItem(repoItemId, history, successCallback, errorCallback = () => {
}) {

    function onValidate(response) {
    }

    function onSuccess(response) {
        successCallback(response.data);
    }

    function onLocalFailure(error) {
        Toaster.showDefaultRequestError()
        errorCallback()
    }

    function onServerFailure(error) {
        Toaster.showServerError(error)
        if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
            history.push('/login?redirect=' + window.location.pathname);
        }
        errorCallback()
    }

    const config = {
        headers: {
            "Content-Type": "application/vnd.api+json",
        }
    }

    const patchData = {
        "data": {
            "type": "repoItem",
            "id": repoItemId,
            "attributes": {
                "isRemoved": true
            }
        }
    };

    Api.patch(`repoItems/${repoItemId}`, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData);
}

export function copyRepoItem(repoItemId, history, successCallback, errorCallback = () => {
}) {

    function onValidate(response) {
    }

    function onSuccess(response) {
        successCallback(response.data);
    }

    function onLocalFailure(error) {
        Toaster.showDefaultRequestError()
        errorCallback()
    }

    function onServerFailure(error) {
        Toaster.showServerError(error)
        if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
            history.push('/login?redirect=' + window.location.pathname);
        }
        errorCallback()
    }

    const config = {
        headers: {
            "Content-Type": "application/vnd.api+json",
        }
    }

    const patchData = {
        "data": {
            "type": "repoItem",
            "attributes": {
                "copyFrom": repoItemId
            }
        }
    };

    Api.post(`repoItems`, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData);
}

export default ReactPublicationTable