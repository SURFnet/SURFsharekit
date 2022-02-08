import Api from "./Api";

class RepoItemApiRequests {
    getRepoItem(repoItemId, onValidate, onSuccess, onLocalFailure, onServerFailure, include = []) {
        Api.jsonApiGet(`repoItems/${repoItemId}/?include=` + include.join(), onValidate, onSuccess, onLocalFailure, onServerFailure);
    }
}

export default RepoItemApiRequests;