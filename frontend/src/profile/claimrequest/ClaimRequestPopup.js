import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import React, {useEffect} from "react";
import ClaimRequestPopupContent from "./ClaimRequestPopupContent";
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";

const SwalClaimRequestPopup = withReactContent(Swal)

class ClaimRequestPopup {

    static show(history, person, personInstitutes) {

        SwalClaimRequestPopup.fire({
            html: (
                <ClaimRequestPopupContent
                    history={history}
                    person={person}
                    personInstitutes={personInstitutes}
                    onCancel={() => {
                        SwalClaimRequestPopup.clickCancel();
                    }}
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

export default ClaimRequestPopup;