
export class UserPermissions {

    static canCreateRepoItem(permissions) {
        let canCreateRepoItemTemp = false;

        if (permissions && permissions.length > 0) {
            permissions.forEach((codeMatrix) => {
                    const canCreatePublicationRecord = codeMatrix.REPOITEM_PUBLICATIONRECORD && codeMatrix.REPOITEM_PUBLICATIONRECORD.CREATE.isSet
                    const canCreateLearningObject = codeMatrix.REPOITEM_LEARNINGOBJECT && codeMatrix.REPOITEM_LEARNINGOBJECT.CREATE.isSet
                    const canCreateResearchObject = codeMatrix.REPOITEM_RESEARCHOBJECT && codeMatrix.REPOITEM_RESEARCHOBJECT.CREATE.isSet

                    if (!canCreateRepoItemTemp && (canCreatePublicationRecord || canCreateLearningObject || canCreateResearchObject)) {
                        canCreateRepoItemTemp = true
                    }
            });
        }

        return canCreateRepoItemTemp;
    }

    static canCreateMember(permissions) {
        return true
    }
}