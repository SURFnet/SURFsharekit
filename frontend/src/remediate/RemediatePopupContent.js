import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import React, {useRef, useState} from "react";
import "./remediatepopupcontent.scss"
import '../components/field/formfield.scss'
import {useTranslation} from "react-i18next";
import {FormField, Required} from "../components/field/FormField";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import {useForm} from "react-hook-form";
import {OrganisationLevelOptionsEnum} from "../organisation/OrganisationLevelOptionsEnum";
import {RepoItemTypePluralOptionsEnum} from "../publications/RepoItemTypeOptionsEnum";
import Api from "../util/api/Api";
import {GlobalPageMethods} from "../components/page/Page";
import Toaster from "../util/toaster/Toaster";
import {HelperFunctions} from "../util/HelperFunctions";
import {RadioGroup} from "../components/field/radiogroup/RadioGroup";
import {RemediateTypeOptionsEnum} from "./RemediateTypeOptionsEnum";
import VerificationPopup from "../verification/VerificationPopup";
import i18n from "i18next";
import {RemediateProgressPopup} from "./RemediateProgressPopup";

export function RemediatePopupContent(props) {
    const remediateTypeRadioGroupOptions = RemediateTypeOptionsEnum().map((option) => {
            return {
                value: option.key,
                labelNL: option.labelNL,
                labelEN: option.labelEN
            }
        }
    );
    const defaultRemediateType = remediateTypeRadioGroupOptions[0].value;
    const statusOptionObjects = [
        {
            value: "published",
            labelNL: "Gepubliceerd",
            labelEN: "Published"
        },
        {
            value: "archived",
            labelNL: "Gearchiveerd",
            labelEN: "Archived"
        },
        {
            value: "draft",
            labelNL: "Concept",
            labelEN: "Draft"
        }
    ];

    const [selectedRemediateType, setSelectedRemediateType] = useState(defaultRemediateType);
    const {t} = useTranslation()
    const {register, handleSubmit, errors, setValue, getValues, trigger} = useForm();
    const formSubmitButton = useRef();
    const repoItemTypeOptionsObjects = RepoItemTypePluralOptionsEnum().map((option) => {
            return {
                value: option.key,
                labelNL: option.labelNL,
                labelEN: option.labelEN
            }
        }
    );
    const organisationLevelOptionObjects = OrganisationLevelOptionsEnum().map((option) => {
            return {
                value: option.key,
                labelNL: option.labelNL,
                labelEN: option.labelEN
            }
        }
    );

    let dutchDateRegex = "(^(0?[1-9]|[12][0-9]|3[01])\\-(0?[1-9]|1[012])\\-[0-9]{4})$|^(0?[1-9]|1[012])\\-([0-9]{4})$|^([0-9]{4})$";

    let popupContent = ([
        <div key={"remediate-title"} className={"remediate-layer-title"}>
            <h3>{t('report.remediate')}</h3>
            <form id={"form-remediate"} onSubmit={handleSubmit(downloadRemediate)}>
                <div className={"flex-column"}>
                    <div className={"form-column flex-column"}>
                        <div className={"form-row flex-row form-field-container"}>
                            <FormField key={"status"}
                                       type={"dropdown"}
                                       isSmallField={true}
                                       classAddition={''}
                                       isRequired={true}
                                       options={statusOptionObjects}
                                       label={t("remediate.status")}
                                       error={errors["status"]}
                                       name={"status"}
                                       register={register}
                                       setValue={setValue}/>
                            <FormField key={"repoType"}
                                       type={"dropdown"}
                                       label={t("remediate.publication_type")}
                                       isSmallField={true}
                                       isRequired={true}
                                       classAddition={''}
                                       error={errors["repoType"]}
                                       name={"repoType"}
                                       options={repoItemTypeOptionsObjects}
                                       register={register}
                                       setValue={setValue}/>
                        </div>
                        <div className={"form-row flex-row"}>
                            <div className={"flex-column form-field-container"}>
                                <FormField key={"institutes"}
                                           type={"multiselectdropdown"}
                                           isSmallField={true}
                                           classAddition={''}
                                           isRequired={true}
                                           getOptions={HelperFunctions.debounce(institutesCall)}
                                           options={organisationLevelOptionObjects}
                                           label={t("remediate.institute_level")}
                                           error={errors["institutes"]}
                                           name={"institutes"}
                                           register={register}
                                           setValue={setValue}/>
                            </div>
                        </div>
                        <div className={"form-row flex-row"}>
                            <div className={"flex-column form-field-container"}>
                                <FormField key={"dateFrom"}
                                           type={"text"}
                                           isSmallField={true}
                                           classAddition={''}
                                           label={t("remediate.date_start")}
                                           error={errors["dateFrom"]}
                                           name={"dateFrom"}
                                           isRequired={true}
                                           validationRegex={dutchDateRegex}
                                           tooltip={t("remediate.date_format_tooltip")}
                                           register={register}
                                           setValue={setValue}/>
                                <FormField key={"dateUntil"}
                                           type={"text"}
                                           isSmallField={true}
                                           classAddition={''}
                                           label={t("remediate.date_until")}
                                           error={errors["dateUntil"]}
                                           name={"dateUntil"}
                                           tooltip={t("remediate.date_format_tooltip")}
                                           isRequired={true}
                                           validationRegex={dutchDateRegex}
                                           register={register}
                                           setValue={setValue}/>
                            </div>
                        </div>
                        <div className={"form-row flex-row"}>
                            <div className={"flex-column form-field-container radio-group-container"}>
                                <label className={"field-label-radio-group"}>
                                    <Required/>
                                    {t("remediate.remediate_type")}
                                </label>
                                <RadioGroup
                                    className={"remediate-radio-group"}
                                    name={"radio-group-type"}
                                    readonly={false}
                                    isRequired={true}
                                    register={register}
                                    options={remediateTypeRadioGroupOptions}
                                    defaultValue={defaultRemediateType}
                                    onChange={(change) => {
                                        setSelectedRemediateType(change)
                                    }}
                                />
                                <div className="form-field-container">
                                    <div className="form-field">
                                        <div
                                            className={"field-error " + (selectedRemediateType ? 'hidden' : '')}>{i18n.t('error_message.field_required')}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className={"save-button-wrapper"}>
                    <button type="submit"
                            form="form-remediate"
                            ref={formSubmitButton}
                            style={{display: "none"}}/>
                    <ButtonText text={t('remediate.start')}
                                buttonType={"callToAction"}
                                onClick={() => {
                                    formSubmitButton.current.click();
                                }}/>
                </div>
            </form>
        </div>
    ])

    return (
        <div className={"remediate-popup-content-wrapper"}>
            <div className={"remediate-popup-content"}>
                <div className={"close-button-container"}
                     onClick={props.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                {popupContent}
            </div>
        </div>
    )

    function downloadRemediate(formData) {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            params: {}
        };

        function getFullEnglishDateFrom(dutchDateString, getLastDate) {
            const dutchDateParts = dutchDateString.split("-"); //jjjj-mm-dd , jjjj-mm or jjjj
            const englishDateParts = dutchDateParts.reverse(); //dd-mm-yyyy, mm-yyyy or yyyy
            if (englishDateParts.length < 2) {
                englishDateParts.push(getLastDate ? '12' : '01');
            }
            if (englishDateParts.length < 3) {
                if (getLastDate) {
                    let firstDayOfNextMonth = new Date(englishDateParts[0], parseInt(englishDateParts[1]), 0);
                    englishDateParts.push(firstDayOfNextMonth.getDate());
                } else {
                    englishDateParts.push('01');
                }
            }
            return englishDateParts.join("-");
        }

        config.params['filter[scope][EQ]'] = formData.institutes.map(i => i.value).reduce((total, cv) => total + (total === '' ? '' : ',') + cv, '')
        config.params['filter[repoType][EQ]'] = formData.repoType;
        config.params['filter[isRemoved][EQ]'] = 0;
        config.params['filter[publicationDate][GE]'] = getFullEnglishDateFrom(formData.dateFrom, false);
        config.params['filter[publicationDate][LE]'] = getFullEnglishDateFrom(formData.dateUntil, true);

        let filterJson = {
            'scope': {
                'EQ': config.params['filter[scope][EQ]']
            },
            'repoType': {
                'EQ': config.params['filter[repoType][EQ]']
            },
            'isRemoved': {
                'EQ': config.params['filter[isRemoved][EQ]']
            },
            'publicationDate': {
                'GE': config.params['filter[publicationDate][GE]'],
                'LE': config.params['filter[publicationDate][LE]']
            }
        }

        if(formData.status !== 'archived'){
            config.params['filter[status][EQ]'] = formData.status;
            config.params['filter[isArchived][EQ]'] = 0;
            filterJson.status = {
                'EQ': config.params['filter[status][EQ]']
            };
            filterJson.isArchived = 0;
        }else{
            config.params['filter[isArchived][EQ]'] = 1;
            filterJson.isArchived = 1;
        }

        config.params['page[size]'] = 1;
        config.params['page[number]'] = 1;

        validateFilter(formData, config, (count, status, repoType, institutes, action) => {
            remediatePublications(formData, filterJson, count, status, repoType, institutes, action)
        })
    }

    function remediatePublications(formData, filterJson, count, status, repoType, institutes, action) {
        let bulkActionEnum = ""
        let simplePastTenseActions = ""
        if (selectedRemediateType === "depublish") {
            bulkActionEnum = "DEPUBLISH"
            simplePastTenseActions = t('remediate.depublishing')
        } else if (selectedRemediateType === "archive") {
            bulkActionEnum = "ARCHIVE"
            simplePastTenseActions = t('remediate.archiving')
        } else if (selectedRemediateType === "delete") {
            bulkActionEnum = "DELETE"
            simplePastTenseActions = t('remediate.deleting')
        } else {
            return
        }

        const newBulkActions = {
            "data": {
                "type": "bulkaction",
                "attributes": {
                    "filters": filterJson,
                    "action": bulkActionEnum
                }
            }
        }


        function onValidate(response) {
        }

        function onSuccess(response) {
            const bulkAction = Api.dataFormatter.deserialize(response.data);
            if (bulkAction.totalCount > 0) {
                RemediateProgressPopup.show(bulkAction.id, count, repoType, simplePastTenseActions);
            }
        }

        function onLocalFailure(error) {
            console.log(error);
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            console.log(error);
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        }

        Api.post('/bulkactions', onValidate, onSuccess, onLocalFailure, onServerFailure, {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }, newBulkActions)
    }

    function validateFilter(formData, config, onConfirm) {
        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            let instituteString = ""

            if (t('language.current_code') === 'nl') {
                instituteString = formData.institutes.map(i => i.label).join(', ')
            } else {
                instituteString = formData.institutes.map(i => i.label).join(', ')
            }

            let remediateActions = remediateTypeRadioGroupOptions.find(o => o.value === selectedRemediateType)
            let actionString = t('language.current_code') === 'nl' ? remediateActions.labelNL : remediateActions.labelEN

            let status = statusOptionObjects.find(o => o.value === formData.status)
            let statusString = t('language.current_code') === 'nl' ? status.labelNL : status.labelEN

            let repoType = repoItemTypeOptionsObjects.find(o => o.value === formData.repoType)
            let repoTypeString = t('language.current_code') === 'nl' ? repoType.labelNL : repoType.labelEN

            VerificationPopup.show(t("verification.remediate.title"), t("verification.remediate.message", {
                count: response.data.meta.totalCount,
                status: statusString.toLowerCase(),
                repoType: repoTypeString,
                institutes: instituteString,
                action: actionString.toLowerCase()
            }), () => {
                onConfirm(
                    response.data.meta.totalCount,
                    statusString.toLowerCase(),
                    repoTypeString,
                    instituteString,
                    actionString.toLowerCase())
            })
        }

        function onLocalFailure(error) {
            console.log(error);
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            console.log(error);
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        }

        Api.get('repoItems/', onValidate, onSuccess, onLocalFailure, onServerFailure, config)
    }
}

export const institutesCall = function (searchQuery = '', callback = () => {

}) {
    const mapper = (resultOption) => {
        return {
            "label": resultOption.title,
            "value": resultOption.id
        }
    };

    function onValidate(response) {
    }

    function onSuccess(response) {
        const newOptions = response.data.map(mapper);
        callback(newOptions)
    }

    function onFailure(error) {
        callback([])
    }

    const config = {
        params: {
            'page[size]': 10,
            'page[number]': 1,
            'fields[institutes]': 'title'
        }
    };

    if (searchQuery.length > 0) {
        config.params['filter[title][LIKE]'] = '%' + searchQuery + '%'
    }

    Api.jsonApiGet('institutes', onValidate, onSuccess, onFailure, onFailure, config);
}