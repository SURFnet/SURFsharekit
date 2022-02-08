import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import React, {useRef, useState} from "react";
import "./addorganisationlayerpopupcontent.scss"
import '../../components/field/formfield.scss'
import ButtonText from "../../components/buttons/buttontext/ButtonText";
import {useTranslation} from "react-i18next";
import {FormField} from "../../components/field/FormField";
import {useForm} from "react-hook-form";
import Toaster from "../../util/toaster/Toaster";
import Api from "../../util/api/Api";
import ValidationError from "../../util/ValidationError";
import LoadingIndicator from "../../components/loadingindicator/LoadingIndicator";
import {OrganisationLevelOptionsEnum} from "../OrganisationLevelOptionsEnum";
import {useHistory} from "react-router-dom";

export function AddOrganisationLayerContent(props) {

    const [isLoading, setIsLoading] = useState(false);
    const {t} = useTranslation()
    const {register, handleSubmit, errors, setValue, getValues, trigger} = useForm();
    const formSubmitButton = useRef();
    const history = useHistory()
    const formTitle = props.isEditing ? t('organisation.add_layer_popup.edit_title') : t('organisation.add_layer_popup.add_title');
    const formSubtitle = props.isEditing ? t('organisation.add_layer_popup.part_of') : t('organisation.add_layer_popup.add_to');
    const formSubmitButtonTitle = props.isEditing ? t('organisation.add_layer_popup.edit_save_button') : t('organisation.add_layer_popup.add_button');

    const organisationLevelOptionObjects = OrganisationLevelOptionsEnum().map((option) => {
            return {
                value: option.key,
                labelNL: option.labelNL,
                labelEN: option.labelEN
            }
        }
    );

    let popupContent

    if (isLoading) {
        popupContent = <LoadingIndicator/>
    } else {
        popupContent = ([
            <div key={"add-organisation-layer-title"} className={"add-organisation-layer-title"}>
                <h3>{formTitle}</h3>
                <h4>{formSubtitle} {props.institute.title}</h4>
            </div>,
            <form key={"add-organisation-layer-form"} id={"add-organisation-layer-form"}
                  onSubmit={handleSubmit(handleOnSubmitForm)}>
                <div className={"flex-column"}>
                    <div className={"form-columns-container flex-row"}>
                        <div className={"flex-column form-field-container"}>
                            <FormField key={"organisationLayerName"}
                                       type={"text"}
                                       label={t("organisation.add_layer_popup.organisation_layer_name")}
                                       isRequired={true}
                                       error={errors["organisationLayerName"]}
                                       name={"organisationLayerName"}
                                       register={register}
                                       setValue={setValue}
                                       defaultValue={props.isEditing ? props.institute.title : null}
                            />
                        </div>
                    </div>
                    <div className={"form-columns-container flex-row"}>
                        <div className={"flex-column form-field-container"}>
                            <FormField key={"organisationLevel"}
                                       type={"dropdown"}
                                       label={t("organisation.add_layer_popup.organisation_level")}
                                       options={organisationLevelOptionObjects}
                                       isRequired={false}
                                       error={errors["organisationLevel"]}
                                       name={"organisationLevel"}
                                       tooltip={t("organisation.add_layer_popup.organisation_level_tooltip")}
                                       register={register}
                                       setValue={setValue}
                                       defaultValue={props.isEditing ? props.institute.level : null}
                            />
                        </div>
                    </div>
                    <div className={"form-columns-container flex-row"}>
                        <div className={"flex-column form-field-container"}>
                            <FormField key={"organisationAbbreviation"}
                                       type={"text"}
                                       label={t("organisation.add_layer_popup.organisation_abbreviation")}
                                       isRequired={false}
                                       error={errors["organisationAbbreviation"]}
                                       name={"organisationAbbreviation"}
                                       register={register}
                                       setValue={setValue}
                                       defaultValue={props.isEditing ? props.institute.abbreviation : null}
                            />
                        </div>
                    </div>
                    <div className={"save-button-wrapper"}>
                        <button type="submit"
                                form="add-organisation-layer-form"
                                ref={formSubmitButton}
                                style={{display: "none"}}/>
                        <ButtonText text={formSubmitButtonTitle}
                                    buttonType={"callToAction"}
                                    disabled={false}
                                    onClick={() => {
                                        formSubmitButton.current.click();
                                    }}/>
                    </div>
                </div>
            </form>
        ])
    }

    return (
        <div className={"add-organisation-layer-popup-content-wrapper"}>
            <div className={"add-organisation-layer-popup-content"}>
                <div className={"close-button-container"}
                     onClick={props.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                {popupContent}
            </div>
        </div>
    )

    function handleOnSubmitForm(formData) {
        createOrEditOrganisationLayer(props.institute, formData, props.isEditing, (savedInstitute, isEditing) => {
            props.onSuccessfulSave(savedInstitute, isEditing);
        })
    }

    function createOrEditOrganisationLayer(institute, formData, isEditing = false, successCallback = () => {}, errorCallback = () => {}) {
        setIsLoading(true);

        function validator(response) {
            const instituteData = response.data ? response.data.data : null;
            if (!(instituteData && instituteData.id && instituteData.attributes)) {
                errorCallback()
                throw new ValidationError("The received institute data is invalid")
            }
        }

        function onSuccess(response) {
            setIsLoading(false);

            const responseData = response.data.data
            //Use current institute when editing, create a new one otherwise
            let savedInstitute = isEditing ? institute : {};
            savedInstitute.id = responseData.id;
            savedInstitute.type = responseData.attributes.type;
            savedInstitute.title = responseData.attributes.title;
            savedInstitute.abbreviation = responseData.attributes.abbreviation;
            savedInstitute.level = responseData.attributes.level;
            savedInstitute.childrenInstitutesCount = responseData.attributes.childrenInstitutesCount;
            savedInstitute.isRemoved = responseData.attributes.isRemoved;
            savedInstitute.permissions = responseData.attributes.permissions;
            savedInstitute.childrenInstitutes = responseData.relationships.childrenInstitutes.data;
            savedInstitute.parentInstitute = responseData.relationships.parentInstitute.data;

            successCallback(savedInstitute, isEditing)
        }

        function onLocalFailure(error) {
            setIsLoading(false);
            console.log(error);
            Toaster.showDefaultRequestError()
            errorCallback()
        }

        function onServerFailure(error) {
            setIsLoading(false);
            console.log(error.response.status);
            Toaster.showServerError(error)
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
            errorCallback()
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const data = {
            "data": {
                "type": "institute",
                "attributes": {
                    "type": institute.type,
                    "title": formData.organisationLayerName,
                    "abbreviation": formData.organisationAbbreviation,
                    "level": formData.organisationLevel
                }
            }
        };

        if (isEditing) {
            data.data.id = institute.id;
            Api.patch('institutes/' + institute.id, validator, onSuccess, onLocalFailure, onServerFailure, config, data);
        } else {
            data.data.relationships = {
                "parentInstitute": {
                    "data": {
                        "type": "institute",
                        "id": institute.id
                    }
                }
            }
            Api.post('institutes', validator, onSuccess, onLocalFailure, onServerFailure, config, data);
        }
    }
}