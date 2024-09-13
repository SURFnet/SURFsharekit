import {useTranslation} from "react-i18next";
import React, {useEffect, useRef, useState} from "react";
import './reactrollsandrightstable.scss'
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faEdit} from "@fortawesome/free-solid-svg-icons";
import {
    ReactTable,
    ReactTableEmptyState,
    ReactTableLoadingIndicator,
    ReactTableSortIcon
} from "../components/reacttable/reacttable/ReactTable";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import {HelperFunctions} from "../util/HelperFunctions";
import {ReactTableSearchInput} from "../components/reacttable/filterrow/ReacTableFilterItems";

function ReactRollsAndRightsTable(props) {
    const [currentSortBy, setCurrentSortBy] = useState([]);
    const [currentPageIndex, setCurrentPageIndex] = useState(0);
    const [currentQuery, setCurrentQuery] = useState('');
    const [groups, setGroups] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const {t} = useTranslation();
    const tableRows = groups;
    const paginationCountRef = useRef();
    const reactTableLoadingIndicator = <ReactTableLoadingIndicator loadingText={t('loading_indicator.loading_text')}/>;
    const debouncedQueryChange = HelperFunctions.debounce(setCurrentQuery)
    const reactTableEmptyState = <ReactTableEmptyState title={t('organisation.roles_rights.groups_empty_title')}/>


    const columns = React.useMemo(
        () => [
            {
                Header: '',
                accessor: 'groupIcon',
                className: 'group-row-icon',
                disableSortBy: true,
                Cell: () => {
                    return (
                        <div className={"group-icon-cell"}>
                            <i className="fas fa-users document-icon"/>
                        </div>
                    )
                },
                style: {
                    minWidth: "59px"
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('organisation.roles_rights.group_title')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'title'}/>]
                },
                accessor: t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN',
                className: 'bold-text',
                Cell: (tableInfo) => {
                    return <div>{tableInfo.cell.value}</div>
                },
                style: {
                    width: "20%"
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('organisation.roles_rights.group_organisation')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'partOf.title'}/>]
                },
                accessor: 'partOf.title',
                className: 'group-row-organisation',
                style: {
                    width: "30%"
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('organisation.roles_rights.group_organisation_level')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'partOf.level'}/>]
                },
                accessor: 'partOf.level',
                className: 'group-row-organisation-level',
                Cell: (tableInfo) => {
                    const levelKey = tableInfo.cell.value;
                    return levelKey ? t(`organisation.level.${levelKey}`) : "";
                },
                style: {
                    width: "30%"
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t('organisation.roles_rights.group_users')}</span>, ' ',
                        <ReactTableSortIcon sortOrder={currentSortBy} name={'persons'}/>]
                },
                accessor: 'amountOfPersons',
                className: 'group-row-users',
                disableSortBy: true,
                Cell: (tableInfo) => {
                    return <div>{tableInfo.cell.value}</div>
                },
                style: {
                    width: "10%"
                }
            },
            {
                accessor: 'actions',
                className: 'group-row-actions',
                disableSortBy: true,
                disableLink: true,
                style: {
                    minWidth: "100px"
                },
                Cell: (tableInfo) => {
                    const group = tableInfo.cell.row.original
                    return <div className={'flex-row'}>
                        <FontAwesomeIcon icon={faEdit}
                                         className={`icon-trash${group.permissions.canDelete ? "" : " disabled"}`}
                                         onClick={() => {
                                             navigateToGroupDetailPage(tableInfo.cell.row)
                                         }}
                        />
                    </div>
                }
            }
        ],
        [groups, currentSortBy, t]
    )

    const navigateToGroupDetailPage = (row) => {
        props.props.history.push('../groups/' + row.original.id)
    }

    const handleReloadData = (sortBy, pageIndex) => {
        if(!isLoading) {
            setCurrentSortBy([...sortBy])
            setCurrentPageIndex(pageIndex)
            getGroups(sortBy, pageIndex, currentQuery)
        }
    };

    useEffect(() => {
        setCurrentPageIndex(0)
        getGroups(currentSortBy, 0, currentQuery)
    }, [currentQuery]);

    return (
        <div className={'rolls-and-rights flex-column'}>
            <div className={'form-field-container react-table-filter-row'}>
                <ReactTableSearchInput placeholder={t('action.search')}
                                       onChange={(e) => {
                                                 debouncedQueryChange(e.target.value)
                                             }}
                />
            </div>
            <ReactTable
                tableName={'react-rolls-and-rights-table'}
                columns={columns}
                tableRows={tableRows}
                enablePagination={true}
                pageCount={paginationCountRef.current ?? -1}
                onReloadData={handleReloadData}
                loadingIndicator={reactTableLoadingIndicator}
                emptyState={reactTableEmptyState}
                isLoading={isLoading}
                onRowClick={navigateToGroupDetailPage}/>
        </div>
    )

    function getGroups(sortBy = [], pageIndex = 0, query = "") {

        if(isLoading) {
            return;
        }

        setGroups([])
        setIsLoading(true)

        const config = {
            params: {
                'include': 'partOf',
                'fields[institutes]': 'level,title,type',
                'fields[groups]': 'title,partOf,amountOfPersons,permissions,roleCode,labelNL,labelEN',
                'filter[roleCode][NEQ]': 'Default Member',
//                'filter[level]': 'organisation,consortium',
                'page[size]': 10,
                'page[number]': pageIndex + 1
            }
        };

        if (query.length > 0) {
            config.params['filter[labelEN,labelNL][LIKE]'] = '%' + query + '%'
        }

        if (sortBy.length > 0) {
            config.params.sort = sortBy.map(sort => {
                let sortKey;
                switch (sort.id) {
                    case "partOf.title":
                        sortKey = "title"
                        break
                    case "partOf.level":
                        sortKey = "level"
                        break
                    case "partOf.type":
                        sortKey = "type"
                        break;
                    default:
                        sortKey = sort.id
                        break;
                }
                return (sort.desc ? '-' : '') + sortKey
            }).join(',');
        }

        function onValidate(response) {}

        function onSuccess(response) {
            setIsLoading(false)
            paginationCountRef.current = parseInt(new URLSearchParams(response.links.last).get("page[number]"))
            setGroups(response.data);
        }

        function onLocalFailure(error) {
            setIsLoading(false);
            Toaster.showDefaultRequestError();
        }

        function onServerFailure(error) {
            setIsLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                window.location.href = '/login?redirect=' + window.location.pathname;
            }
        }

        Api.jsonApiGet('groups', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

export default ReactRollsAndRightsTable;