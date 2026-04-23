import React from "react";
import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import "./restorepublicationpopupcontent.scss"
import {RestorePublicationPopupContent} from "./RestorePublicationPopupContent";

const SwalRestorePublicationPopup = withReactContent(Swal)

class RestorePublicationPopup {
    static show(onConfirm, source = null) {
        SwalRestorePublicationPopup.fire({
            html: (
                <RestorePublicationPopupContent
                    source={source}
                    onConfirm={(reason) => {
                        onConfirm(reason)
                        SwalRestorePublicationPopup.clickConfirm();
                    }}
                    onCancel={() => {
                        SwalRestorePublicationPopup.clickCancel();
                    }}/>
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "restore-publication-popup",
                container: "restore-publication-container",
                content: "restore-publication-content",
            }
        })
    }
}

export default RestorePublicationPopup 