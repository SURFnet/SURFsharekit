import React from "react";
import './errorpage.scss'
import {faInfoCircle} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {useTranslation} from "react-i18next";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import IconButton from "../components/buttons/iconbutton/IconButton";

function NotFound(props) {
    const {t} = useTranslation();

    function navigateToDashboard() {
        props.history.push('/dashboard')
    }

    function helpPressed() {

    }

    return (
        <div id={"not-found"} className="main">
            <div className={"page-content-container"}>
                <div className={"page-wrapper"}>
                    <div className={"page-content row with-margin"}>
                        <div className={"error-page-wrapper"}>
                            <div className={"error-page-container"}>
                                <FontAwesomeIcon className={"not-found-icon"} icon={faInfoCircle}/>
                                <h1>{t('error_pages.page_not_found')}</h1>
                                <div className={"subtitle"}>{t('error_pages.page_not_found_subtitle')}</div>
                                <div className={"text"}>{t('error_pages.page_not_found_text')}</div>
                                <div className={"buttons-wrapper"}>
                                    <ButtonText text={t("error_pages.to_dashboard")}
                                                buttonType={"callToAction"}
                                                onClick={navigateToDashboard}/>
                                    <IconButton icon={faInfoCircle}
                                                text={t("error_pages.help")}
                                                onClick={helpPressed}/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default NotFound;