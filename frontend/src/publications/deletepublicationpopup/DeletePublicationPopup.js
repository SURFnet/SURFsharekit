import React from "react";
import withReactContent from "sweetalert2-react-content";
import Swal from "sweetalert2";
import "./deletepublicationpopupcontent.scss"
import {DeletePublicationPopupContent} from "./DeletePublicationPopupContent";

const SwalDeletePublicationPopup = withReactContent(Swal)

class DeletePublicationPopup {
    static show(onConfirm) {
        SwalDeletePublicationPopup.fire({
            html: (
                <DeletePublicationPopupContent
                    onConfirm={(reason) => {
                        onConfirm(reason)
                        SwalDeletePublicationPopup.clickConfirm();
                    }}
                    onCancel={() => {
                        SwalDeletePublicationPopup.clickCancel();
                    }}/>
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "delete-publication-popup",
                container: "delete-publication-container",
                content: "delete-publication-content",
            }
        })
    }
}

export default DeletePublicationPopup