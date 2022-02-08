import React, {useEffect, useState} from "react"
import './search.scss'
import Page, {GlobalPageMethods} from "../components/page/Page";
import {useTranslation} from "react-i18next";
import {SearchInput} from "../components/searchinput/SearchInput";
import {SearchResultRow} from "./SearchResultRow";
import Toaster from "../util/toaster/Toaster";
import Api from "../util/api/Api";
import {HelperFunctions} from "../util/HelperFunctions";
import {EmptyState} from "../components/emptystate/EmptyState";
import {Redirect} from "react-router-dom";
import {StorageKey, useAppStorageState} from "../util/AppStorage";

function Search(props) {
    const {t} = useTranslation();
    const [currentQuery, setCurrentQuery] = useState(props.match.params.searchQuery ?? '');
    const [searchResults, setSearchResults] = useState([]);
    const [totalCount, setTotalCount] = useState(0);
    const debouncedQueryChange = HelperFunctions.debounce(setCurrentQuery)
    const [user] = useAppStorageState(StorageKey.USER);

    useEffect(() => {
        if (currentQuery && currentQuery.length > 0) {
            search(currentQuery)
        } else {
            setSearchResults([])
            setTotalCount(0)
        }
    }, [currentQuery]);

    if (user === null) {
        return <Redirect to={'unauthorized?redirect=search'}/>
    }

    function SearchResultsList() {
        if (searchResults.length === 0) {
            return (
                <div className={"search-results-list"}>
                    <EmptyState/>
                </div>
            )
        } else {
            return (
                <div className={"search-results-list"}>
                    {
                        searchResults.map((searchResult) => {
                            switch (searchResult.type.toLowerCase()) {
                                case "group":
                                    return <SearchResultRow {...searchResult}
                                                            key={searchResult.id}
                                                            history={props.history}
                                                            subtitle={t("search.group")}/>
                                case "repoitem":
                                    return <SearchResultRow {...searchResult}
                                                            key={searchResult.id}
                                                            history={props.history}
                                                            subtitle={t('search.' + searchResult.repoType.toLowerCase())}/>
                                case "person":
                                    return <SearchResultRow {...searchResult}
                                                            key={searchResult.id}
                                                            history={props.history}
                                                            title={searchResult.name}
                                                            subtitle={t("search.user")}/>
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
        <div className={"search-page-content"}>
            <div className={"title-row"}>
                <h1>{t("search.title")}</h1>
                <div className={"search-results-count-container"}>
                    <div className={"search-results-count"}>
                        {totalCount}
                    </div>
                </div>
            </div>
            <SearchInput placeholder={t("navigation_bar.search")}
                         defaultValue={props.match.params.searchQuery}
                         onChange={(e) => {
                             debouncedQueryChange(e.target.value)
                         }}/>
            <SearchResultsList/>
        </div>
    );

    const oldHistoryPush = props.history.push;
    props.history.push = (url) => {
        oldHistoryPush(url)
        window.location.reload()
        props.history.push = oldHistoryPush
    }

    return <Page id="search"
                 history={props.history}
                 activeMenuItem={"organisation"}
                 content={content}
                 breadcrumbs={[
                     {
                         path: '../dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         title: 'navigation_bar.search'
                     }
                 ]}
                 showBackButton={true}/>;

    function search(searchQuery) {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            params: {
                'fields[repoItems]': 'title,repoType',
                'fields[groups]': 'title',
                'fields[persons]': 'name',
                'filter[query]': searchQuery,
                'filter[isRemoved]': 0,
                'page[number]': 1,
                'page[size]': 50,
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
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        }

        Api.get('search', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

export default Search;