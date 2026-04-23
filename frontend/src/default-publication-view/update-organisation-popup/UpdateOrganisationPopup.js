import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import UpdateOrganisationPopupContent from "./UpdateOrganisationPopupContent";

const SwalUpdateOrganisationPopup = withReactContent(Swal)

class UpdateOrganisationPopup {
    static show(currentInstitute, onConfirm){
        SwalUpdateOrganisationPopup.fire({
            html: (
                <UpdateOrganisationPopupContent
                    currentInstitute={currentInstitute}
                    onCancel={() => {
                        SwalUpdateOrganisationPopup.clickCancel()
                    }}
                    onConfirm={(selectedInstituteId) => {
                        onConfirm(selectedInstituteId)
                        SwalUpdateOrganisationPopup.clickConfirm();
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

export default UpdateOrganisationPopup;
