import ValidationError from "../ValidationError";
import Api from "./Api";
import Toaster from "../toaster/Toaster";

class ApiRequests {

    static getExtendedPersonInformation(user, history, successCallback, errorCallback) {
        const config = {
            params: {
                'fields[institutes]': 'title,conextCode,permissions,isUsersConextInstitute,isBaseScopeForUser',
                'fields[groups]': 'title,permissions,userPermissions,roleCode,codeMatrix,partOf'
            }
        };
        Api.jsonApiGet('persons/' + user.id + '?include=groups,groups.partOf,groups.partOf.image', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
            if (!(response.data.groups && response.data.groups.length > 0)) {
                throw ValidationError("Person doesn't have a group")
            }
            let group = response.data.groups[0];

            if (!(group.partOf)) {
                throw ValidationError("Person's group not partOf an institute")
            }
        }

        function onSuccess(response) {
            successCallback(response.data)
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/unauthorized');
            }
            errorCallback()
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
            errorCallback()
        }
    }
}

export default ApiRequests;