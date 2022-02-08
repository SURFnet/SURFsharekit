import RepoItemHelper from "./RepoItemHelper";


class FormFieldHelper {

    fieldTypeMap = {
        "date": "singledatepicker",
        "text": "text",
        "doi": "doi",
        "number": "number",
        "dropdown": "dropdown",
        "multiselectdropdown": "multiselectdropdown",
        "multiselectsuborganisation": "multiselectsuborganisation",
        "multiselectpublisher": "multiselectpublisher",
        "textarea": "textarea",
        "checkbox": "checkbox",
        "file": "file",
        "switch": "switch",
        "switch-row": "switch-row",
        "email": "email",
        "tag": "tag",
        "person": "person",
        "repoitem": "repoitem",
        "discipline": "discipline",
        "lectorate": "lectorate",
        "institute": "institute",
        "tree-multiselect": "tree-multiselect",

        "repoitems": "repoitems",
        "attachment": "attachment",
        "personinvolved": "personinvolved",
        "repoitemlink": "repoitemlink",
        "repoitemlearningobject": "repoitemlearningobject"
    };

    singleAnswerFieldType = [
        "select",
        "person",
        "repoitem",
        "text",
        "doi",
        "number",
        "textarea",
        "singledatepicker",
        "dropdown",
        "discipline",
        "lectorate",
        "organisationdropdown",
        "switch-row"
    ];

    getFieldType(fieldType) {
        return this.fieldTypeMap[fieldType.toLowerCase()];
    }

    getFieldOptions(field) {
        return field.options.map((o) => {
                return {
                    value: o.key,
                    labelNL: o.labelNL,
                    labelEN: o.labelEN,
                    coalescedLabelNL: o.coalescedLabelNL,
                    coalescedLabelEN: o.coalescedLabelEN
                }
            }
        );
    }

    getFieldAnswer(repoItem, field) {
        let answer = RepoItemHelper.getAnswerFromRepoItemForField(repoItem, field);

        if (answer) {
            answer = answer.values.map(v => {
                if (v.repoItemID) {
                    return {
                        id: v.repoItemID,
                        summary: v.summary
                    }
                } else if (v.optionKey) {
                    return v.optionKey
                } else if (v.instituteID) {
                    return {
                        id: v.instituteID,
                        summary: v.summary
                    }
                } else if (v.repoItemFileID) {
                    return {
                        id: v.repoItemFileID,
                        summary: v.summary
                    }
                } else if (v.personID) {
                    return {
                        id: v.personID,
                        summary: v.summary
                    }
                }
                return v.value;
            });
            const formFieldHelper = new FormFieldHelper()
            const fieldType = formFieldHelper.getFieldType(field.fieldType);

            if (this.singleAnswerFieldType.includes(fieldType) && answer.length > 0) {
                answer = answer[0];
            }
        }
        return answer;
    }

    getAllFormAnswersForRepoItem(repoItem, formData) {
        const answers = Object.entries(formData).map(([formDataFieldKey, formDataAnswerValue]) => {
                const field = RepoItemHelper.getFieldForFieldKey(repoItem, formDataFieldKey);
                const answerValueArray = RepoItemHelper.getAnswerValueArrayForField(field, formDataAnswerValue);

                return {
                    "fieldKey": formDataFieldKey,
                    "values": answerValueArray
                };
            }
        );

        return answers.filter(a => a.values.length > 0);
    }

}

export default FormFieldHelper;