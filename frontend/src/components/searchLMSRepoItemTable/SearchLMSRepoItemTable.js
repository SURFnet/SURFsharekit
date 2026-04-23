import {useTranslation} from "react-i18next";
import React, {useCallback, useEffect, useRef, useState} from "react";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";
import '../field/formfield.scss';
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import {ReactTable, ReactTableLoadingIndicator} from "../reacttable/reacttable/ReactTable";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faSearch} from "@fortawesome/free-solid-svg-icons";
import {HelperFunctions} from "../../util/HelperFunctions";
import {useNavigation} from "../../providers/NavigationProvider";
import axios from "axios";
import styled from "styled-components";

const EmptySearchString = styled.h4`
    text-align: center;
    padding-top: 30px;
`

const LmsTitle = styled.div`
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
    line-height: 1.2em; 
`;


function SearchLMSRepoItemTable(props) {
    const [user] = useAppStorageState(StorageKey.USER);
    const [lmsItems, setLmsItems] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [query, setQuery] = useState('');
    const {t} = useTranslation();
    const navigate = useNavigation();
    const cancelToken = useRef();

    const selectedLMSItemsNotEmptyOrNull = props.selectedLMSItems && props.selectedLMSItems.length > 0;

    const columns = React.useMemo(
        () => [
            {
                accessor: 'isSelected',
                className: 'lmsitem-row-image',
                disableLink: true,
                Cell: (tableInfo) => {
                    return (
                        <div className="radio square-radio">
                            <div className="option">
                                <input
                                    id={tableInfo.row.original.id + 'is-selected'}
                                    disabled={props.readonly}
                                    defaultChecked={selectedLMSItemsNotEmptyOrNull &&
                                        props.selectedLMSItems.find(selectedItem =>
                                            tableInfo.row.original.id === selectedItem.id
                                        )}
                                    onChange={() => props.onLMSItemSelect(tableInfo.row.original)}
                                    type="radio"
                                />
                                <label htmlFor={tableInfo.row.original.id + 'is-selected'}>{"\u00a0"}</label>
                            </div>
                        </div>
                    );
                }
            },
            {
                accessor: 'title',
                className: 'lmsitem-row-title',
                disableLink: true,
                Cell: (tableInfo) => <LmsTitle>{tableInfo.cell.value}</LmsTitle>,
                style: { width: "50%", fontWeight: "bold" }
            },
            {
                accessor: 'authors[0].fullName',
                className: 'lmsitem-row-author',
                disableLink: true,
                Cell: (tableInfo) => <div>{tableInfo.cell.value}</div>,
                style: { width: "50%" }
            },
            {
                accessor: 'authors[0].institute',
                className: 'lmsitem-row-institute',
                disableLink: true,
                Cell: (tableInfo) => <div>{tableInfo.cell.value}</div>,
                style: { width: "50%" }
            }
        ],
        [props.selectedLMSItems, lmsItems, props.multiSelect, props.readonly]
    );

    const handleSort = useCallback(sortBy => {
        console.log("Sort By Called = ", sortBy);
    }, []);

    const onRowClick = useCallback((row) => {
        props.onLMSItemSelect(row.original);
    }, [props.onLMSItemSelect]);

    useEffect(() => {
        if (query !== '') {
            getLMSItems();
            return () => {
                if (cancelToken.current) {
                    cancelToken.current.cancel('Operation canceled due to component unmount');
                }
            };
        }
    }, [query]);

    const debouncedQueryChange = useCallback(
        HelperFunctions.debounce(setQuery),
        []
    );

    if (!user) {
        return null;
    }

    return (
        <div className="lmsitem-search flex-column">
            <div className="form-field-container query-row">
                <div className="form-field search-left">
                    <FontAwesomeIcon icon={faSearch} />
                    <input
                        type="text"
                        className="field-input text"
                        placeholder={t('action.search')}
                        onChange={(e) => debouncedQueryChange(e.target.value)}
                    />
                </div>
            </div>
            { query === '' ?
                <EmptySearchString>{t("add_publication.popup.lms.empty_search")}</EmptySearchString>
                :
                <ReactTable
                    tableName="lmsitems-table"
                    columns={columns}
                    tableRows={lmsItems}
                    onReloadData={handleSort}
                    loadingIndicator={<ReactTableLoadingIndicator loadingText={t('loading_indicator.loading_text')}/>}
                    emptyState={<div className="empty-message">{t('error_message.empty_search')}</div>}
                    isLoading={isLoading}
                    onRowClick={onRowClick}
                />
            }
        </div>
    );

    function getLMSItems() {
        setLmsItems([]);
        setIsLoading(true);

        const config = {
            params: {
                'filter[search]': query,
                'institute': props.instituteUuid,
                ...props.defaultParams
            }
        };

        if (props.filters) {
            config.params = {
                ...config.params,
                ...props.filters
            };
        }

        if (cancelToken.current) {
            cancelToken.current.cancel('Operation canceled due to new request');
        }
        config.cancelToken = cancelToken.current;
        console.log(config.cancelToken);
        cancelToken.current = Api.jsonApiGet('lms/items', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        const errorCallback = (error) => {
            if (axios.isCancel(error)) {
                return;
            }
            setIsLoading(false);
            Toaster.showServerError(error);
        }

        function onValidate(response) {
            // Add any validation logic here if needed
        }

        function onSuccess(response) {
            setIsLoading(false);
            setLmsItems(response.data);
        }

        function onServerFailure(error) {
            errorCallback(error)
            if (error?.response?.status === 401) {
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            errorCallback(error)
            console.error('Local failure:', error);
        }
    }
}

export default SearchLMSRepoItemTable;