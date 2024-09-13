import {useTranslation} from "react-i18next";
import React, {useCallback, useEffect, useRef, useState} from "react";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";
import './searchandselectpersontable.scss'
import StatusIcon from "../statusicon/StatusIcon";
import '../field/formfield.scss'
import {ReactTable, ReactTableLoadingIndicator} from "../reacttable/reacttable/ReactTable";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faSearch, faTimesCircle} from "@fortawesome/free-solid-svg-icons";
import {HelperFunctions} from "../../util/HelperFunctions";
import {ProfileBanner} from "../profilebanner/ProfileBanner";
import Toaster from "../../util/toaster/Toaster";
import ButtonText from "../buttons/buttontext/ButtonText";
import Api from "../../util/api/Api";
import {InputField} from "../field/FormField";
import {roleKeyToTranslationKey} from "../../util/MemberPositionOptionsHelper";
import {useHistory} from "react-router-dom";
import styled from "styled-components";
import {cultured, majorelle, openSans} from "../../Mixins";
import {GlobalPageMethods} from "../page/Page";
import ValidationError from "../../util/ValidationError";
import axios from "axios";
import {SwalRepoItemPopup} from "../field/relatedrepoitempopup/RelatedRepoItemPopup";

function SearchAndSelectPersonTable(props) {
    const [user] = useAppStorageState(StorageKey.USER);
    const [persons, setPersons] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [searchInInstitute, setSearchInInstitute] = useState(true);
    const [instituteToSearchIn, setInstituteToSearchIn] = useState(null);
    const {t} = useTranslation();
    const [query, setQuery] = useState('');
    const tableRows = persons;
    const history = useHistory();
    const cancelToken = useRef();

    const selectedPersonsNotEmptyOrNull = props.selectedPersons && props.selectedPersons.length > 0;

    useEffect(() => {
        if (searchInInstitute) {
            setInstituteToSearchIn(null)
        }
    }, [searchInInstitute])

    const columns = React.useMemo(
        () => [
            {
                accessor: 'isSelected',
                className: 'person-row-image',
                disableLink: true,
                Cell: (tableInfo) => {
                    if(props.multiSelect) {
                        return <fieldset className={"field-input checkbox "}>
                            <div className="option">
                                <input
                                    defaultChecked={selectedPersonsNotEmptyOrNull && props.selectedPersons.find(person => tableInfo.row.original.id === person.id)}
                                    type="checkbox"
                                    id={tableInfo.row.original.id + 'is-selected'}
                                    disabled={props.readonly}
                                    onChange={(e) => {
                                        props.onPersonSelect(tableInfo.row.original)
                                    }}
                                    name={props.name}
                                />
                                <label htmlFor={tableInfo.row.original.id + 'is-selected'}>{"\u00a0"}</label>
                            </div>
                        </fieldset>
                    } else {
                        return <fieldset className={"field-input radio"}>
                            <div className="option">
                                <input
                                    defaultChecked={selectedPersonsNotEmptyOrNull && props.selectedPersons.find(person => tableInfo.row.original.id === person.id)}
                                    type="radio"
                                    id={tableInfo.row.original.id + 'is-selected'}
                                    disabled={props.readonly}
                                    onChange={(e) => {
                                        props.onPersonSelect(tableInfo.row.original)
                                    }}
                                    name={props.name}
                                />
                                <label htmlFor={tableInfo.row.original.id + 'is-selected'}>{"\u00a0"}</label>
                            </div>
                        </fieldset>
                    }
                }
            },
            {
                accessor: 'imageURL',
                className: 'person-row-image',
                disableLink: true,
                Cell: (tableInfo) => {
                    return <ProfileBanner imageUrl={undefined} onClick={(e) => {
                                e.stopPropagation()
                                onRowClick(tableInfo.row)
                            }
                    }/>
                }
            },
            {
                accessor: 'name',
                className: 'person-row-name',
                disableLink: true,
                Cell: (tableInfo) => {
                    return <div>{tableInfo.cell.value}</div>
                },
                style: {
                    width: "25%"
                }
            },
            {
                accessor: 'position',
                className: 'person-row-role',
                disableLink: true,
                Cell: (tableInfo) => {
                    return <div>{t(roleKeyToTranslationKey[tableInfo.cell.value] ?? tableInfo.cell.value)}</div>
                },
                style: {
                    width: "25%"
                }
            },
            {
                accessor: 'institutes',
                className: 'person-row-institute',
                disableLink: true,
                Cell: (tableInfo) => {
                    return <div>{tableInfo.cell.value.reduce((total, cv) => total + (total === '' ? '' : ', ') + cv, '')}</div>
                },
                style: {
                    width: "25%"
                }
            },
            {
                accessor: 'hasLoggedIn',
                className: 'person-row-active-state',
                disableLink: true,
                Cell: (tableInfo) => {
                    if (tableInfo.cell.value) {
                        return <StatusIcon color='purple' text={t('person.active')}/>
                    } else {
                        return <StatusIcon color='red' text={t('person.inactive')}/>
                    }
                }
            }
        ],
        [props.selectedPersons, persons]
    )

    const handleSort = useCallback(sortBy => {
    }, [])

    const reactTableLoadingIndicator = <ReactTableLoadingIndicator loadingText={t('loading_indicator.loading_text')}/>;
    const onRowClick = (row) => {
        props.personClicked(row.original.id)
    }

    useEffect(() => {
        getPersons();
    }, [query, searchInInstitute, instituteToSearchIn]);

    if (!user) {
        return null
    }

    const debouncedQueryChange = HelperFunctions.debounce(setQuery)

    return (
        <>
            <div className={'person-search flex-column'}>
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
                    {props.allowOutsideScope !== false && <div className={"checkbox"}>
                        <div className="option">
                            <input
                                id={'search-outside-scope'}
                                // defaultChecked={false}
                                type="checkbox"
                                onChange={(e) => setSearchInInstitute(!e.target.checked)}/>
                            <label htmlFor={'search-outside-scope'}>
                                {t('repoitem.popup.searchoutsidescope')}
                            </label>
                        </div>
                    </div>
                    }
                </div>
                {
                    (props.allowOutsideScope !== false && !searchInInstitute)
                    && <div className={"flex-column form-field-container organisation-dropdown"}>
                        <InputField type={"institute"}
                                    setValue={(_, instituteID, __) => setInstituteToSearchIn(instituteID)}/>
                    </div>
                }
                <ReactTable
                    tableName={'persons-table'}
                    columns={columns}
                    tableRows={tableRows}
                    onReloadData={handleSort}
                    loadingIndicator={reactTableLoadingIndicator}
                    emptyState={<div className={'empty-message'}>{t('error_message.empty_search')}</div>}
                    isLoading={isLoading}
                    onRowClick={(row) => {
                        props.onPersonSelect(row.original)
                    }}
                />
                <div className={"save-button-wrapper"}>
                    <ButtonText text={props.buttonText ?? t('repoitem.popup.next')}
                                buttonType={"callToAction"}
                                disabled={!selectedPersonsNotEmptyOrNull}
                                onClick={ () => {
                                    if(props.multiSelect && props.selectedPersons.length > 1) {
                                        if(props.selectNextStep) {
                                            props.selectNextStep()
                                        }
                                        createExtraRepoItems()
                                    } else {
                                        if(props.onAddButtonClick) {
                                            props.onAddButtonClick()
                                        }
                                        if(props.selectNextStep) {
                                            props.selectNextStep()
                                        }
                                    }
                                }}/>
                </div>
            </div>
        </>
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
        for(let i = 0; i < (props.selectedPersons.length - 1); i++) {
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

    function getPersons() {
        setPersons([])
        setIsLoading(true)

        const config = {
            params: {
                'filter[search]': query,
                'filter[isRemoved]': false,
                'page[size]': '20',
                'page[number]': '1',
                'include': 'groups.partOf',
                'sort': 'name',
                ...props.defaultParams
            }
        };

        if (!searchInInstitute) {
            config.params['filter[scope]'] = 'off'
            if (instituteToSearchIn) {
                config.params['filter[institute]'] = instituteToSearchIn;
            }
        }
        config.cancelToken = cancelToken.current;
        console.log(config.cancelToken);
        cancelToken.current = Api.jsonApiGet('personSummaries', onValidate, onSuccess, onLocalFailure, onServerFailure, config);


        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsLoading(false)
            setPersons(response.data);
        }

        function onServerFailure(error) {
            setIsLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            setIsLoading(false);
            Toaster.showDefaultRequestError();
        }
    }
}

export const TagContainer = styled.div`
    width: 100%;
    margin: 20px 0 0 0;
    display: flex;
    min-height: 32px;
    flex-direction: row;
    flex-wrap: wrap;
    column-gap: 5px;
    row-gap: 5px;
`;

export const Tag = styled.div`
    background: ${majorelle};
    display: flex;
    flex-direction: row;
    align-items: center;
    padding: 8px 10px 8px 15px;
    border-radius: 50px;
    column-gap: 5px;
`;

export const TagName = styled.span`
    ${openSans}
    font-size: 12px;
    color: ${cultured};
`;

export const TagButton = styled(FontAwesomeIcon)`
    color: ${cultured};
    font-size: 14px;
    cursor: pointer;
    border-sizing: content-box;
`;

export default SearchAndSelectPersonTable;