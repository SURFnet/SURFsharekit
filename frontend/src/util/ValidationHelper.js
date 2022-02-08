import RepoItemHelper from "./RepoItemHelper";

export const VALIDATION_RESULT = {
    NO_CHANNEL: "noChannel",
    DEPENDENCY_ERROR: "conflictingChannels",
    NO_ERRORS: "noErrors"
}

class ValidationHelper {

    static exists(value) {
        return (value !== null && typeof value !== 'undefined')
    }

    static isArray(value) {
        return (value !== null && typeof value !== 'undefined')
    }

    /**
     * Used to check if a repoItem has channel dependency conflicts.
     * Only one of the following channel types are allowed to be 'enabled':
     * Archive OR PublicChannel OR PrivateChannel
     * @param answers
     * @param repoItem
     */
    static hasChannelDependencyErrors(answers, repoItem) {
        if(answers) {
            const fieldAnswerObjects = answers.map((answer) => {
                return {
                    field: RepoItemHelper.getFieldForFieldKey(repoItem, answer.fieldKey),
                    answer: answer
                }
            })

            const enabledChannels = fieldAnswerObjects.filter((fieldAnswerObject) => {
                return fieldAnswerObject.field.channelType !== null && fieldAnswerObject.answer.values[0].value === 1
            })

            if(enabledChannels.length === 0){
                return VALIDATION_RESULT.NO_CHANNEL
            }

            const privateChannelEnabled = enabledChannels.filter((channel) => channel.field.channelType === 'PrivateChannel').length > 0
            const publicChannelEnabled = enabledChannels.filter((channel) => channel.field.channelType === 'PublicChannel').length > 0
            const archiveEnabled = enabledChannels.filter((channel) => channel.field.channelType === 'Archive').length > 0

            const privateAndOtherChannelEnabled = privateChannelEnabled && (publicChannelEnabled || archiveEnabled)
            const publicAndOtherChannelEnabled = publicChannelEnabled && (privateChannelEnabled || archiveEnabled)
            const archiveAndOtherChannelEnabled = archiveEnabled && (privateChannelEnabled || publicChannelEnabled)

            return (privateAndOtherChannelEnabled || publicAndOtherChannelEnabled || archiveAndOtherChannelEnabled) ? VALIDATION_RESULT.DEPENDENCY_ERROR : VALIDATION_RESULT.NO_ERRORS
        }

        return VALIDATION_RESULT.NO_ERRORS
    }

    /**
     * Channel dependencies should be checked when the status passed is equal to 'published', 'approved', 'submitted', 'embargo'
     * @param repoItemStatus
     * @return {boolean}
     */
    static shouldCheckChannelDependencyErrors(repoItemStatus) {
        const statusesToCheck = ['published', 'approved', 'submitted', 'embargo'];
        const statusResult = statusesToCheck.find(status => {
            return status === repoItemStatus.toLowerCase()
        });
        return statusResult !== undefined
    }
}

export default ValidationHelper