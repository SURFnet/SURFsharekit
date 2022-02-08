class FormFieldAnswerValues {

    static getAnswerValue(field, answerValues) {
        return (answerValues[field.key] !== undefined && answerValues[field.key][0] !== null) ? answerValues[field.key][0].value : null;
    }

    static getAnswerRepoItemId(field, answerValues) {
        return (answerValues[field.key] !== undefined && answerValues[field.key][0] !== null) ? answerValues[field.key][0].repoItemID : null;
    }

    static getSingleAnswerOptionId(field, answerValues) {
        return (answerValues[field.key] !== undefined && answerValues[field.key][0] !== null) ? answerValues[field.key][0].optionKey : null
    }

    static getAllAnswerOptionIds(field, answerValues) {
        if (answerValues[field.key] !== undefined) {
            const answerOptionIds = answerValues[field.key].map(answer => {
                return answer.optionKey
            });
            return field.options.map(option => {
                return answerOptionIds.includes(option.key)
            })
        }
        return null
    }

    static getAnswersDictionary(answers) {
        let answerValues = [];
        answers.forEach(answer => {
            answerValues[answer.fieldKey] = answer.values
        });
        return answerValues
    }
}

export default FormFieldAnswerValues;