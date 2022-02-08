import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import React from "react";
import {AddPublicationPopupContent} from "./AddPublicationPopupContent";

const SwalAddPublicationPopup = withReactContent(Swal)

class AddPublicationPopup {
    static show(institutes, instituteAndTypeSelected, repoItemToCopyCallback, onCancel) {
        SwalAddPublicationPopup.fire({
            html: (
                <AddPublicationPopupContent
                    institutes={institutes}
                    instituteAndTypeSelected={(instituteAndType) => {
                        SwalAddPublicationPopup.clickConfirm()
                        instituteAndTypeSelected(instituteAndType)
                    }}
                    repoItemToCopySelected={(repoItemToCopy) => {
                        SwalAddPublicationPopup.clickConfirm()
                        repoItemToCopyCallback(repoItemToCopy)
                    }}
                    onCancel={() => {
                        SwalAddPublicationPopup.clickCancel();
                    }}
                />
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "add-publication-popup",
                container: "add-publication-container",
                content: "add-publication-content",
            }
        }).then(function (result) {
            if (result.isDismissed) {
                onCancel();
            }
        });
    }
}

export default AddPublicationPopup;