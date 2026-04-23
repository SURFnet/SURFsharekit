import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import React from "react";
import LmsFlaggedPopupContent from "./LmsFlaggedPopupContent";

const SwalLmsFlaggedPopup = withReactContent(Swal);

class LmsFlaggedPopup {
    static show(onConfirm) {
        SwalLmsFlaggedPopup.fire({
            html: (
                <LmsFlaggedPopupContent
                    onCancel={() => SwalLmsFlaggedPopup.clickCancel()}
                    onConfirm={() => SwalLmsFlaggedPopup.clickConfirm()}
                />
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false
        }).then((result) => {
            if (result.isConfirmed && typeof onConfirm === "function") {
                onConfirm();
            }
        });
    }
}

export default LmsFlaggedPopup;
