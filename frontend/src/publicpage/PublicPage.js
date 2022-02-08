import React, {useEffect, useState} from "react";
import './publicpage.scss'
import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import Toaster from "../util/toaster/Toaster";
import Api from "../util/api/Api";
import ValidationHelper from "../util/ValidationHelper";
import ValidationError from "../util/ValidationError";

function PublicPage(props) {
    const [isLoading, setIsLoading] = useState(false);
    const [pageData, setPageData] = useState(null);

    useEffect(() => {
        getPageData();
    }, []);

    const getAttributeList = () => {
        return Object.keys(pageData).map(key => {
            let pageRow;
            const data = pageData[key];

            if(ValidationHelper.exists(data)) {
                pageRow = <PublicPageRow label={key} value={data}/>
            }

            return pageRow;
        })
    }

    const content = (
        <div className={"public-page-content"}>
            <h2>SURFsharekit publication</h2>
            <div className={"data-container"}>
                {pageData && getAttributeList()}
            </div>
        </div>
    )

    const footer = (
        <div className={"public-page-footer-wrapper"}>
            <div className={"public-page-footer-container row with-margin"}>
                <div className={"footer-logo-container"}>
                    <div className={"footer-logo"}>
                        <img alt="Logo" id="public-page-logo" src={require('../resources/images/surf-sharekit-logo-fill.png')}/>
                    </div>
                </div>
                <div className={"footer-text-container"}>
                    <h4>SURFsharekit</h4>
                    <div className={"footer-text"}>
                        Dienst van HBO Kennisinfrastructuur (HKI)<br/>
                        Repositorydienst voor hogescholen<br/>
                        Gebaseerd op open standaarden volgens Edustandaard<br/>
                    </div>
                </div>
            </div>
        </div>
    )

    return (
        <div className={"public-page"}>
            <div className={"page-content-container"}>
                <div className={"page-wrapper"}>
                    <div className={"page-content row with-margin"}>
                        {content}
                    </div>
                    <LoadingIndicator
                        isLoading={isLoading}
                        centerInPage={true}/>
                </div>
                {footer}
            </div>
        </div>
    )

    function getPageData() {
        setIsLoading(true)

        function onValidate(response) {
            const repoItemData = response.data ? response.data : null;
            if (!(repoItemData && repoItemData.id)) {
                throw new ValidationError("The received repo item data is invalid")
            }
        }

        function onSuccess(response) {
            setIsLoading(false)
            //response.data['Identifier'] = response.data['id']
            delete response.data['type']
            delete response.data['id']
            setPageData(response.data)
        }

        function onLocalFailure(error) {
            setIsLoading(false)
            Toaster.showDefaultRequestError();
        }

        function onServerFailure(error) {
            setIsLoading(false)
            Toaster.showServerError(error)
        }

        const config = {
            baseURL: process.env.REACT_APP_JSON_API_URL
        }

        Api.jsonApiGet(`repoItems/${props.match.params.uuid}`, onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

export function PublicPageRow(props) {

    function valueRow(label, value) {
        return (
            <div id={`${label}-${value}`} className={"value-row"}>
                {value}
            </div>
        )
    }

    function subtitleRow(label, value) {
        return (
            <div id={`${label}-${value}`} className={"value-row value-row-subtitle"}>
                {value}
            </div>
        )
    }

    function urlRow(label, value, url) {
        return (
            <div id={`${label}-${value}`} className={"value-row value-row-url"}>
                <a href={`${url}`}>{value}</a>
            </div>
        )
    }

    function getValueRows(label, value) {
        let valueColumn;

        if(Array.isArray(value)) {
            valueColumn = value.map( valueItem => {
                return getValueRows(label, valueItem)
            })
        } else if (value instanceof Object) {
            valueColumn = []
            if(value.url && value.title){
                // add href with title to url
                valueColumn.push(urlRow(label, value.title, value.url))
            }
            else if(value.title){
                valueColumn.push(valueRow(label, value.title))
            }
            if(value.subtitle){
                valueColumn.push(subtitleRow(label, value.subtitle))
            }
        } else {
            valueColumn = valueRow(label, value)
        }

        return valueColumn
    }

    return (
        <div id={props.label} className={"public-page-row"}>
            <div className={"data-label"}>{props.label}</div>
            <div className={"data-value-container"}>
                {getValueRows(props.label, props.value)}
            </div>
        </div>
    )
}

export default PublicPage;