import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import React, {useRef, useState} from "react";
import "./exportpopupcontent.scss"
import '../components/field/formfield.scss'
import {useTranslation} from "react-i18next";
import {FormField} from "../components/field/FormField";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import {useForm} from "react-hook-form";
import {OrganisationLevelOptionsEnum} from "../organisation/OrganisationLevelOptionsEnum";
import {RepoItemTypeOptionsEnum} from "../publications/RepoItemTypeOptionsEnum";
import Api from "../util/api/Api";
import {GlobalPageMethods} from "../components/page/Page";
import Toaster from "../util/toaster/Toaster";
import {HelperFunctions} from "../util/HelperFunctions";
import {RadioGroup} from "../components/field/radiogroup/RadioGroup";
import {ExportTypeOptionsEnum} from "./ExportTypeOptionsEnum";

export function ExportPopupContent(props) {
    let radioButtonTypes = ["export", "statistics", "downloads"];
    const defaultExportType = radioButtonTypes[0];

    const [selectedExportType, setSelectedExportType] = useState(defaultExportType);
    const {t} = useTranslation()
    const {register, handleSubmit, formState: {errors}, setValue} = useForm();
    const formSubmitButton = useRef();
    const repoItemTypeOptionsObjects = RepoItemTypeOptionsEnum().map((option) => {
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

    const exportTypeRadioGroupOptions = ExportTypeOptionsEnum().map((option) => {
            return {
                value: option.key,
                labelNL: option.labelNL,
                labelEN: option.labelEN
            }
        }
    );

    let dutchDateRegex = "(^(0?[1-9]|[12][0-9]|3[01])\\-(0?[1-9]|1[012])\\-[0-9]{4})$|^(0?[1-9]|1[012])\\-([0-9]{4})$|^([0-9]{4})$";

    let popupContent = ([
        <div key={"export-title"} className={"export-layer-title"}>
            <h3>{t('report.export')}</h3>
            <form id={"form-export"} onSubmit={handleSubmit(async (data) => await downloadExport(data, props.onCancel))}>
                <div className={"flex-column"}>
                            <div className={"flex-column form-field-container"}>
                                <FormField key={"repoType"}
                                           type={"dropdown"}
                                           label={t("export.publication_type")}
                                           isSmallField={true}
                                           classAddition={'export'}
                                           error={errors && errors["repoType"]}
                                           name={"repoType"}
                                           options={repoItemTypeOptionsObjects}
                                           register={register}
                                           setValue={setValue}/>
                                <FormField key={"institute"}
                                           type={"institute"}
                                           onlyScopedInstitutes={true}
                                           isRequired={true}
                                           isSmallField={true}
                                           classAddition={'export'}
                                           getOptions={HelperFunctions.debounce(institutesCall)}
                                           options={organisationLevelOptionObjects}
                                           label={t("export.institute_level")}
                                           error={errors && errors["institute"]}
                                           name={"institute"}
                                           register={register}
                                           setValue={setValue}/>
                            </div>
                        </div>
                        <div className={"form-row flex-row"}>
                            <div className={"flex-column form-field-container"}>
                                <FormField key={"dateFrom"}
                                           isRequired={true}
                                           type={"datepicker"}
                                           label={t("export.date_start")}
                                           classAddition={'export'}
                                           error={errors && errors["dateFrom"]}
                                           name={"dateFrom"}
                                           register={register}
                                           setValue={setValue}
                                />
                                <FormField key={"dateUntil"}
                                           isRequired={true}
                                           type={"datepicker"}
                                           label={t("export.date_until")}
                                           classAddition={'export'}
                                           error={errors && errors["dateUntil"]}
                                           name={"dateUntil"}
                                           register={register}
                                           setValue={setValue}
                                />
                            </div>
                        </div>
                        <div className={"form-row flex-row"}>
                            <div className={"flex-column form-field-container radio-group-container"}>
                                <label className={"field-label-radio-group"}>
                                    {t("report.export_type")}
                                </label>
                                <RadioGroup
                                    className={"export-radio-group"}
                                    name={"radio-group-type"}
                                    readonly={false}
                                    options={exportTypeRadioGroupOptions}
                                    defaultValue={defaultExportType}
                                    onChange={(change) => {
                                        setSelectedExportType(change)
                                    }}
                                />
                            </div>
                </div>
                <div className={"save-button-wrapper"}>
                    <button type="submit"
                            form="form-export"
                            ref={formSubmitButton}
                            style={{display: "none"}}/>
                    <ButtonText text={t('report.export')}
                                buttonType={"callToAction"}
                                onClick={() => {
                                    formSubmitButton.current.click();
                                }}/>
                </div>
            </form>
        </div>
    ])

    return (
        <div className={"export-popup-content-wrapper"}>
            <div className={"export-popup-content"}>
                <div className={"close-button-container"}
                     onClick={props.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                {popupContent}
            </div>
        </div>
    )

    function downloadExport(formData, onCancel) {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            params: {}
        };

        if (formData.institute) {
            config.params['filter[scope]'] = formData.institute
        }
        if (formData.repoType) {
            config.params['filter[repoType]'] = formData.repoType;
        }
        if (formData.repoType) {
            config.params['filter[repoType]'] = formData.repoType;
        }

        config.params['reportType'] = selectedExportType

        let objectType = 'repoItems'
        if (selectedExportType === 'downloads') {
            objectType = 'statsDownloads';
            if (formData.dateFrom) {
                config.params['filter[downloadDate][GE]'] = formData.dateFrom;
            }
            if (formData.dateUntil) {
                config.params['filter[downloadDate][LE]'] = formData.dateUntil
            }
        } else {
            config.params['filter[isRemoved]'] = 0;

            if (formData.dateFrom) {
                config.params['filter[publicationDate][GE]'] = formData.dateFrom
            }
            if (formData.dateUntil) {
                config.params['filter[publicationDate][LE]'] = formData.dateUntil
            }
        }

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            onCancel()
        }

        const errorCallback = (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        }

        function onLocalFailure(error) {
            errorCallback(error);
        }

        function onServerFailure(error) {
            errorCallback(error);
        }

        Api.post('csv/' + objectType, onValidate, onSuccess, onLocalFailure, onServerFailure, config)
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
            'fields[institutes]': 'title',
            'filter[level]': 'organisation,consortium'
        }
    };

    if (searchQuery.length > 0) {
        config.params['filter[title][LIKE]'] = '%' + searchQuery + '%'
    }

    Api.jsonApiGet('institutes', onValidate, onSuccess, onFailure, onFailure, config);
}
