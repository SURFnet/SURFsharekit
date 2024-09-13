import FormFieldHelper from "./FormFieldHelper";
import {HelperFunctions} from "./HelperFunctions";
import i18n from "i18next";

class RepoItemHelper {
    static getLastEditedDate(lastEdited) {
        const dateFormat = HelperFunctions.getDateFormat(lastEdited, {
            year: 'numeric',
            month: 'short',
            day: '2-digit'
        })

        return `${dateFormat.day} ${dateFormat.month} ${dateFormat.year}`;
    }

    static getTitle(repoItem) {
        if (repoItem.title && repoItem.title.length > 0) {
            return repoItem.title;
        } else if (this.getSectionsFromSteps(repoItem)) {
            const answers = this.getAnswerValuesForAttributeKey(repoItem, "Title");

            if (answers && Array.isArray(answers) && answers.length > 0) {
                return answers[0].value;
            }
            return null;
        }
    }

    static getAuthor(repoItem) {
        const answers = this.getAnswerValuesForAttributeKey(repoItem, "Author");

        if (answers && Array.isArray(answers) && answers.length > 0) {
            return answers[0].value;
        }
        return null;
    }

    static getAllFields(repoItem) {
        return (this.getSectionsFromSteps(repoItem) ?? []).reduce((total, val) => {
            return total.concat(val.fields);
        }, []);
    }

    static getSectionsFromSteps(repoItem) {
        let sections = [];
        repoItem.steps.forEach(step => {
           step.templateSections.forEach(section => {
               sections.push(section)
           })
        })
        return sections
    }

    static getAnswerValuesForAttributeKey(repoItem, attributeKey) {

        const fieldWithAttributeKey = RepoItemHelper.getAllFields(repoItem).find((field) => field.attributeKey === attributeKey);

        if (!fieldWithAttributeKey)
            return null;
        const answerObjectForField = repoItem.answers.find((answer) => answer.fieldKey === fieldWithAttributeKey.key);
        if (!answerObjectForField) {
            return null;
        }
        return answerObjectForField.values;
    }

    static getFieldForFieldKey(repoItem, fieldKey) {
        let actualField = null;
        RepoItemHelper.getAllFields(repoItem).forEach(field => {
            if (field.key === fieldKey) {
                actualField = field;
            }
        });
        return actualField;
    }

