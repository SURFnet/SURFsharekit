import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import React from "react";
import {RemediateProgressPopupContent} from "./RemediateProgressPopupContent";

const SwalAddPersonToGroupPopup = withReactContent(Swal)

export class RemediateProgressPopup {
    static show(bulkActionId, count, repoType, action) {
        SwalAddPersonToGroupPopup.fire({
            html: (
                <RemediateProgressPopupContent
                    bulkActionId={bulkActionId}
                    count={count}
                    repoType={repoType}
                    action={action}
                    onCancel={() => {
                        SwalAddPersonToGroupPopup.clickCancel();
                    }}/>
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "remediate-progress-popup",
                container: "remediate-progress-container",
                content: "remediate-progress-content",
            }
        }).then(function (result) {
            console.log("Remediate progress popup result = ", result)
        });
    }
}