import React from "react";
import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import VocabularyPopupContent from './VocabularyPopupContent';

const SwalAddPublicationPopup = withReactContent(Swal)

class VocabularyPopup {
    static show(metaFieldName, selectedVocabulary, onCancel, retainOrder = false) {
        SwalAddPublicationPopup.fire({
            html: (
                <VocabularyPopupContent
                    name={metaFieldName}
                    retainOrder={retainOrder}
                    onCancel={() => {
                        SwalAddPublicationPopup.clickCancel();
                    }}
                    selectedVocabulary={(vocabulary) => {
                        selectedVocabulary(vocabulary)
                        SwalAddPublicationPopup.clickConfirm()
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
            if(result.isDismissed) {
                onCancel();
            }
        });
    }
}

export default VocabularyPopup;