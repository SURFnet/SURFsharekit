import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import React from "react";
import {VerificationPopupContent} from "./VerificationPopupContent";

const SwalVerificationPopup = withReactContent(Swal)

class VerificationPopup {
    static show(title, subtitle, onConfirm, displayConfirmButtonOnly = false, cancelTimeoutMS = null, smallSubtitle = false) {
        SwalVerificationPopup.fire({
            html: (
                <VerificationPopupContent
                    title={title}
                    subtitle={subtitle}
                    displayConfirmButtonOnly={displayConfirmButtonOnly}
                    onConfirm={() => {
                        onConfirm()
                        SwalVerificationPopup.clickConfirm();
                    }}
                    onCancel={() => {
                        SwalVerificationPopup.clickCancel();
                    }}/>
            ),
            timer: cancelTimeoutMS,
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "verification-popup",
                container: "verification-container",
                content: `verification-content ${smallSubtitle ? "smallSubtitle" : ""}`,
            }
        })
    }
}

export default VerificationPopup;