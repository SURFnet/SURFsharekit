import {useTranslation} from "react-i18next";
import React, {useCallback, useEffect, useState} from "react";
import './grouppersontable.scss'
import '../../components/field/formfield.scss'
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import AddPersonToGroupPopup from "../addpersontogrouppopup/AddPersonToGroupPopup";
import PersonTableWithSearch from "../../components/persontablewithsearch/PersonTableWithSearch";
import VerificationPopup from "../../verification/VerificationPopup";
import {useHistory} from "react-router-dom";

function GroupPersonTable(props) {
    const [sortOrder, setSortOrder] = useState([]);
    const [persons, setPersons] = useState([]);
    const [query, setQuery] = useState('');
    const [pageCount, setPageCount] = useState(1);
    const [pageNumber, setPageNumber] = useState(1);
    const [isLoading, setIsLoading] = useState(true);
    const {t} = useTranslation();
    const personCount = props.group ? props.group.amountOfPersons : 0
    const history = useHistory()

    const onReloadData = useCallback((sortBy, pageIndex, pageCount) => {
        setPageNumber(pageIndex + 1)
        if (sortBy && sortBy.length > 0) {
            if (['name', 'hasLoggedIn'].indexOf(sortBy[0].id) !== -1) {
                setSortOrder([...sortBy])
            }
        }
    }, [])

    useEffect(() => {
        setPageNumber(1)
        getPersons()
    }, [query])

    useEffect(() => {
        getPersons()
    }, [pageNumber, sortOrder])

    return (
        <div className={'group-person flex-column'}>
            <div className={'title'}>
                <h2>{t('group.members')}</h2>
                <div className={"search-count"}>{personCount}</div>
            </div>
            <PersonTableWithSearch
                onReload={onReloadData}
                onQueryChange={setQuery}
                sortOrder={sortOrder}
                persons={persons}
                allowSearch={true}
                pageCount={pageCount}
                showDelete={true}
                history={props.history}
                canEdit={props.group && props.group.userPermissions.canEdit}
                showAddElement={props.group && props.group.userPermissions.canEdit}
                isLoading={isLoading}
                onCreatePerson={showAddPersonToGroupPopup}
                onDeletePerson={askRemovePersonFromGroup}
            />
        </div>
    )

    function askRemovePersonFromGroup(person) {
        VerificationPopup.show(t("group.remove_popup.title"), t("group.remove_popup.subtitle"), () => {
            removePersonFromGroup(person);
        })
    }

    function removePersonFromGroup(person) {
        setIsLoading(true)

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            },
            data: {
                data: [
                    {
                        type: 'group',
                        id: props.group.id
                    }
                ]
            }
        }
        Api.delete('persons/' + person.id + '/groups', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsLoading(false)
            setPageNumber(1)
            Toaster.showToaster({type: 'info', message: t("group.remove_person_success_message")})
            getPersons()
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

    function getPersons() {
        setPersons([])
        setIsLoading(true)

        const config = {
            params: {
                'fields[persons]': 'name,imageURL,primaryRole,primaryInstitute,hasLoggedIn,permissions,groupCount',
                'filter[group]': props.group.id,
                'filter[search]': query,
                'filter[isRemoved]': false,
                'page[number]': pageNumber,
                'page[size]': 10,
            }
        };

        if (sortOrder.length > 0) {
            config.params.sort = sortOrder.map(sort => (sort.desc ? '-' : '') + sort.id).join(',')
        }

        Api.jsonApiGet('personSummaries', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            setPageCount(parseInt(new URLSearchParams(response.links.last).get("page[number]")))
            setIsLoading(false)
            setPersons(response.data);
            props.reloadGroup()
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

    function showAddPersonToGroupPopup() {
        const onPersonAdded = (onPersonAdded) => {
            getPersons()
            Toaster.showToaster({type: 'info', message: t("group.add_person_success_message")})
            props.reloadGroup()
        }

        const onCancelPopup = () => {
        }

        AddPersonToGroupPopup.show(props.group, history, onPersonAdded, onCancelPopup)

    }
}

export default GroupPersonTable;