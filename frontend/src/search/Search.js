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
import {Navigate, useParams, useNavigate} from "react-router-dom";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import useDocumentTitle from "../util/useDocumentTitle";

function Search(props) {
    const {t} = useTranslation();
    const params = useParams();
    const navigate = useNavigate();
    const [currentQuery, setCurrentQuery] = useState(params.searchQuery ?? '');
    const [searchResults, setSearchResults] = useState([]);
    const [totalCount, setTotalCount] = useState(0);
    const debouncedQueryChange = HelperFunctions.debounce(setCurrentQuery)
    const [user] = useAppStorageState(StorageKey.USER);

    useDocumentTitle("Search")

    useEffect(() => {
        if (currentQuery && currentQuery.length > 0) {
            search(currentQuery)
        } else {
            setSearchResults([])
            setTotalCount(0)
        }
    }, [currentQuery]);

    if (user === null) {
        return <Navigate to={'login?redirect=search'}/>
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
                                                            subtitle={t("search.group")}/>
                                case "repoitem":
                                    return <SearchResultRow {...searchResult}
                                                            key={searchResult.id}
                                                            subtitle={t('search.' + searchResult.repoType.toLowerCase())}/>
                                case "person":
                                    return <SearchResultRow {...searchResult}
                                                            key={searchResult.id}
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
                         defaultValue={params.searchQuery}
                         onChange={(e) => {
                             debouncedQueryChange(e.target.value)
                         }}/>
            <SearchResultsList/>
        </div>
    );

    return <Page id="search"
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
                 showBackButton={true}
                 onNavigate={(url) => navigate(url)}/>;

    function search(searchQuery) {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            params: {
                'fields[repoItems]': 'title,repoType',
                'fields[groups]': 'title,labelNL,labelEN',
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

        const errorCallback = (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        }

        function onLocalFailure(error) {
            errorCallback(error);
        }

        function onServerFailure(error) {
            errorCallback(error);
        }

        Api.get('search', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

export default Search;
