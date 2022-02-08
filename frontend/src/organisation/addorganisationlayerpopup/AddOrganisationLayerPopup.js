import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import React from "react";
import {AddOrganisationLayerContent} from "./AddOrganisationLayerContent";

const SwalAddOrganisationLayerPopup = withReactContent(Swal)

class AddOrganisationLayerPopup {

    static show(institute, isEditing = false, onSuccessfulSave = null, onCancel = null) {

        SwalAddOrganisationLayerPopup.fire({
            html: (
                <AddOrganisationLayerContent
                    institute={institute}
                    isEditing={isEditing}
                    onSuccessfulSave={(savedInstitute, callbackIsEditing) => {
                        SwalAddOrganisationLayerPopup.clickConfirm();
                        onSuccessfulSave(savedInstitute, callbackIsEditing);
                    }}
                    onCancel={() => {
                        SwalAddOrganisationLayerPopup.clickCancel();
                        onCancel();
                    }}/>
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "add-organisation-layer-popup",
                container: "add-organisation-layer-container",
                content: "add-organisation-layer-content",
            }
        }).then(function (result) {
        });
    }
}

export default AddOrganisationLayerPopup;