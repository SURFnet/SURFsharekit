import React from "react";
import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import {CheckDetailsPopupContent} from "./CheckDetailsPopupContent";

const SwalAddPublicationPopup = withReactContent(Swal)

class CheckDetailsPopup {
    static show(repoItem) {
        SwalAddPublicationPopup.fire({
            html: (
                <CheckDetailsPopupContent
                    onCancel={() => {
                        SwalAddPublicationPopup.clickCancel();
                    }}
                    repoItem={repoItem}
                />
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "check-details-popup",
                container: "check-details-container",
                content: "check-details-content",
            }
        }).then(function (result) {

        });
    }
}

export default CheckDetailsPopup;