import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import React from "react";
import RelatedRepoItemContent from "./RelatedRepoItemContent";

const SwalRepoItemPopup = withReactContent(Swal)

class RelatedRepoItemPopup {

    static show(repoItemId, file, onSuccessfulSave = null, onCancel = null, baseRepoItem, repoItemIdsToExcludeInSearch = []) {

        SwalRepoItemPopup.fire({
            html: (
                <RelatedRepoItemContent
                    baseRepoItem={baseRepoItem}
                    repoItemIdsToExcludeInSearch={repoItemIdsToExcludeInSearch}
                    repoItemId={repoItemId}
                    file={file}
                    onSuccessfulSave={(savedRepoItem) => {
                        SwalRepoItemPopup.clickConfirm();
                        onSuccessfulSave(savedRepoItem);
                    }}
                    onCancel={() => {
                        SwalRepoItemPopup.clickCancel();
                        onCancel();
                    }}/>
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "repoitem-popup",
                container: "repoitem-container",
                content: "repoitem-content",
            }
        }).then(function (result) {
        });
    }
}

export default RelatedRepoItemPopup;