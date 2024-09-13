import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import React from "react";
import ProfileDetailsPopupContent from "./ProfileDetailsPopupContent";

const SwalProfileDetailsPopup = withReactContent(Swal)

class ProfileDetailsPopup {
    static show(profile) {
        SwalProfileDetailsPopup.fire({
            html: (
                <ProfileDetailsPopupContent
                    onCancel={() => SwalProfileDetailsPopup.clickCancel() }
                    details={profile}
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
        })
    }
}

export default ProfileDetailsPopup;