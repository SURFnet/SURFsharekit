import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import React from "react";
import {RemediatePopupContent} from "./RemediatePopupContent";

const SwalAddPersonToGroupPopup = withReactContent(Swal)

class RemediatePopup {
    static show() {
        SwalAddPersonToGroupPopup.fire({
            html: (
                <RemediatePopupContent
                    onCancel={() => {
                        SwalAddPersonToGroupPopup.clickCancel();
                    }}/>
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "remediate-popup",
                container: "remediate-container",
                content: "remediate-content",
            }
        }).then(function (result) {
            console.log("Remediate popup result = ", result)
        });
    }
}

export default RemediatePopup;