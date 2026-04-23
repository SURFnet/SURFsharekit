import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import React from "react";
import ClaimRequestPopupContent from "../claimrequest/ClaimRequestPopupContent";
import MergeProfilePopupContent from "./MergeProfilePopupContent";

const SwalMergeProfilesPopup = withReactContent(Swal)

class MergeProfilePopup {

    static show(navigate, isOnboarding = false, onSuccess = null) {
        SwalMergeProfilesPopup.fire({
            html: (
                <MergeProfilePopupContent
                    onCancel={() => {
                        SwalMergeProfilesPopup.clickCancel();
                    }}
                    navigate={navigate}
                    isOnboarding={isOnboarding}
                    onSuccess={onSuccess}
                />
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