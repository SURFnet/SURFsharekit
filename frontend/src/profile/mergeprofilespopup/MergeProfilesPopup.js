import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import React from "react";
import ClaimRequestPopupContent from "../claimrequest/ClaimRequestPopupContent";
import MergeProfilePopupContent from "./MergeProfilePopupContent";

const SwalMergeProfilesPopup = withReactContent(Swal)

class MergeProfilePopup {

    static show(history) {

        SwalMergeProfilesPopup.fire({
            html: (
                <MergeProfilePopupContent
                    history={history}
                    onCancel={() => {
                        SwalMergeProfilesPopup.clickCancel();
                    }}/>
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            width: '875px'
        }).then(function (result) {
        });
    }
}

export default MergeProfilePopup;