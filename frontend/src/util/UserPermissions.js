
export class UserPermissions {

    static canCreateRepoItem(permissions) {
        let canCreateRepoItemTemp = false;

        if (permissions && permissions.length > 0) {
            permissions.forEach((codeMatrix) => {
                    const canCreatePublicationRecord = codeMatrix.REPOITEM_PUBLICATIONRECORD && codeMatrix.REPOITEM_PUBLICATIONRECORD.CREATE.isSet
                    const canCreateLearningObject = codeMatrix.REPOITEM_LEARNINGOBJECT && codeMatrix.REPOITEM_LEARNINGOBJECT.CREATE.isSet
                    const canCreateResearchObject = codeMatrix.REPOITEM_RESEARCHOBJECT && codeMatrix.REPOITEM_RESEARCHOBJECT.CREATE.isSet
                    const canCreateDataset = codeMatrix.REPOITEM_DATASET && codeMatrix.REPOITEM_DATASET.CREATE.isSet
                    const canCreateProject = codeMatrix.REPOITEM_PROJECT && codeMatrix.REPOITEM_PROJECT.CREATE.isSet

                    if (
                        !canCreateRepoItemTemp &&
                        (
                            canCreatePublicationRecord ||
                            canCreateLearningObject ||
                            canCreateResearchObject ||
                            canCreateDataset ||
                            canCreateProject
                        )
                    ) {
                        canCreateRepoItemTemp = true
                    }
            });
        }

        return canCreateRepoItemTemp;
    }

    static canCreateMember(permissions) {
        return true
    }

    static canRemediate(permissions) {
        let canRemediate = false
        if (permissions && permissions.length > 0) {
            permissions.forEach((codeMatrix) => {
                const cr = codeMatrix.REPOITEM_ALL.SANITIZE && codeMatrix.REPOITEM_ALL.SANITIZE.isSet
                if (cr){
                    canRemediate = cr
                }
            });
        }

        return canRemediate;
    }
}