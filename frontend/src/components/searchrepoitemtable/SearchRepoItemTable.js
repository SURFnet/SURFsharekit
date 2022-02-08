import {useTranslation} from "react-i18next";
import React, {useCallback, useEffect, useRef, useState} from "react";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";
import './searchrepoitemtable.scss'
import '../field/formfield.scss'
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import ButtonText from "../buttons/buttontext/ButtonText";
import {ReactTable, ReactTableLoadingIndicator} from "../reacttable/reacttable/ReactTable";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faSearch} from "@fortawesome/free-solid-svg-icons";
import {HelperFunctions} from "../../util/HelperFunctions";
import {ProfileBanner} from "../profilebanner/ProfileBanner";
import {useHistory} from "react-router-dom";

function SearchRepoItemTable(props) {
    const [user] = useAppStorageState(StorageKey.USER);
    const [repoItems, setRepoItems] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const {t} = useTranslation();
    const [query, setQuery] = useState('');
    const tableRows = repoItems;
    const [selectedRepoItem, setSelectedRepoItem] = useState(null);
    const history = useHistory()
    const cancelToken = useRef();

    useEffect(() => {
        if (props.hideNextButton) {
            props.setSelectedRepoItem(selectedRepoItem)
        }
    }, [selectedRepoItem])

    const columns = React.useMemo(
        () => [
            {
                accessor: 'imageURL',
                className: 'repoitem-row-image',
                Cell: (tableInfo) => {
                    return <ProfileBanner imageUrl={undefined}/>
                }
            },
            {
                accessor: 'isSelected',
                className: 'repoitem-row-image',
                Cell: (tableInfo) => {
                    return <div className={"checkbox"}>
                        <input
                            id={tableInfo.row.original.id + 'is-selected'}
                            defaultChecked={selectedRepoItem !== null && tableInfo.row.original.id === selectedRepoItem.id}
                            type="checkbox"/>
                        <label htmlFor={tableInfo.row.original.id + 'is-selected'}>{"\u00a0"}</label>
                    </div>
                }
            },
            {
                accessor: 'title',
                className: 'repoitem-row-title',
                style: {
                    width: "50%"
                }
            },
            {
                accessor: 'authorName',
                className: 'repoitem-row-author',
                Cell: (tableInfo) => {
                    return <div>{tableInfo.cell.value}</div>
                },
                style: {
                    width: "50%"
                }
            }
        ],
        [selectedRepoItem, repoItems]
    )

    const handleSort = useCallback(sortBy => {
        console.log("Sort By Called = ", sortBy)
    }, [])

    const reactTableLoadingIndicator = <ReactTableLoadingIndicator loadingText={t('loading_indicator.loading_text')}/>;
    const onRowClick = (row) => {
        if (selectedRepoItem && row.original.id === selectedRepoItem.id) {
            setSelectedRepoItem(null)
        } else {
            setSelectedRepoItem(row.original)
        }
    }

    useEffect(() => {
        getRepoItems();
    }, [query]);

    if (!user) {
        return null
    }

    const debouncedQueryChange = HelperFunctions.debounce(setQuery)

    return (
        <div className={'repoitem-search flex-column'}>
            <div className={'form-field-container query-row'}>
                <div className={'form-field'}>
                    <div className="form-row">
                        <input type="text"
                               className={"field-input text"}
                               placeholder={t('action.search')}
                               onChange={(e) => {
                                   debouncedQueryChange(e.target.value)
                               }}
                        />
                        <FontAwesomeIcon icon={faSearch}/>
                    </div>
                </div>
            </div>
            <ReactTable
                tableName={'repoitems-table'}
                columns={columns}
                tableRows={tableRows}
                onReloadData={handleSort}
                loadingIndicator={reactTableLoadingIndicator}
                emptyState={<div className={'empty-message'}>{t('error_message.empty_search')}</div>}
                isLoading={isLoading}
                onRowClick={onRowClick}
            />
            {!props.hideNextButton && <div className={"save-button-wrapper"}>
                <ButtonText text={props.buttonText ?? t('repoitem.popup.next')}
                            buttonType={"callToAction"}
                            disabled={!selectedRepoItem}
                            onClick={() => {
                                props.setSelectedRepoItem(selectedRepoItem)
                            }}/>
            </div>}
        </div>

    )

    function getRepoItems() {
        setRepoItems([])
        setIsLoading(true)

        const config = {
            params: {
                'fields[repoItems]': 'title,lastEdited,authorName',
                'filter[title][LIKE]': '%' + query + '%',
                'page[number]': '1',
                'page[size]': '20',
                'sort': 'title',
                ...props.defaultParams
            }
        };

        if (props.filters) {
            config.params = {
                ...config.params,
                ...props.filters
            }
        }
        config.cancelToken = cancelToken.current;
        console.log(config.cancelToken);
        cancelToken.current = Api.jsonApiGet('repoItems', onValidate, onSuccess, onLocalFailure, onServerFailure, config);


        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsLoading(false)
            setRepoItems(response.data);
        }

        function onServerFailure(error) {
            console.log(error);
            setIsLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            setIsLoading(false);
            Toaster.showDefaultRequestError();
            console.log(error);
        }
    }
}

export default SearchRepoItemTable;