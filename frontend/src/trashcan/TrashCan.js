import React, {useEffect, useState} from "react"
import './trashcan.scss'
import Page, {GlobalPageMethods} from "../components/page/Page";
import {useTranslation} from "react-i18next";
import {SearchInput} from "../components/searchinput/SearchInput";
import {HelperFunctions} from "../util/HelperFunctions";
import {EmptyState} from "../components/emptystate/EmptyState";
import {Pagination} from "../components/reacttable/reacttable/ReactTable";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {Redirect} from "react-router-dom";
import {TrashcanResultRow} from "./TrashcanResultRow";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import MemberPositionOptionsHelper from "../util/MemberPositionOptionsHelper";

function TrashCan(props) {
    const {t} = useTranslation();
    const [currentQuery, setCurrentQuery] = useState(props.match.params.searchQuery ?? '');
    const [pageIndex, setPageIndex] = useState(0);
    const [totalCount, setTotalCount] = useState(0);
    const [searchResults, setSearchResults] = useState([]);
    const debouncedQueryChange = HelperFunctions.debounce(setCurrentQuery)
    const pageSize = 10;
    const [user] = useAppStorageState(StorageKey.USER);

    useEffect(() => {
        search(currentQuery)
    }, [currentQuery, pageIndex]);

    if (user === null) {
        return <Redirect to={'unauthorized?redirect=trashcan'}/>
    }

    function SearchResultsList() {
        if (searchResults.length === 0) {
            return (
                <div className={"trashcan-results-list"}>
                    <EmptyState/>
                </div>
            )
        } else {
            return (
                <div className={"trashcan-results-list"}>
                    {
                        searchResults.map((searchResult) => {
                            if (searchResult.title === null || searchResult.title === ''){
                                searchResult.title = t('search.unnamed')
                            }
                            switch (searchResult.type.toLowerCase()) {
                                case "group":
                                    return <TrashcanResultRow {...searchResult}
                                                              key={searchResult.id}
                                                              history={props.history}
                                                              onReload={() => {
                                                                  setPageIndex(0)
                                                                  search(currentQuery)
                                                              }}
                                                              subtitle={t("search.group")}/>
                                case "repoitem":
                                    return <TrashcanResultRow {...searchResult}
                                                              key={searchResult.id}
                                                              history={props.history}
                                                              onReload={() => {
                                                                  setPageIndex(0)
                                                                  search(currentQuery)
                                                              }}
                                                              subtitle={t('search.' + searchResult.repoType.toLowerCase())}/>
                                case "person":
                                    const options = new MemberPositionOptionsHelper().getPositionOptions()
                                    var positionLabel = '';
                                    if (searchResult.position !== null && searchResult.position !== '') {
                                        positionLabel = searchResult.position
                                        const position = options.find(o => o.value === searchResult.position)
                                        if (position !== undefined) {
                                            positionLabel = t('language.current_code') === 'nl' ? position.labelNL : position.labelEN
                                        }
                                    }
                                    return <TrashcanResultRow {...searchResult}
                                                              key={searchResult.id}
                                                              history={props.history}
                                                              onReload={() => {
                                                                  setPageIndex(0)
                                                                  search(currentQuery)
                                                              }}
                                                              title={searchResult.name}
                                                              subtitle={positionLabel}/>
                                default:
                                    return null;
                            }
                        })
                    }
                </div>
            )
        }
    }

    const content = (
        <div className={"trashcan-page-content"}>
            <div className={"title-row"}>
                <h1>{t("side_menu.trash_can")}</h1>
                <div className={"search-count"}>{totalCount}</div>
            </div>
            <SearchInput placeholder={t("navigation_bar.search")}
                         defaultValue={props.match.params.searchQuery}
                         onChange={(e) => {
                             debouncedQueryChange(e.target.value)
                         }}/>
            <SearchResultsList/>
            {totalCount !== 0 && <Pagination pageIndex={pageIndex}
                                             pageCount={Math.ceil(totalCount / pageSize)}
                                             setPage={setPageIndex}
                                             previousPageIfPossible={() => {
                                                 if (pageIndex > 0) {
                                                     setPageIndex(pageIndex - 1)
                                                 }
                                             }}
                                             nextPageIfPossible={() => {
                                                 if (pageIndex < Math.ceil(totalCount / pageSize) - 1) {
                                                     setPageIndex(pageIndex + 1)
                                                 }
                                             }}/>}
        </div>
    );

    return <Page id="trashcan"
                 history={props.history}
                 activeMenuItem={"trashcan"}
                 content={content}
                 breadcrumbs={[
                     {
                         path: './dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         title: 'side_menu.trash_can'
                     }
                 ]}
                 showBackButton={true}/>;

    function search(searchQuery) {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            params: {
                'fields[repoItems]': 'title,repoType,permissions,lastEdited',
                'fields[groups]': 'title,permissions,lastEdited',
                'fields[persons]': 'name,permissions,position,lastEdited',
                'filter[query]': searchQuery,
                'filter[isRemoved]': 1,
                'sort': '-lastEdited',
                'page[number]': pageIndex + 1,
                'page[size]': pageSize,
            }
        };

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            setSearchResults(Api.dataFormatter.deserialize(response.data) ?? [])
            setTotalCount(response.data.meta.totalCount)
        }

        function onLocalFailure(error) {
            console.log(error);
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            console.log(error);
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        }

        Api.get('search', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

export default TrashCan;