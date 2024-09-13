import React, {useEffect, useRef, useState} from 'react';
import './tasktable.scss';
import {useHistory} from "react-router-dom";
import {useTranslation} from "react-i18next";
import Api from "../../../util/api/Api";
import Toaster from "../../../util/toaster/Toaster";
import styled from "styled-components";
import {majorelle, nunitoExtraBold, SURFShapeLeft, white} from "../../../Mixins";
import {StorageKey, useAppStorageState} from "../../../util/AppStorage";
import {
    ReactTable,
    ReactTableEmptyState,
    ReactTableLoadingIndicator,
} from "../../../components/reacttable/reacttable/ReactTable";
import TaskHelper from "../../../util/TaskHelper";
import {ThemedA} from "../../../Elements";
import ActionCell from "../action-cell/ActionCell";
import EmptyPlaceholder from "../../../resources/images/tasks-empty.png"
import CompletedActionCell from "../action-cell/CompletedActionCell";
import {EventDispatcher, TestEvent, UpdateTaskCountEvent} from "../../../util/events/Events";
import app from "../../../App";

function ReactTaskTable(props) {

    const [user] = useAppStorageState(StorageKey.USER);
    const [tasks, setTasks] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const history = useHistory()
    const {t} = useTranslation();
    const paginationCountRef = useRef();
    const cancelToken = useRef();

    const [pageIndex, setPageIndex] = useState(0)

    let filtersSet = false
    const noTasks = tasks && tasks.length === 0

    useEffect(() => {
        // TODO: get sortBy if needed
        handleReloadData([], 0, true)
    }, [props.filters])

    const columns = React.useMemo(
        () => [
            {
                Header: () => {
                    return [<span className={"border"}>{t("dashboard.tasks.header.title")}</span>]
                },
                accessor: 'nope',
                disableSortBy: true,
                style: {
                    width: "35%"
                },
                Cell: cellProps => {
                    return <TaskRowTitle>{
                        <div dangerouslySetInnerHTML={{__html: TaskHelper.getTaskTitle(cellProps.row.original) }} />
                    }</TaskRowTitle>
                },
            },
            {
                Header: () => [<span className={"border"}>{t("dashboard.tasks.header.material")}</span>],
                accessor: 'material',
                disableSortBy: true,
                style: {
                    width: "10%"
                },
                Cell: cellProps => cellProps.value ? TaskHelper.getRepoTypeTitle(cellProps.value) ?? "" : ""
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t("dashboard.tasks.header.type")}</span>]
                },
                accessor: 'type',
                disableSortBy: true,
                style: {
                    width: "10%"
                },
                Cell: cellProps => {
                    return TaskHelper.getTypeTitle(cellProps.value)
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t("dashboard.tasks.header.organization")}</span>]
                },
                accessor: 'institute',
                disableSortBy: true,
                style: {
                    width: "10%"
                },
                Cell: cellProps => cellProps.value
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t("dashboard.tasks.header.date")}</span>]
                },
                accessor: 'date',
                disableSortBy: true,
                style: {
                    width: "10%"
                },
                Cell: cellProps => {
                    return TaskHelper.getDate(cellProps.value)
                }
            },
            {
                Header: () => {
                    return [<span className={"border"}>{t("dashboard.tasks.header.action")}</span>]
                },
                accessor: 'action',
                disableSortBy: true,
                style: {
                    width: "25%"
                },
                Cell: cellProps => {
                    return props.isDone ?
                        <CompletedActionCell
                            task={cellProps.row.original}
                            onDeleteSuccess={(taskId) => {
                                handleSuccessfulActionOnTask(taskId)
                            }}
                        />
                        :
                        <ActionCell
                            task={cellProps.row.original}
                            onActionSuccess={(taskId) => {
                                handleSuccessfulActionOnTask(taskId)
                            }}
                        />
                }
            }
        ],
        [tasks, t]
    )

    const handleReloadData = (sortBy, pageIndex, force = false) => {
        if (!isLoading || force) {
            setPageIndex(pageIndex)
            getTasks(sortBy, pageIndex);
        }
    }

    const handleSuccessfulActionOnTask = (taskId) => {
        var nextPageIndex = pageIndex

        const updatedTasks = tasks.filter(task => task.id !== taskId)
        if((!updatedTasks || updatedTasks.length === 0) && paginationCountRef.current === (pageIndex + 1)) {
            // If the selected page is the last one and the action was performed on the last item on this page,
            // the total pageCount and pageIndex are reduced here so the api call is made with the correct parameters
            // and the Pagination component shows the right amount of total pages
            paginationCountRef.current = (paginationCountRef.current - 2)
            nextPageIndex = nextPageIndex > 0 ? pageIndex - 1 : pageIndex
        }
        handleReloadData([], nextPageIndex)
    }

    const reactTableLoadingIndicator = <ReactTableLoadingIndicator
        loadingText={t('dashboard.tasks.table.loading')}/>;
    const completedTaskTableEmptyState = <ReactTableEmptyState title={t('dashboard.tasks.empty.title')} />
    const taskTableEmptyState = <EmptyTaskTablePlaceholderWrapper>
                                    <EmptyTaskTablePlaceholder src={EmptyPlaceholder} alt=""/>
                                    <EmptyTaskTableText>{t("dashboard.tasks.table.empty.title")}</EmptyTaskTableText>
                                </EmptyTaskTablePlaceholderWrapper>


    if (!user) {
        return null
    }

    return  <div>
                <ReactTableWrapper isHidden={noTasks && !isLoading} isDone={props.isDone}>
                    <ReactTable columns={columns}
                                tableRows={tasks}
                                cellsAreLinks={false}
                                enablePagination={props.enablePagination ?? false}
                                pageCount={paginationCountRef.current ?? -1}
                                onReloadData={handleReloadData}
                                loadingIndicator={reactTableLoadingIndicator}
                                isLoading={isLoading}
                                separatedRows={true}
                                isDone={props.isDone}
                                emptyState={props.isDone ? completedTaskTableEmptyState : taskTableEmptyState}
                    />
                </ReactTableWrapper>
            </div>

    function getTasks(sortBy = [], pageIndex = 0) {
        if(tasks !== null) {
            setTasks([])
        }
        setIsLoading(true)

        const config = {
            params: {
                'filter[state][EQ]': props.isDone ? 'DONE' : 'INITIAL',
                'sort': 'created'
            }
        };

        if (props.isDone) {
            config.params["include"] = "completedBy"
        }

        if (props.enablePagination === true) {
            config.params['page[size]'] = 10
            config.params['page[number]'] = pageIndex + 1
        }

        if (props.filters) {
            const appliedFilters = {};
            Object.entries(props.filters).filter(([, value]) => value !== null).forEach(([key, value]) => (appliedFilters[key] = value));

            filtersSet = Object.entries(appliedFilters).length > 0;

            if (Object.entries(appliedFilters).length > 0) {
                for (const appliedFilter in appliedFilters) {
                    config.params[`additionalFilter[${appliedFilter}][EQ]`] = appliedFilters[appliedFilter];
                }
            }
        }

        config.cancelToken = cancelToken.current;
        cancelToken.current = Api.jsonApiGet('tasks', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsLoading(false)
            paginationCountRef.current = parseInt(new URLSearchParams(response.links.last).get("page[number]"))
            setTasks(response.data);

            if (props.onFiltersLoad) {
                props.onFiltersLoad(response.filters)
            }

            if (props.onTableFiltered) {
                props.onTableFiltered({
                    query: null,
                    count: response.meta.totalCount
                })
            }

            if (!props.isDone && filtersSet === false) {
                // Only dispatch event for unfinished tasks
                window.dispatchEvent(new UpdateTaskCountEvent({count: response.meta.totalCount}))
            }
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
}

const TaskRowTitle = styled(ThemedA)`
    & a {
        color: ${majorelle};
        text-decoration: underline;
    }
`;

const EmptyTaskTablePlaceholder = styled.img`
    width: 70px;
`;

const EmptyTaskTablePlaceholderWrapper = styled.div`
    width: 100%;
    background: ${white};
    ${SURFShapeLeft};
    padding: 30px;
    gap: 20px;
    display: flexbox;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    margin-bottom: 100px;
`;

const EmptyTaskTableText = styled.div`
    ${nunitoExtraBold};
    font-size: 25px;
`;

const ReactTableWrapper = styled.div`
    ${props => !props.isDone && (`visibility: ${props => props.isHidden ? "hidden" : "visible"}`)};
`;

export default ReactTaskTable;