import React from "react";
import "./loadingindicator.scss"
import ValidationHelper from "../../util/ValidationHelper";
import LoadingSpinner from '../../resources/images/spinner.png'
import {useTranslation} from "react-i18next";

/**
 * Possible 'props' property values
 * @property isLoading {boolean}  - True is default
 * @property isFullscreen {boolean} - False is default
 * Example usage: <LoadingIndicator isLoading={true} isFullscreen={false}/>
 */

function LoadingIndicator(props) {
    const {t} = useTranslation();
    const isLoading = ValidationHelper.exists(props.isLoading) ? props.isLoading : true; //isLoading defaults to 'true'
    const isFullscreen = ValidationHelper.exists(props.isFullscreen) ? props.isFullscreen : false; //Default is false
    const isCenteredInPage = props.centerInPage

    if (!isLoading) {
        return null;
    }

    if (isFullscreen) {
        return <FullScreenLoadingIndicator/>
    } else {
        return <LoadingIndicator/>
    }

    function LoadingIndicator() {
        let style = {}
        if(isCenteredInPage) {
            style = {
                position: "absolute",
                margin: "auto",
                top: 0,
                left: 0,
                bottom: 0,
                right: 0,
            }
        }

        return (
            <div className="loading-indicator" style={style}>
                <img src={LoadingSpinner} alt={'loading spinner'}/>
            </div>
        );
    }

    function FullScreenLoadingIndicator() {
        return (
            <div className="fullscreen-loading-indicator">
                <div className={"fullscreen-loading-indicator-background"}>
                    <img src={LoadingSpinner} alt={'loading spinner'}/>
                    <h3 className={"fullscreen-loading-indicator-subtitle"}>{t("loading_indicator.loading_text")}</h3>
                </div>
            </div>
        );
    }
}

export default LoadingIndicator