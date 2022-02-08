import FormFieldHelper from "../FormFieldHelper";
import RepoItemHelper from "../RepoItemHelper";

class FormFieldFiles {

    static getFormFilesToUpload(repoItem, formData) {
        const formFieldHelper = new FormFieldHelper();
        let filesToUpload = [];

        RepoItemHelper.getAllFields(repoItem).forEach(field => {
            const fieldType = formFieldHelper.getFieldType(field.fieldType);

            if (fieldType === "file") {
                if (formData[field.key] !== null) {
                    const file = formData[field.key];
                    file.fieldKey = field.key;
                    filesToUpload.push(file);
                }
            }
        });
        return filesToUpload;
    }

    static setFormFileValues(repoItem, formData, answers) {
        const formFieldHelper = new FormFieldHelper();

        RepoItemHelper.getAllFields(repoItem).forEach(field => {
            const fieldType = formFieldHelper.getFieldType(field.fieldType);
            if (fieldType === "file") {
                if (formData[field.key] !== null && formData[field.key] === undefined) {
                    if (answers[field.key] !== undefined) {
                        formData[field.key] = answers[field.key].value;
                    }
                }
            }
        });

        return formData;
    }
}

export default FormFieldFiles;