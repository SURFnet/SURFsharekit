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
                default:
                    return null;
            }
        }
    }

    static showDefaultRequestError(message = null) {
        Toaster.showToaster({type: "error", message: message ?? i18n.t("toast.server_error_generic")})
    }

    static showDefaultRequestSuccess() {
        Toaster.showToaster({type: "success", message: i18n.t("success_message.patch_request_success")})
    }

    static showServerError(error) {
        if (!error) {
            this.showDefaultRequestError();
            return;
        }

        // If error is a string, show it directly
        if (typeof error === 'string') {
            Toaster.showToaster({type: "error", message: error});
            return;
        }

        if (error?.response?.status === 403) {
            return;
        }

        const errorMessage = this.getErrorMessage(error);
        const genericRequestError = i18n.t("error_message.request_error");
        const genericServerError = i18n.t("toast.server_error_generic");
        
        // Only show error if we have a specific message (not generic fallbacks)
        if (errorMessage && 
            errorMessage !== genericRequestError && 
            errorMessage !== genericServerError) {
            Toaster.showToaster({type: "error", message: errorMessage})
        } else {
            // Only show default if we don't have a specific error message
            this.showDefaultRequestError();
        }
    }

    static getErrorMessage(error){
        if (!error) {
            return i18n.t("error_message.request_error");
        }

        const status = error?.response?.status;

        if (status && status >= 500) { // generic message for server errors
            return i18n.t("toast.server_error_generic");
        }

        if (error.response && error.response.data) {
            // Prioritize errors array with code over generic error field
            if (error.response.data.errors) {
                const errors = error.response.data.errors;
                const errorObject = Array.isArray(errors) ? errors[0] : errors;

                if (errorObject) {
                    const {title, code} = errorObject;

                    if (code) {
                        // If we have a code, return it immediately and ignore generic error field
                        return `${i18n.t("toast.server_error")} ${code}`;
                    }

                    if (title) {
                        // If we have a title, return it and ignore generic error field
                        return title;
                    }
                }
            }

            // Check for message field (e.g., from login endpoint)
            if (error.response.data.message) {
                return error.response.data.message;
            }

            // Only fall back to generic error field if no errors array with code/title was found
            if (error.response.data.error) {
                const genericError = error.response.data.error;
                // Don't return if it matches the generic server error (to avoid duplicates)
                if (genericError !== i18n.t("toast.server_error_generic")) {
                    return genericError;
                }
            }
        }

        if (error.message) {
            return error.message;
        }

        return i18n.t("error_message.request_error");
    }
}

export default Toaster;
