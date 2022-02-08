import React, {useEffect, useState} from "react";
import './reacttemplatestable.scss'
import {useTranslation} from "react-i18next";
import {ReactTableSearchInput} from "../components/reacttable/filterrow/ReacTableFilterItems";
import {
    ReactTable,
    ReactTableEmptyState,
    ReactTableLoadingIndicator,
    ReactTableSortIcon
} from "../components/reacttable/reacttable/ReactTable";
import {HelperFunctions} from "../util/HelperFunctions";
import Toaster from "../util/toaster/Toaster";
import Api from "../util/api/Api";
import RepoItemHelper from "../util/RepoItemHelper";
import {useHistory} from "react-router-dom";

export function ReactTemplatesTable(props) {
    const {t} = useTranslation();
    const [currentSortBy, setCurrentSortBy] = useState([]);
    const [currentQuery, setCurrentQuery] = useState('');
    const [templates, setTemplates] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const tableRows = templates;
    const debouncedQueryChange = HelperFunctions.debounce(setCurrentQuery)
    const reactTableLoadingIndicator = <ReactTableLoadingIndicator loadingText={t('loading_indicator.loading_text')}/>;
    const reactTableEmptyState = <ReactTableEmptyState title={t('templates.templates_list_empty')}/>
    const history = useHistory()

    const columns = React.useMemo(
        () => [
            {
                Header: () => {
                    return [t('templates.templates_table.title'), ' ', <ReactTableSortIcon sortOrder={currentSortBy} name={'title'}/>]
                },
                accessor: 'title',
                className: 'bold-text',
                style: {
                    width: "30%"
                }
            },
            {
                Header: () => {
                    return [t('templates.templates_table.organisation'), ' ', <ReactTableSortIcon sortOrder={currentSortBy} name={'instituteTitle'}/>]
                },
                accessor: 'instituteTitle',
                className: 'templates-row-organisation',
                style: {
                    width: "20%"
                }
            },
            {
                Header: () => {
                    return [t('templates.templates_table.last_edited'), ' ', <ReactTableSortIcon sortOrder={currentSortBy} name={'lastEdited'}/>]
                },
                accessor: 'lastEdited',
                style: {
                    width: "15%"
                },
                Cell: tableInfo => {
                    return RepoItemHelper.getLastEditedDate(tableInfo.cell.value)
                }
            }
        ],
        [currentSortBy, t]
    )

    const handleReloadData = (sortBy, pageIndex) => {
        if(!isLoading) {
            setCurrentSortBy([...sortBy])
            getTemplates(currentSortBy, currentQuery)
        }
    };

    const navigateToEditTemplatePage = (row) => {

        props.props.history.push('../templates/' + row.original.id)
    }

    useEffect(() => {
        getTemplates(currentSortBy, currentQuery)
    }, [currentQuery]);

    return (
        <div className={'templates-list flex-column'}>
            <div className={'form-field-container react-table-filter-row'}>
                <ReactTableSearchInput placeholder={t('action.search')}
                                       onChange={(e) => {
                                           debouncedQueryChange(e.target.value)
                                       }}
                />
            </div>
            <ReactTable
                tableName={'react-templates-table'}
                columns={columns}
                tableRows={tableRows}
                onReloadData={handleReloadData}
                loadingIndicator={reactTableLoadingIndicator}
                emptyState={reactTableEmptyState}
                isLoading={isLoading}
                onRowClick={(row) => {
                    navigateToEditTemplatePage(row);
                }}/>
        </div>
    )

    function getTemplates(sortBy = [], query = "") {

        if(isLoading) {
            return;
        }

        setTemplates([])
        setIsLoading(true)

        const config = {
            params: {
                'filter[allowCustomization]': 1,
            }
        };

        if (query.length > 0) {
            config.params['filter[title][LIKE]'] = '%' + query + '%'
        }

        if (sortBy.length > 0) {
            config.params.sort = sortBy.map(sort => {
                return (sort.desc ? '-' : '') + sort.id
            }).join(',');
        }

        function onValidate(response) {}

        function onSuccess(response) {
            setIsLoading(false)
            setTemplates(response.data);
        }

        function onLocalFailure(error) {
            setIsLoading(false);
            Toaster.showDefaultRequestError();
        }

        function onServerFailure(error) {
            setIsLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        Api.jsonApiGet('templates', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

