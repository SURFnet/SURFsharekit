import {useTranslation} from "react-i18next";
import React, {useEffect, useState} from "react";
import './persontablewithsearch.scss'
import '../field/formfield.scss'
import StatusIcon from "../statusicon/StatusIcon";
import {ReactComponent as IconClaimPerson } from "../../resources/icons/claim-person-icon.svg";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faEdit, faPlus, faTrash, faExclamationCircle} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../buttons/iconbuttontext/IconButtonText";
import {HelperFunctions} from "../../util/HelperFunctions";
import {ReactTableSearchInput} from "../reacttable/filterrow/ReacTableFilterItems";
import {
    ReactTable,
    ReactTableEmptyState,
    ReactTableLoadingIndicator,
    ReactTableSortIcon
} from "../reacttable/reacttable/ReactTable";
import {ProfileBanner} from "../profilebanner/ProfileBanner";
import {roleKeyToTranslationKey} from "../../util/MemberPositionOptionsHelper";
import {ReactTableHelper} from "../../util/ReactTableHelper";
import MergeProfilePopup from "../../profile/mergeprofilespopup/MergeProfilesPopup";
import ClaimRequestPopup from "../../profile/claimrequest/ClaimRequestPopup";
import {useHistory} from "react-router-dom";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";

function PersonTableWithSearch(props) {
    const sortOrder = props.sortOrder
    const persons = props.persons
    const pageCount = props.pageCount
    const showDelete = props.showDelete
    const isLoading = props.isLoading
    const tableRows = persons;
    const [searchOutOfScope, setSearchOutOfScope] = useState(false);
    const [query, setQuery] = useState('');
    const {t} = useTranslation();
    const history = useHistory();
    const columns = React.useMemo(
        () => {
            return [
                {
                    Header: '',
                    accessor: 'imageURL',
                    className: 'person-row-image',
                    disableSortBy: true,
                    Cell: (tableInfo) => {
                        return <ProfileBanner imageUrl={undefined}/>
                    }
                },
                {
                    Header: () => {
                        return <div>
                            <span className={"border"}>{t('person.firstName')}</span>
                            <ReactTableSortIcon
                                sortOrder={sortOrder}
                                name={'firstName'}/>
                        </div>
                    },
                    accessor: 'firstName',
                    className: 'person-row-firstname',
                    Cell: (tableInfo) => {
                        return <div>{tableInfo.cell.value}</div>
                    },
                    style: {
                        width: "15%"
                    }
                },
                {
                    Header: () => {
                        return <div>
                            <span className={"border"}>{t('person.surname')}</span>
                            <ReactTableSortIcon sortOrder={sortOrder} name={'surname'}/>
                        </div>
                    },
                    accessor: 'surname',
                    className: 'person-row-surname',
                    Cell: (tableInfo) => {
                        return <div>{tableInfo.row.original.surname + (tableInfo.row.original.surnamePrefix ? ', ' + tableInfo.row.original.surnamePrefix : '')}</div>
                    },
                    style: {
                        width: "15%"
                    }
                },
                {
                    Header: () => {
                        return <div>
                            <span className={"border"}>{t('person.role')}</span>
                            <ReactTableSortIcon sortOrder={sortOrder} name={'position'}/>
                        </div>
                    },
                    accessor: 'position',
                    className: 'person-row-role',
                    Cell: (tableInfo) => {
                        return <div>{t(roleKeyToTranslationKey[tableInfo.cell.value] ?? tableInfo.cell.value)}</div>
                    },
                    style: {
                        width: "15%"
                    }
                },
                {
                    Header: () => {
                        return <div className={"border"}>{t('person.groups')}</div>
                    },
                    disableSortBy: true,
                    accessor: t('language.current_code') === 'nl' ? 'groupLabelsNL' : 'groupLabelsEN',
                    className: 'person-row-groups',
                    Cell: (tableInfo) => {
                        return <div>{ReactTableHelper.concatenateCellValue(tableInfo.cell.value)}</div>
                    },
                    style: {
                        width: "30%"
                    }
                },
                {
                    Header: () => {
                        return <div className={"border"}>{t('person.identifiers')}</div>
                    },
                    disableSortBy: true,
                    accessor: 'identifiers',
                    className: 'person-row-groups',
                    Cell: (tableInfo) => {
                        const values = [
                            tableInfo.row.original.persistentIdentifier,
                            tableInfo.row.original.orcid,
                            tableInfo.row.original.isni,
                            tableInfo.row.original.hogeschoolId
                        ].filter(value => value !== null && value !== "");
                        return <div>{values.join("\n")}</div>;
                    },
                    style: {
                        width: "20%"
                    }
                },
                {

                    Header: () => {
                        return <div>
                            <span className={"border"}>{t('person.status')} </span>
                            <ReactTableSortIcon
                                sortOrder={sortOrder}
                                name={'hasLoggedIn'}/>
                        </div>
                    },
                    accessor: 'hasLoggedIn',
                    className: 'person-row-active-state',
                    Cell: (tableInfo) => {
                        if (tableInfo.cell.value) {
                            return <StatusIcon color='purple' text={t('person.active')}/>
                        } else {
                            return <StatusIcon color='red' text={t('person.inactive')}/>
                        }
                    }
                },
                {
                    accessor: 'permissions',
                    className: 'person-row-actions',
                    disableSortBy: true,
                    disableLink: true,
                    style: {
                        width: "20%"
                    },
                    Cell: (tableInfo) => {
                        const permissions = tableInfo.cell.value
                        const canEdit = (permissions.canEdit)
                        const canDelete = (permissions.canEdit && props.canEdit && tableInfo.row.original.groupCount > 1)
                        return <div className={"flex-row"}>
                            {props.claimIconEnabled &&
                                <IconClaimPerson
                                    title={t("profile.claim_popup.tooltip")}
                                    onClick={(e) => {
                                        e.stopPropagation()
                                        const person = tableInfo.row.original.id
                                        const personInstitutes = tableInfo.row.original.rootInstitutesSummary
                                        ClaimRequestPopup.show(history, person, personInstitutes)
                                    }}
                                />
                            }
                            {showDelete && <FontAwesomeIcon icon={faTrash}
                                             className={`icon-trash${(canDelete) ? "" : " disabled"}`}
                                             onClick={(e) => {
                                                 e.stopPropagation()
                                                 if (canDelete) {
                                                     props.onDeletePerson(tableInfo.row.original)
                                                 }
                                             }}/>}
                            <FontAwesomeIcon icon={faEdit}
                                             className={`icon-trash${(canEdit) ? "" : " disabled"}`}
                                             onClick={(e) => {
                                                 e.stopPropagation()
                                                 if (canEdit) {
                                                     props.history.push('../profile/' + tableInfo.row.original.id)
                                                 }
                                             }}/>
                        </div>
                    }
                }
            ]
        },
        [persons, sortOrder, t]
    )

    useEffect(() => {
        props.onQueryChange(query, searchOutOfScope)
    }, [query, searchOutOfScope]);

    let debouncedQueryChange = HelperFunctions.debounce(setQuery)

    const addUserElement = <IconButtonText faIcon={faPlus} buttonText={t('person.add')} onClick={() => {
        props.onCreatePerson()
    }}/>


    const reactTableLoadingIndicator = <ReactTableLoadingIndicator loadingText={t('loading_indicator.loading_text')}/>;
    const onRowClick = (row) => {
        props.history.push('/profile/' + row.original.id)
    }

    return <div>
        {props.allowSearch &&
        <div className={'form-field-container react-table-filter-row query-row'}>
            <ReactTableSearchInput placeholder={t('action.search')}
                                   onChange={(e) => {
                                       debouncedQueryChange(e.target.value)
                                   }}
            />
            {props.allowOutsideScope === true &&
            <div className="checkbox">
                <div className="option">
                    <input
                        id={'search-outside-scope'}
                        // defaultChecked={false}
                        type="checkbox"
                        onChange={(e) => setSearchOutOfScope(e.target.checked)}/>
                    <label htmlFor={'search-outside-scope'}>
                        {t('repoitem.popup.searchoutsidescope')}
                    </label>
                </div>
            </div>}
            {props.showAddElement && addUserElement}
        </div>}
        <ReactTable
            tableName={'persons-table'}
            columns={columns}
            tableRows={tableRows}
            onReloadData={props.onReload}
            pageCount={pageCount}
            enablePagination={true}
            loadingIndicator={reactTableLoadingIndicator}
            emptyState={<ReactTableEmptyState title={t('profiles.profile_empty')} />}
            isLoading={isLoading}
            onRowClick={onRowClick}/>
    </div>

}

export default PersonTableWithSearch;