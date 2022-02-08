import {useTranslation} from "react-i18next";
import React, {useCallback, useEffect, useRef, useState} from "react";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";
import './searchandselectpersontable.scss'
import StatusIcon from "../statusicon/StatusIcon";
import '../field/formfield.scss'
import {ReactTable, ReactTableLoadingIndicator} from "../reacttable/reacttable/ReactTable";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faSearch} from "@fortawesome/free-solid-svg-icons";
import {HelperFunctions} from "../../util/HelperFunctions";
import {ProfileBanner} from "../profilebanner/ProfileBanner";
import Toaster from "../../util/toaster/Toaster";
import ButtonText from "../buttons/buttontext/ButtonText";
import Api from "../../util/api/Api";
import {InputField} from "../field/FormField";
import {roleKeyToTranslationKey} from "../../util/MemberPositionOptionsHelper";
import {useHistory} from "react-router-dom";

function SearchAndSelectPersonTable(props) {
    const [user] = useAppStorageState(StorageKey.USER);
    const [persons, setPersons] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [searchInInstitute, setSearchInInstitute] = useState(true);
    const [instituteToSearchIn, setInstituteToSearchIn] = useState(null);
    const {t} = useTranslation();
    const [query, setQuery] = useState('');
    const tableRows = persons;
    const [selectedPerson, setSelectedPerson] = useState(null);
    const history = useHistory()
    const cancelToken = useRef();

    useEffect(() => {
        if (searchInInstitute) {
            setInstituteToSearchIn(null)
        }
    }, [searchInInstitute])

    const columns = React.useMemo(
        () => [
            {
                accessor: 'imageURL',
                className: 'person-row-image',
                Cell: (tableInfo) => {
                    return <ProfileBanner imageUrl={undefined} onClick={() => onRowClick(tableInfo.row)}/>
                }
            },
            {
                accessor: 'isSelected',
                className: 'person-row-image',
                Cell: (tableInfo) => {
                    return <fieldset className={"field-input radio"}>
                        <div className="option">
                            <input
                                defaultChecked={selectedPerson !== null && tableInfo.row.original.id === selectedPerson.id}
                                type="radio"
                                id={tableInfo.row.original.id + 'is-selected'}
                                disabled={props.readonly}
                                onChange={(e) => {
                                    if (selectedPerson && tableInfo.row.original.id === selectedPerson.id) {
                                        setSelectedPerson(null)
                                    } else {
                                        setSelectedPerson(tableInfo.row.original)
                                    }
                                }}
                                name={props.name}/>
                            <label htmlFor={tableInfo.row.original.id + 'is-selected'}>{"\u00a0"}</label>
                        </div>
                    </fieldset>
                }
            },
            {
                accessor: 'name',
                className: 'person-row-name',
                Cell: (tableInfo) => {
                    return <div onClick={() => onRowClick(tableInfo.row)}>{tableInfo.cell.value}</div>
                },
                style: {
                    width: "25%"
                }
            },
            {
                accessor: 'position',
                className: 'person-row-role',
                Cell: (tableInfo) => {
                    return <div
                        onClick={() => onRowClick(tableInfo.row)}>{t(roleKeyToTranslationKey[tableInfo.cell.value] ?? tableInfo.cell.value)}</div>
                },
                style: {
                    width: "25%"
                }
            },
            {
                accessor: 'institutes',
                className: 'person-row-institute',
                Cell: (tableInfo) => {
                    return <div
                        onClick={() => onRowClick(tableInfo.row)}>{tableInfo.cell.value.reduce((total, cv) => total + (total === '' ? '' : ', ') + cv, '')}</div>
                },
                style: {
                    width: "25%"
                }
            },
            {
                accessor: 'hasLoggedIn',
                className: 'person-row-active-state',
                Cell: (tableInfo) => {
                    if (tableInfo.cell.value) {
                        return <StatusIcon color='purple' text={t('person.active')}
                                           onClick={() => onRowClick(tableInfo.row)}/>
                    } else {
                        return <StatusIcon color='red' text={t('person.inactive')}
                                           onClick={() => onRowClick(tableInfo.row)}/>
                    }
                }
            }
        ],
        [selectedPerson, persons]
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
            />
            <div className={"save-button-wrapper"}>
                <ButtonText text={props.buttonText ?? t('repoitem.popup.next')}
                            buttonType={"callToAction"}
                            disabled={!selectedPerson}
                            onClick={() => {
                                props.setSelectedPerson(selectedPerson)
                            }}/>
            </div>
        </div>

    )

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
                'sort': '-name',
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

export default SearchAndSelectPersonTable;