import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import React from "react";
import {ExportPopupContent} from "./ExportPopupContent";

const SwalAddPersonToGroupPopup = withReactContent(Swal)

class ExportPopup {
    static show() {
        return SwalAddPersonToGroupPopup.fire({
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
        })
    }
}

export default ExportPopup;