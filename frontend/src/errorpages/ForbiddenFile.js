import React from "react";
import './errorpage.scss'
import {faInfoCircle} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {useTranslation} from "react-i18next";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import {useLocation} from "react-router-dom";
import styled from "styled-components";

function Forbidden(props) {
    const {t} = useTranslation();
    const location = useLocation()

    function navigateToDashboard() {
        const errorCode = getErrorCodeFromQueryParams()
        const fileId = getFileId()

        // Only try to download file on dashboard when a user is not yet authenticated and a file id is present
        if (errorCode === "FJAC_401" && fileId) {
            props.history.push({
                pathname: "/publicationfiles/" + getFileId(),
            })
        } else {
            props.history.push({
                pathname: "/dashboard"
            })
        }
    }

    function getFileId() {
        return new URLSearchParams(location.search).get('fileId')
    }

    function getErrorCodeFromQueryParams() {
        return new URLSearchParams(window.location.search).get('errorCode')
    }

    function getErrorText() {
        switch (getErrorCodeFromQueryParams()) {
            case "FJAC_401":
                return t('error_pages.forbidden_file_text.401')
            case "FJAC_403_E":
                return t('error_pages.forbidden_file_text.403_E')
            case "FJAC_403_R":
                return t('error_pages.forbidden_file_text.403_R')
            case "FJAC_403_C":
                return t('error_pages.forbidden_file_text.403_C')
            default:
                return t('error_pages.forbidden_file_text.default')
        }
    }

    function getErrorTitle() {
        switch (getErrorCodeFromQueryParams()) {
            case "FJAC_401":
                return t('error_pages.forbidden_file_title.401')
            default:
                return t('error_pages.forbidden_file_title.default')
        }
    }

    function getButtonText() {
        switch (getErrorCodeFromQueryParams()) {
            case "FJAC_401":
                return t('error_pages.forbidden_file_button.401')
            default:
                return t('error_pages.to_dashboard')
        }
    }

    return (
        <div id={"not-found"} className="main">
            <div className={"page-content-container"}>
                <div className={"page-wrapper"}>
                    <div className={"page-content row with-margin"}>
                        <div className={"error-page-wrapper"}>
                            <div className={"error-page-container"}>
                                <ErrorTitle>{getErrorTitle()}</ErrorTitle>
                                <div className={"text"}>{getErrorText()}</div>
                                <div className={"buttons-wrapper"}>
                                    <ButtonText text={getButtonText()}
                                                buttonType={"callToAction"}
                                                onClick={navigateToDashboard}/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

const ErrorTitle = styled.h1`
  font-size: 38px;
`;

export default Forbidden;