    static getAnswerValueArrayForField(field, formDataAnswerValue) {
        const formFieldHelper = new FormFieldHelper();
        let answerArray = []

        if (field !== null) {
            const fieldType = formFieldHelper.getFieldType(field.fieldType);
            if (fieldType === 'tag' || fieldType === 'dropdowntag') {
                // let tagAnswers = formDataAnswerValue ? JSON.parse(formDataAnswerValue) : [];
                let tagAnswers = formDataAnswerValue ?? [];
                tagAnswers.forEach((tagOption) => {
                    if (tagOption.id) {
                        answerArray.push({
                            "repoItemID": null,
                            "optionKey": tagOption.id,
                            "value": null
                        })
                    } else {
                        answerArray.push({
                            "repoItemID": null,
                            "optionKey": null,
                            "labelNL": tagOption.labelNL,
                            "labelEN": tagOption.labelEN,
                            "coalescedLabelEN": tagOption.coalescedLabelEN,
                            "coalescedLabelNL": tagOption.coalescedLabelNL,
                        })
                    }
                })
            } else if (fieldType === "checkbox") {
                if (!formDataAnswerValue) {
                    return [];
                }

                if (!Array.isArray(formDataAnswerValue)) {
                    formDataAnswerValue = [formDataAnswerValue]
                }

                answerArray = formDataAnswerValue.map((value) => {
                    return {
                        "repoItemID": null,
                        "optionKey": value,
                        "value": null
                    }
                });
            } else if (fieldType === "switch-row") {
                if (!formDataAnswerValue) {
                    return [];
                }

                if (!Array.isArray(formDataAnswerValue)) {
                    formDataAnswerValue = [formDataAnswerValue]
                }

                answerArray = formDataAnswerValue.map((value) => {
                    return {
                        "repoItemID": null,
                        "optionKey": null,
                        "value": value ? 1 : 0
                    }
                });
            } else if (fieldType === "radio" || fieldType === "dropdown" || fieldType === "rightofusedropdown") {
                if (formDataAnswerValue) {
                    answerArray = [{
                        "repoItemID": null,
                        "optionKey": formDataAnswerValue,
                        "value": null
                    }];
                } else {
                    answerArray = [];
                }
            } else if (fieldType === "multiselectdropdown") {
                if (formDataAnswerValue) {
                    answerArray = formDataAnswerValue.map((answerValue) => {
                        return {
                            "repoItemID": null,
                            "optionKey": answerValue.value,
                            "value": null
                        }
                    })
                } else {
                    answerArray = [];
                }
            } else if (fieldType === "discipline") {
                let instituteAnswer = formDataAnswerValue ? JSON.parse(formDataAnswerValue) : null;
                if (instituteAnswer) {
                    answerArray.push({
                        "instituteID": instituteAnswer.id,
                        "optionKey": null,
                        "value": null,
                        "summary": instituteAnswer.summary //used to store title, subtitle and such
                    })
                } else {
                    answerArray = []
                }
            } else if(fieldType === 'multiselectsuborganisation' || fieldType === 'multiselectsuborganisationswitch') {
                // let instituteAnswer = formDataAnswerValue ? JSON.parse(formDataAnswerValue) : null;
                answerArray = [];
                if(formDataAnswerValue) {
                    formDataAnswerValue.map(formDataAnswerJsonObject => {
                        const formDataAnswerOption = JSON.parse(formDataAnswerJsonObject)
                        answerArray.push({
                            "instituteID": formDataAnswerOption.id,
                            "optionKey": null,
                            "value": null,
                            "summary": formDataAnswerOption.summary //used to store title, subtitle and such
                        })
                    })
                }
            } else if(fieldType === 'multiselectpublisher' || fieldType === 'multiselectpublisherswitch' || fieldType === 'multiselectinstitute') {
                // let instituteAnswer = formDataAnswerValue ? JSON.parse(formDataAnswerValue) : null;
                answerArray = [];
                if(formDataAnswerValue) {
                    formDataAnswerValue.map(formDataAnswerJsonObject => {
                        const formDataAnswerOption = JSON.parse(formDataAnswerJsonObject)
                        answerArray.push({
                            "instituteID": formDataAnswerOption.id,
                            "optionKey": null,
                            "value": null,
                            "summary": formDataAnswerOption.summary //used to store title, subtitle and such
                        })
                    })
                }
            } else if (fieldType === "lectorate") {
                let instituteAnswer = formDataAnswerValue ? JSON.parse(formDataAnswerValue) : null;
                if (instituteAnswer) {
                    answerArray.push({
                        "instituteID": instituteAnswer.id,
                        "optionKey": null,
                        "value": null,
                        "summary": instituteAnswer.summary //used to store title, subtitle and such
                    })
                } else {
                    answerArray = []
                }
            } else if (fieldType === "repoitems" ||
                fieldType === "attachment" ||
                fieldType === "personinvolved" ||
                fieldType === "repoitemlink" ||
                fieldType === "repoitemlearningobject" ||
                fieldType === "repoitemresearchobject") {
                let repoItemFieldAnswers = formDataAnswerValue ? JSON.parse(formDataAnswerValue) : [];
                repoItemFieldAnswers.forEach((relatedRepoItem) => {
                    answerArray.push({
                        "repoItemID": relatedRepoItem.id,
                        "optionKey": null,
                        "value": null,
                        "summary": relatedRepoItem.summary //used to store title, subtitle and such
                    })
                })
            } else if (fieldType === "tree-multiselect"){
                let optionAnswers = formDataAnswerValue ? JSON.parse(formDataAnswerValue) : [];
                optionAnswers.forEach((optionId) => {
                    answerArray.push({
                        "repoItemID": null,
                        "optionKey": optionId,
                        "value": null,
                        "summary": null
                    })
                })
            } else if (fieldType === "file") {
                if (formDataAnswerValue) {
                    answerArray = [{
                        "repoItemID": null,
                        "optionKey": null,
                        "value": null,
                        "repoItemFileID": formDataAnswerValue
                    }];
                } else {
                    answerArray = [];
                }
            } else if (fieldType === "person") {
                let personFieldAnswer = formDataAnswerValue ? JSON.parse(formDataAnswerValue) : null;
                if (personFieldAnswer) {
                    answerArray = [{
                        "repoItemID": null,
                        "optionKey": null,
                        "value": null,
                        "personID": personFieldAnswer.id,
                        "summary": personFieldAnswer.summary
                    }];
                } else {
                    answerArray = [];
                }
            } else if (fieldType === "repoitem") {
                let repoItemFieldAnswer = formDataAnswerValue ? JSON.parse(formDataAnswerValue) : null;
                if (repoItemFieldAnswer) {
                    answerArray = [{
                        "repoItemID": repoItemFieldAnswer.id,
                        "optionKey": null,
                        "value": null,
                        "personID": null,
                        "summary": repoItemFieldAnswer.summary
                    }];
                } else {
                    answerArray = [];
                }
            } else {
                if (formDataAnswerValue) {
                    answerArray = [{
                        "repoItemID": null,
                        "optionKey": null,
                        "value": formDataAnswerValue
                    }];
                } else {
                    answerArray = [];
                }
            }
        }

        return answerArray.map((av, i) => {
                av.sortOrder = i;
                return av;
            }
        )
    }

