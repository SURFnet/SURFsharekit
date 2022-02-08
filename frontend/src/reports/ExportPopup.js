import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import React from "react";
import {ExportPopupContent} from "./ExportPopupContent";

const SwalAddPersonToGroupPopup = withReactContent(Swal)

class ExportPopup {
    static show() {
        SwalAddPersonToGroupPopup.fire({
            html: (
                <ExportPopupContent
                    onCancel={() => {
                        SwalAddPersonToGroupPopup.clickCancel();
                    }}/>
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "export-popup",
                container: "export-container",
                content: "export-content",
            }
        }).then(function (result) {
            console.log("Export popup result = ", result)
        });
    }
}

export default ExportPopup;