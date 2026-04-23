import React, {useEffect, useState} from 'react'
import Page, {GlobalPageMethods} from "../components/page/Page";
import {useTranslation} from "react-i18next";
import "./archive.scss"
import {SearchInput} from "../components/searchinput/SearchInput";
import {Pagination} from "../components/reacttable/reacttable/ReactTable";
import {EmptyState} from "../components/emptystate/EmptyState";
import {HelperFunctions} from "../util/HelperFunctions";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import useDocumentTitle from "../util/useDocumentTitle";
import {Navigate, useSearchParams} from "react-router-dom";
import ArchiveResultRow from "./ArchiveResultRow";

function Archive(props) {
    const {t} = useTranslation();
    const [searchParams, setSearchParams] = useSearchParams();
    const [currentQuery, setCurrentQuery] = useState(searchParams.get('searchQuery') ?? '');
    const [pageIndex, setPageIndex] = useState(parseInt(searchParams.get('page') ?? '1') - 1);
    const [totalCount, setTotalCount] = useState(0);
    const [searchResults, setSearchResults] = useState([]);
    const debouncedQueryChange = (value) => {
        setCurrentQuery(value);
        setSearchParams(prev => {
            const newParams = new URLSearchParams(prev);
            if (value) {
                newParams.set('searchQuery', value);
            } else {
                newParams.delete('searchQuery');
            }
            newParams.set('page', '1'); // Reset to first page on new search
            return newParams;
        });
    }
    const pageSize = 10;

    const [user] = useAppStorageState(StorageKey.USER);

    useDocumentTitle("Archive")

    useEffect(() => {
        search(currentQuery)
    }, [currentQuery, pageIndex]);

    if (user === null) {
        return <Navigate to={'login?redirect=archive'}/>
    }

    function search(searchQuery) {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            params: {
                'fields[repoItems]': 'title,repoType,permissions,lastEdited,authorName',
                'filter[search]': searchQuery,
                'filter[archived]': 1,
                'filter[isRemoved]': 0,
                'sort': '-lastEdited',
                'page[number]': pageIndex + 1,
                'page[size]': pageSize,
            }
        }

        const errorCallback = (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        }

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            setSearchResults(Api.dataFormatter.deserialize(response.data) ?? [])
            setTotalCount(response.data.meta.totalCount)
        }

        function onLocalFailure(error) {
            console.log(error);
            errorCallback(error)
        }

        function onServerFailure(error) {
            console.log(error);
            errorCallback(error)
        }

        Api.get('repoItemSummaries', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }

    function SearchResultsList() {
        if (searchResults.length === 0) {
            return (
                <div className={"archive-results-list"}>
                    <EmptyState />
                </div>
            )
        }

        return <div className={"archive-results-list"}>{
            searchResults.map((searchResult) => <ArchiveResultRow
                {...searchResult}
                key={searchResult.id}
                history={props.history}
                onReload={() => {
                    setPageIndex(0)
                    search(currentQuery)
                }}
            />)
        }</div>
    }

    const content = (
        <div className={"archive-page-content"}>
            <div className={"title-row"}>
                <h1>{t("side_menu.archive")}</h1>
                <div className={"search-count"}>{totalCount}</div>
            </div>
            <SearchInput placeholder={t("navigation_bar.search")}
                         defaultValue={currentQuery}
                         onChange={(e) => debouncedQueryChange(e.target.value)}/>
            <SearchResultsList/>
            { totalCount !== 0 &&
                <Pagination  pageIndex={pageIndex}
                             pageCount={Math.ceil(totalCount / pageSize)}
                             setPage={(newPage) => {
                                 setPageIndex(newPage);
                                 setSearchParams(prev => {
                                     const newParams = new URLSearchParams(prev);
                                     newParams.set('page', (newPage + 1).toString());
                                     return newParams;
                                 });
                             }}
                             previousPageIfPossible={() => {
                                 if (pageIndex > 0) {
                                     setPageIndex(pageIndex - 1);
                                     setSearchParams(prev => {
                                         const newParams = new URLSearchParams(prev);
                                         newParams.set('page', pageIndex.toString());
                                         return newParams;
                                     });
                                 }
                             }}
                             nextPageIfPossible={() => {
                                 if (pageIndex < Math.ceil(totalCount / pageSize) - 1) {
                                     setPageIndex(pageIndex + 1);
                                     setSearchParams(prev => {
                                         const newParams = new URLSearchParams(prev);
                                         newParams.set('page', (pageIndex + 2).toString());
                                         return newParams;
                                     });
                                 }
                             }}
                />
            }
        </div>
    )

    return <Page
        id="archive"
        history={props.history}
        activeMenuItem={"archive"}
        content={content}
        breadcrumbs={[
            {
                path: './dashboard',
                title: 'side_menu.dashboard'
            },
            {
                title: 'side_menu.archive'
            }
        ]}
    />
}

export default Archive