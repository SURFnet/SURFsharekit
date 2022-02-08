import React from "react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faCheck, faExclamationTriangle} from "@fortawesome/free-solid-svg-icons";
import {toast} from "react-toastify";
import i18n from '../../i18n'

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
                    <FontAwesomeIcon icon={toasterProps.type === 'error' ? faExclamationTriangle : faCheck}/>
                </div>
                <div className={"custom-toaster-text"}>
                    {toasterProps.message}
                </div>
                {toasterProps.details && <div className={"custom-toaster-text"}>
                    {toasterProps.details}
                </div>}
            </div>

        if (toasterProps.type === 'error') {
            toast.error(toasterContent);
        } else {
            toast.success(toasterContent);
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
            const title = error.response.data.errors[0].title;
            const details = error.response.data.errors[0].detail;

            let errorText = '';
            if (code) {
                errorText += "(" + code + ") ";
            }
            errorText += details ?? title;
            this.showDefaultRequestError(errorText);
        } else {
            this.showDefaultRequestError();
        }
    }
}

export default Toaster;