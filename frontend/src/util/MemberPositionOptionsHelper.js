import i18n from "../i18n";

class MemberPositionOptionsHelper {

    constructor() {
        this.nlLng = i18n.getFixedT('nl')
        this.enLng = i18n.getFixedT('en')
    }

    getOption(key, lngKey) {
        return {
            value: key,
            labelNL: this.nlLng(lngKey),
            labelEN: this.enLng(lngKey)
        }
    }

    getPositionOptions() {
        return Object.keys(roleKeyToTranslationKey).map(rk => this.getOption(rk, roleKeyToTranslationKey[rk]))
    }
}

export default MemberPositionOptionsHelper;

export const roleKeyToTranslationKey = {
    "role-lecturer": "profile.profile_function_options.role_lecturer",
    "teacher": "profile.profile_function_options.teacher",
    "researcher": "profile.profile_function_options.researcher",
    "student": "profile.profile_function_options.student",
    "staff-employee": "profile.profile_function_options.staff_employee",
    "associate-lecturer": "profile.profile_function_options.associate_lecturer",
    "member-lectureship": "profile.profile_function_options.member_lectureship",
    "phd": "profile.profile_function_options.phd",
    "other": "profile.profile_function_options.other"
}