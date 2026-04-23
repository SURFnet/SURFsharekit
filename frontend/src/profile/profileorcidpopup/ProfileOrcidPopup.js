import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import React from "react";
import ProfileOrcidPopupContent from "./ProfileOrcidPopupContent";

const SwalProfileOrcidPopup = withReactContent(Swal)

class ProfileOrcidPopup {
    static show(orcid) {
        SwalProfileOrcidPopup.fire({
            html: (
                <ProfileOrcidPopupContent
                    onCancel={() => SwalProfileOrcidPopup.clickCancel() }
                    orcid={orcid}
                />
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false
        })
    }
}

export default ProfileOrcidPopup;