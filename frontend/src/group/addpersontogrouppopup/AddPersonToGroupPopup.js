import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import React from "react";
import {AddPersonToGroupPopupContent} from "./AddPersonToGroupPopupContent";
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import {GlobalPageMethods} from "../../components/page/Page";

const SwalAddPersonToGroupPopup = withReactContent(Swal)

class AddPersonToGroupPopup {
    static show(groupToAddTo, navigate, onPersonSelected = null, onCancel = null) {

        SwalAddPersonToGroupPopup.fire({
            html: (
                <AddPersonToGroupPopupContent
                    groupToAddTo={groupToAddTo}
                    onAddButtonClick={(selectedPerson) => {
                        addPersonToGroup(selectedPerson, (error) => {
                            console.log(error);
                            GlobalPageMethods.setFullScreenLoading(false)
                            Toaster.showServerError(error)
                        })
                    }}
                    onCancel={() => {
                        SwalAddPersonToGroupPopup.clickCancel();
                        onCancel();
                    }}/>
            ),
            heightAuto: false,
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
                popup: "add-person-popup",
                container: "add-person-container",
                content: "add-person-content",
            }
        }).then(function (result) {
            console.log("AddPersonToGroupPopup popup result = ", result)
        });

        function addPersonToGroup(person, errorCallback = () => {}) {
            GlobalPageMethods.setFullScreenLoading(true)

            const config = {
                headers: {
                    "Content-Type": "application/vnd.api+json",
                },
                data: {
                    data: [
                        {
                            type: 'group',
                            id: groupToAddTo.id
                        }
                    ]
                }
            }
            Api.post('persons/' + person.id + '/groups', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

            function onValidate(response) {
            }

            function onSuccess(response) {
                GlobalPageMethods.setFullScreenLoading(false)
                onPersonSelected(person)
                SwalAddPersonToGroupPopup.clickConfirm();
            }

            function onServerFailure(error) {
                errorCallback(error)
                if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                    navigate('/login?redirect=' + window.location.pathname);
                }
            }

            function onLocalFailure(error) {
                errorCallback(error)
                console.log(error);
            }
        }
    }
}

export default AddPersonToGroupPopup;