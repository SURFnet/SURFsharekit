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
import {faFileInvoice, faPaperclip, faSearch} from "@fortawesome/free-solid-svg-icons";
import {HelperFunctions} from "../../util/HelperFunctions";
import {ProfileBanner} from "../profilebanner/ProfileBanner";
import {useHistory} from "react-router-dom";
import axios from "axios";
import {SwalRepoItemPopup} from "../field/relatedrepoitempopup/RelatedRepoItemPopup";

function SearchRepoItemTable(props) {
    const [user] = useAppStorageState(StorageKey.USER);
    const [repoItems, setRepoItems] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const {t} = useTranslation();
    const [query, setQuery] = useState('');
    const tableRows = repoItems;
    const history = useHistory()
    const cancelToken = useRef();

    const selectedRepoItemsNotEmptyOrNull = props.selectedRepoItems && props.selectedRepoItems.length > 0;

    const columns = React.useMemo(
        () => [
            {
                accessor: 'isSelected',
                className: 'repoitem-row-image',
                disableLink: true,
                Cell: (tableInfo) => {
                    if(props.multiSelect) {
                        return <div className={"checkbox"}>
                            <input
                                id={tableInfo.row.original.id + 'is-selected'}
                                defaultChecked={selectedRepoItemsNotEmptyOrNull && props.selectedRepoItems.find(selectedRepoItem => tableInfo.row.original.id === selectedRepoItem.id)}
                                onChange={(e) => {
                                    props.onRepoItemSelect(tableInfo.row.original)
                                }}
                                type="checkbox"/>
                            <label htmlFor={tableInfo.row.original.id + 'is-selected'}>{"\u00a0"}</label>
                        </div>
                    } else {
                        return (
                            <div className={"field-input radio"}>
                                <div className="option">
                                    <input
                                        id={tableInfo.row.original.id + 'is-selected'}
                                        disabled={props.readonly}
                                        defaultChecked={selectedRepoItemsNotEmptyOrNull && props.selectedRepoItems.find(selectedRepoItem => tableInfo.row.original.id === selectedRepoItem.id)}
                                        onChange={(e) => {
                                            props.onRepoItemSelect(tableInfo.row.original)
                                        }}
                                        type="radio"/>
                                    <label htmlFor={tableInfo.row.original.id + 'is-selected'}>{"\u00a0"}</label>
                                </div>
                            </div>
                        )
                    }
                }
            },
            {
                accessor: 'imageURL',
                className: 'repoitem-row-image',
                disableLink: true,
                Cell: (tableInfo) => {
                    switch (props.repoType) {
                        case "RepoItemResearchObject":
                            return <ProfileBanner icon={faFileInvoice}/>
                            break;
                        case "RepoItemLearningObject":
                            return <ProfileBanner icon={faFileInvoice}/>
                            break;
                        default:
                            return <ProfileBanner imageUrl={undefined}/>
                            break;
                    }
                }
            },
            {
                accessor: 'title',
                className: 'repoitem-row-title',
                disableLink: true,
                style: {
                    width: "50%"
                }
            },
            {
                accessor: 'authorName',
                className: 'repoitem-row-author',
                disableLink: true,
                Cell: (tableInfo) => {
                    return <div>{tableInfo.cell.value}</div>
                },
                style: {
                    width: "50%"
                }
            }
        ],
        [props.selectedRepoItems, repoItems]
    )

    const handleSort = useCallback(sortBy => {
        console.log("Sort By Called = ", sortBy)
    }, [])

    const reactTableLoadingIndicator = <ReactTableLoadingIndicator loadingText={t('loading_indicator.loading_text')}/>;
    const onRowClick = (row) => {
        props.onRepoItemSelect(row.original)
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
                            disabled={!selectedRepoItemsNotEmptyOrNull}
                            onClick={() => {
                                if(props.multiSelect && props.selectedRepoItems.length > 1) {
                                    props.selectNextStep()
                                    createExtraRepoItems()
                                } else {
                                    props.selectNextStep()
                                }
                            }}/>
            </div>}
        </div>

    )


    function createExtraRepoItems() {
        // create extra RepoItemPerson objects, there was already 1 created when opening the relatedRepoItemPopup
        // so create (props.selectedPersons.length - 1) repoItems

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const postData = {
            "data": {
                "type": "repoItem",
                "attributes": {
                    "repoType": props.repoType
                },
                "relationships": {
                    "relatedTo": {
                        "data": {
                            "type": "institute",
                            "id": props.repoItemId
                        }
                    }
                }
            }
        };
        const requestList = []
        for(let i = 0; i < (props.selectedRepoItems.length - 1); i++) {
            requestList.push(axios.post("repoItems", postData, Api.getRequestConfig(config)))
        }
        Promise.all(requestList).then(axios.spread((...responses) => {
            const repoItemList = responses.map((response) => {
                return Api.dataFormatter.deserialize(response.data);
            })
            props.setAdditionalRepoItems(repoItemList)
        })).catch(errors => {
            SwalRepoItemPopup.close()
            Toaster.showDefaultRequestError()
        })
    }

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