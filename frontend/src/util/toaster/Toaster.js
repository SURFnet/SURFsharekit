import React from "react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faExclamationTriangle, faInfoCircle} from "@fortawesome/free-solid-svg-icons";
import {toast} from "react-toastify";
import i18n from '../../i18n'
import {faTimesCircle} from "@fortawesome/free-solid-svg-icons/faTimesCircle";
import {faTimes} from "@fortawesome/free-solid-svg-icons/faTimes";
import {faCheckCircle} from "@fortawesome/free-solid-svg-icons/faCheckCircle";

class Toaster {
    /**
     * @param toasterProps
     * Type: error or success. Defaults to success.
     * Message: the toast message
     * You can call this function anywhere, it's scope agnostic. E.g.: Toaster.showToaster({type: "error", message:"Dit werkt als een trein!"})
     */
    static showToaster(toasterProps) {
        const toasterContent =
            <div className={'custom-toaster-content'}>
                <div className={"icon-wrapper"}>
                    <FontAwesomeIcon icon={getIcon(toasterProps.type)}/>
                </div>
                <div className={"custom-toaster-text"}>
                    {toasterProps.message}
                </div>
                {toasterProps.details && <div className={"custom-toaster-text"}>
                    {toasterProps.details}
                </div>}
            </div>

        const closeButton =
            <div className={'custom-toaster-close-button'}>
                <FontAwesomeIcon icon={faTimes}/>
            </div>

        if (toasterProps.type === 'error') {
            toast.error(toasterContent, {closeButton});
        } else if (toasterProps.type === 'info') {
            toast.info(toasterContent, {closeButton});
        } else if (toasterProps.type === 'warning') {
            toast.warning(toasterContent, {closeButton});
        } else {
            toast.success(toasterContent, {closeButton});
        }

        function getIcon(toasterType) {
            switch (toasterType) {
                case "error":
                    return faTimesCircle;
                case "warning":
                    return faExclamationTriangle;
                case "success":
                    return faCheckCircle;
                case "info":
                    return faInfoCircle;
            }
        }
    }

    static showDefaultRequestError(message = null) {
        Toaster.showToaster({type: "error", message: message ?? i18n.t("error_message.request_error")})
    }

    static showDefaultRequestSuccess() {
        Toaster.showToaster({type: "success", message: i18n.t("success_message.patch_request_success")})
    }

    static showServerError(error) {

        if(error.response && error.response.status === 403){
            return;
        }
        if (error.response && error.response.data && error.response.data.errors) {
            const code = error.response.data.errors[0].code;

            let errorText = i18n.t("toast.server_error");
            if (code) {
                errorText += " " + code;
            }
            this.showDefaultRequestError(errorText);
        } else {
            this.showDefaultRequestError();
        }
    }
}

export default Toaster;