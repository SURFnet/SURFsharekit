import React, {useEffect} from "react";
import {usePagination, useSortBy, useTable} from "react-table";
import LoadingIndicator from "../../loadingindicator/LoadingIndicator";
import IconButtonText from "../../buttons/iconbuttontext/IconButtonText";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faAngleDown, faAngleUp, faArrowLeft, faArrowRight} from "@fortawesome/free-solid-svg-icons";
import "./reacttable.scss"
import "./reacttablefilterrow.scss"

export function ReactTable({
                               tableName = '',
                               columns,
                               tableRows,
                               footer = undefined,
                               enablePagination = false,
                               pageCount = -1,
                               onReloadData,
                               loadingIndicator,
                               isLoading,
                               emptyState = null,
                               onRowClick
                           }) {

    const data = tableRows ?? []
    const tableConfig = {
        columns,
        data,
        manualSortBy: true
    }
    if (enablePagination === true) {
        tableConfig.manualPagination = true
        tableConfig.initialState = {
            pageIndex: 0
        }
        tableConfig.pageCount = pageCount
    }

    const hooks = [useSortBy]
    if (enablePagination === true) {
        hooks.push(usePagination);
    }

    const table = useTable(tableConfig, ...hooks)
    const sortBy = table.state.sortBy
    const pageIndex = table.state.pageIndex
    const pageSize = table.state.pageSize

    useEffect(() => {
        onReloadData && onReloadData(sortBy, pageIndex, pageSize);
    }, [sortBy, pageIndex, pageSize]);

    return (
        <div className={"table-wrapper " + tableName}>
            <div className={"table-container"}>
                <table className={"react-table " + (!footer && 'without-footer')} {...table.getTableProps()}>
                    <thead>
                    {table.headerGroups.map((headerGroup, i) => (
                        <tr key={i}>
                            {headerGroup.headers.map((column) => (
                                <th key={column.id} {...column.getSortByToggleProps(
                                    {
                                        className: column.className,
                                        style: column.style
                                    }
                                )}
                                >
                                    {column.render('Header')}
                                </th>
                            ))}
                        </tr>
                    ))}
                    </thead>
                    {
                        (!(isLoading && data.length === 0) && footer) && <tfoot>
                        <tr>
                            <td colSpan="100%">
                                <div className={'footer-holder'}>
                                    {footer}
                                </div>
                            </td>
                        </tr>
                        </tfoot>
                    }
                    <tbody {...table.getTableBodyProps()}>
                    {table.rows.map((row, i) => {
                        table.prepareRow(row)
                        return (
                            <tr {...row.getRowProps()} onClick={(e) => {
                                if (onRowClick && !e.isPropagationStopped()) {
                                    return onRowClick(row)
                                }
                            }}>
                                {row.cells.map(cell => {
                                    return (
                                        <td {...cell.getCellProps({
                                            className: cell.column.className,
                                            style: cell.column.style
                                        })}
                                        >
                                            {cell.render('Cell')}
                                        </td>
                                    )
                                })}
                            </tr>
                        )
                    })}
                    </tbody>
                </table>
                {isLoading && data.length === 0 && loadingIndicator}
                {!isLoading && data.length != null && data.length === 0 && emptyState}
                {enablePagination && pageCount > 1 && <Pagination pageIndex={pageIndex}
                                                                  pageCount={pageCount}
                                                                  setPage={table.gotoPage}
                                                                  previousPageIfPossible={previousPageIfPossible}
                                                                  nextPageIfPossible={nextPageIfPossible}/>}
            </div>
        </div>
    )

    function previousPageIfPossible() {
        if (table.canPreviousPage) {
            table.previousPage()
        }
    }

    function nextPageIfPossible() {
        if (table.canNextPage) {
            table.nextPage()
        }
    }
}

export function Pagination(props) {
    return <div className={"pagination"}>
        <FontAwesomeIcon icon={faArrowLeft} className={"previous"}

                         onClick={props.previousPageIfPossible}>Previous</FontAwesomeIcon>
        <PaginationContent {...props}/>
        <FontAwesomeIcon icon={faArrowRight} className={"next"}
                         onClick={props.nextPageIfPossible}>Next</FontAwesomeIcon>
    </div>

    function PaginationContent() {
        let paginationButtonOffset = 1
        const paginationNumbers = []

        paginationNumbers.push(props.pageIndex)
        //Add numbers around current index
        let morePagesAvailableBefore = true
        let morePagesAvailableAfter = true
        while (paginationNumbers.length < 7 && (morePagesAvailableBefore || morePagesAvailableAfter)) {
            if (props.pageIndex - paginationButtonOffset >= 0) {
                paginationNumbers.unshift(props.pageIndex - paginationButtonOffset)
                morePagesAvailableBefore = true
            } else {
                morePagesAvailableBefore = false
            }
            if (props.pageIndex + paginationButtonOffset <= (props.pageCount - 1)) {
                paginationNumbers.push(props.pageIndex + paginationButtonOffset)
                morePagesAvailableAfter = true
            } else {
                morePagesAvailableAfter = false
            }

            paginationButtonOffset++
        }

        //If first index is not 0, edit it to zero and second to null (will be "...")
        if (paginationNumbers[0] > 0) {
            paginationNumbers[0] = 0
            paginationNumbers[1] = null
        }
        //If last index is not pageCount - 1, edit it to pageCount - 1 and the one before to null (will be "...")
        if (paginationNumbers[paginationNumbers.length - 1] < (props.pageCount - 1)) {
            paginationNumbers[paginationNumbers.length - 1] = (props.pageCount - 1)
            paginationNumbers[paginationNumbers.length - 2] = null
        }

        const paginationContent = []
        for (let i = 0; i < paginationNumbers.length; i++) {
            let pageNumber = paginationNumbers[i]
            if (props.pageIndex === pageNumber) {
                paginationContent.push(<div className={"pagination-number current"}>{pageNumber + 1}</div>)
            } else if (pageNumber == null) {
                paginationContent.push(<div className={"pagination-number"}>...</div>)
            } else {
                paginationContent.push(<div className={"pagination-number clickable"}
                                            onClick={() => props.setPage(pageNumber)}>{pageNumber + 1}</div>)
            }
        }

        return paginationContent
    }
}

export function ReactTableLoadingIndicator({loadingText}) {
    return (
        <div className={"react-table-loading-indicator"}>
            <LoadingIndicator/>
            <div className={"loading-subtitle"}>{loadingText}</div>
        </div>
    )
}

export function ReactTableEmptyState(props) {
    return (
        <div className='react-table-empty-list'>
            <div className={'empty-list-title'}>
                {props.title}
            </div>
        </div>
    )
}

export function ReactTableSortIcon(props) {
    const sortObj = props.sortOrder.find(a => a.id === props.name)
    if (sortObj) {
        return <FontAwesomeIcon icon={sortObj.desc ? faAngleDown : faAngleUp}/>;
    } else {
        return ''
    }
}