import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import ClaimRequestPopupContent from "./ClaimRequestPopupContent";

const SwalClaimRequestPopup = withReactContent(Swal)

class ClaimRequestPopup {
    static show(person, personInstitutes) {

        SwalClaimRequestPopup.fire({
            html: (
                <ClaimRequestPopupContent
                    person={person}
                    personInstitutes={personInstitutes}
                    onCancel={() => {
                        SwalClaimRequestPopup.clickCancel();
                    }}
                />
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false
        }).then(function (result) {
        });
    }
}

export default ClaimRequestPopup;