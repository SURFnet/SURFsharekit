import i18n from "i18next";
import Api from "./api/Api";
import {useTranslation} from "react-i18next";

export class HelperFunctions {

    static debounce(func, wait = 500, immediate = false) {
        let timeout;
        return function () {
            let context = this, args = arguments;
            let later = function () {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            let callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    static sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms))
    }

    static getMemberRootInstitutes(member) {
        const instituteIdArray = []
        //Get institutes and prevent duplicates
        let memberInstitutes = member.groups.map((group) => {
            if (group.partOf && group.partOf.isBaseScopeForUser && !instituteIdArray.includes(group.partOf.id)) {
                instituteIdArray.push(group.partOf.id)
                return group.partOf
            }
            return null;
        })

        //Filter null values
        memberInstitutes = memberInstitutes.filter(institute =>
            institute !== null
        ).map(institute => {
            return institute
        });

        return memberInstitutes
    }

    static getMemberDefaultInstitute(member) {

        const defaultInstituteIdArray = []
        //Get institutes and prevent duplicates
        let defaultMemberInstitutes = member.groups.map((group) => {
            if (group.partOf && group.partOf.isUsersConextInstitute && !defaultInstituteIdArray.includes(group.partOf.id)) {
                defaultInstituteIdArray.push(group.partOf.id)
                return group.partOf
            }
            return null;
        })

        //Filter null values
        defaultMemberInstitutes = defaultMemberInstitutes.filter(institute =>
            institute !== null
        ).map(institute => {
            return institute
        });

        //There should be only 1 default institute, so return the first result.
        return defaultMemberInstitutes && defaultMemberInstitutes.length > 0 ? defaultMemberInstitutes[0] : null
    }

    static getDateFormat(date, options) {
        let dateString = date;
        if(date && date.length > 0) {
            const result = date.split(" ")
            //Fixes crash on Safari. This fix places a 'T' between each date part, so '2020-10-06 08:41:59' becomes '2020-10-06T08:41:59'
            if(result.length === 2) {
                dateString = result.join("T");
            }
        }

        const dateObj = new Date(dateString);
        const dateTimeFormat = new Intl.DateTimeFormat(i18n.t('language.current_code'), options);
        const f_date = (m_ca, m_it) => Object({...m_ca, [m_it.type]: m_it.value});
        return dateTimeFormat.formatToParts(dateObj).reduce(f_date, {});
    }

    static getGetOptionsCallForFieldKey(fieldKey, mapper) {
        return function (searchQuery = '', callback = () => {
        }) {

            function onValidate(response) {
            }

            function onSuccess(response) {
                const newOptions = response.data.map(mapper);
                callback(newOptions)
            }

            function onFailure(error) {
                callback([])
            }

            let labelSort = i18n.t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN';


            const config = {
                params: {
                    'filter[FieldKey][EQ]': fieldKey,
                    'filter[IsRemoved][EQ]': 0,
                    'sort': labelSort,
                    'page[size]': 50,
                    'page[number]': 1,
                }
            };

            if (searchQuery.length > 0) {
                config.params['filter[Value][LIKE]'] = '%' + searchQuery + '%'
            }

            Api.jsonApiGet('metaFieldOptions', onValidate, onSuccess, onFailure, onFailure, config);
        }
    }
}