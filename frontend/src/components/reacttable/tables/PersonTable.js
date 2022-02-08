import React, {useEffect, useRef, useState} from "react";
import '../../field/formfield.scss'
import PersonTableWithSearch from "../../../components/persontablewithsearch/PersonTableWithSearch";
import {useHistory} from "react-router-dom";
import './persontable.scss'
import Api from "../../../util/api/Api";
import Toaster from "../../../util/toaster/Toaster";

function PersonTable(props) {
    const [sortOrder, setSortOrder] = useState([]);
    const [persons, setPersons] = useState([]);
    const [query, setQuery] = useState('');
    const [searchOutsideOfScope, setSearchOutsideOfScope] = useState(false);
    const [pageCount, setPageCount] = useState(1);
    const [pageNumber, setPageNumber] = useState(1);
    const [isLoading, setIsLoading] = useState(true);
    const history = useHistory()
    const cancelToken = useRef();

    const onReloadData = (sortBy, pageIndex) => {
        setPageNumber(pageIndex + 1)
        setSortOrder([...sortBy])
    }

    useEffect(() => {
        getPersons()
    }, [query, pageNumber, sortOrder, searchOutsideOfScope])

    return (
        <div className={'group-person flex-column'}>
            <PersonTableWithSearch
                onReload={onReloadData}
                onQueryChange={(q, s) => {
                    setQuery(q);
                    setSearchOutsideOfScope(s)
                }}
                sortOrder={sortOrder}
                persons={persons}
                pageCount={pageCount}
                history={props.history}
                canEdit={props.institute && props.institute.permissions.canEdit}
                showDelete={!props.hideDelete}
                showAddElement={false}
                allowSearch={true}
                allowOutsideScope={true}
                isLoading={isLoading}
            />
        </div>
    )

    function getPersons() {
        setPersons([])
        setIsLoading(true)

        const config = {
            params: {
                'filter[search]': query,
                'page[number]': pageNumber,
                'filter[scope]': searchOutsideOfScope ? "off" : "on",
                'filter[isRemoved]': false,
                'page[size]': 10,
            }
        };

        if (sortOrder.length > 0) {
            config.params.sort = sortOrder.map(sort => (sort.desc ? '-' : '') + sort.id).join(',')
        }
        config.cancelToken = cancelToken.current;
        console.log(config.cancelToken);
        cancelToken.current = Api.jsonApiGet('personSummaries', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            setPageCount(parseInt(new URLSearchParams(response.links.last).get("page[number]")))
            setIsLoading(false)
            setPersons(response.data);
            if (props.onTableFiltered) {
                props.onTableFiltered({
                    query: query,
                    count: response.meta.totalCount
                })
            }
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

export default PersonTable;