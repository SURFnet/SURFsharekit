import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import React from "react";
import RelatedRepoItemContent from "./RelatedRepoItemContent";
import axios from "axios";

export const SwalRepoItemPopup = withReactContent(Swal)

class RelatedRepoItemPopup {

    static show(repoItemId, file, onSuccessfulSave = null, onCancel = null, baseRepoItem, repoItemIdsToExcludeInSearch = [], getRepoItemFunction = null,  repoItems = []) {

        const cancelToken = axios.CancelToken.source()
        SwalRepoItemPopup.fire({
            html: (
                <RelatedRepoItemContent
                    baseRepoItem={baseRepoItem}
                    repoItems={repoItems}
                    repoItemIdsToExcludeInSearch={repoItemIdsToExcludeInSearch}
                    repoItemId={repoItemId}
                    getRepoItemFunction={getRepoItemFunction}
                    file={file}
                    cancelToken={cancelToken}
                    onSuccessfulSave={(savedRepoItems) => {
                        onSuccessfulSave(savedRepoItems)
                        SwalRepoItemPopup.clickConfirm();
                    }}
                    onCancel={() => {
                        cancelToken.cancel()
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