    static getAnswerFromRepoItemForField(repoItem, field) {
        return repoItem.answers.find(a => a.fieldKey === field.key);
    }

    static getProgressForSection(repoItem, s) {
        //get all answers that apply to this section
        const fieldInSectionWithNonEmptyAnswer = s.fields.filter(f => {
            return repoItem.answers.find(a => a.fieldKey === f.key && a.values.length > 0)
        })
        //get all none empty replies towards the total fields in this section
        return (fieldInSectionWithNonEmptyAnswer.length / s.fields.length) * 100;
    }

    static repoItemIsPersonInvolved(repoItem) {
        return repoItem.repoType.toLowerCase() === 'repoitemperson';
    }

    static repoItemIsRepoItemLearningObject(repoItem) {
        return repoItem.repoType.toLowerCase() === 'repoitemlearningobject';
    }

    static repoItemIsRepoItemResearchObject(repoItem) {
        return repoItem.repoType.toLowerCase() === 'repoitemresearchobject';
    }

    static repoItemIsRepoItemLinkObject(repoItem) {
        return repoItem.repoType.toLowerCase() === 'repoitemlink';
    }

    static repoItemIsAttachment(repoItem) {
        return repoItem.repoType.toLowerCase() === 'repoitemrepoitemfile';
    }

    static repoItemIsDataset(repoItem) {
        return repoItem.repoType.toLowerCase() === "dataset"
    }

    static getStatusColor(repoItem) {
        switch (repoItem.status.toLowerCase()) {
            case 'draft':
                return "#899194";
            case 'published':
                return "#64C3A5";
            case 'submitted':
                return "#F3BA5A";
            case 'revising':
                return "#F3BA5A";
            case 'approved':
                return "#64C3A5";
            case 'declined':
                return "#e87c5d";
            default:
                return "#899194"
        }
    }

    static getStatusText(repoItem) {

        if (repoItem.isRemoved) {
            return i18n.t('publication.state.deleted');
        }

        if (repoItem.isArchived) {
            return i18n.t('publication.state.archived');
        }

        switch (repoItem.status.toLowerCase()) {
            case 'draft':
                return i18n.t('publication.state.draft');
            case 'published':
                return i18n.t('publication.state.published');
            case 'submitted':
                return i18n.t('publication.state.submitted');
            case 'revising':
                return i18n.t('publication.state.revising');
            case 'approved':
                return i18n.t('publication.state.approved');
            case 'declined':
                return i18n.t('publication.state.declined');
            case 'embargo':
                return i18n.t('publication.state.embargo');
            default:
                return "#"
        }
    }

    static getTranslatedRepoType(repoType) {
        let typeTitle = 'Document';
        switch (repoType.toLowerCase()) {
            case 'publicationrecord':
                typeTitle = i18n.t("repoitem.thesis")
                break
            case 'repoitemrepoitemfile':
                typeTitle = i18n.t("repoitem.attachment")
                break;
            case 'learningobject':
                typeTitle = i18n.t("repoitem.teachingmaterials")
                break;
            case 'researchobject':
                typeTitle = i18n.t("repoitem.researchobject")
                break;
            case 'dataset':
                typeTitle = i18n.t("repoitem.dataset")
                break;
            case 'project':
                typeTitle = i18n.t("repoitem.project")
                break;
        }
        return typeTitle
    }
}

export default RepoItemHelper;