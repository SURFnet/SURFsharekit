import React from "react";
import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import VocabularyPopupContent2 from "./VocabularyPopupContent2";

const SwalAddPublicationPopup = withReactContent(Swal)

class VocabularyPopup2 {
    static show(formReducerState, name, jsonKey, label, selectedVocabularyOption, onCancel,  retainOrder = false, vocabularies){
        SwalAddPublicationPopup.fire({
            html: (
                <VocabularyPopupContent2
                    formReducerState={formReducerState}
                    name={name}
                    jsonKey={jsonKey}
                    label={label}
                    retainOrder={retainOrder}
                    onCancel={() => {
                        SwalAddPublicationPopup.clickCancel();
                    }}
                    selectedVocabularyOption={(vocabulary) => {
                        selectedVocabularyOption(vocabulary);
                        SwalAddPublicationPopup.clickConfirm()
                    }}
                    vocabularies={vocabularies}
                />
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false
        }).then(function (result) {
            if(result.isDismissed) {
                onCancel();
            }
        });
    }
}

export default VocabularyPopup2;
