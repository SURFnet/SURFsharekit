import React, {useEffect, useRef, useState} from "react";
import './createpersonform.scss'
import '../field/formfield.scss'
import {useForm} from "react-hook-form";
import {FormField, Required, Tooltip} from "../field/FormField";
import {useTranslation} from "react-i18next";
import ButtonText from "../buttons/buttontext/ButtonText";
import LoadingIndicator from "../loadingindicator/LoadingIndicator";
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import {Mod11Helper} from "../../util/Mod11Helper"
import {useHistory} from "react-router-dom";

function CreatePersonForm(props) {
    const {register, handleSubmit, errors, setValue, getValues, trigger} = useForm();
    const [isLoading, setIsLoading] = useState(false);
    const [isEmailUnknown, setIsEmailUnknown] = useState(false);
    const history = useHistory()
    const {t} = useTranslation();
    const formSubmitButton = useRef();

    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            trigger();
        }
    }, [isEmailUnknown])

    return <div>
        <div hidden={!isLoading}>
            <LoadingIndicator/>
        </div>
        <form id={"create-person"} onSubmit={handleSubmit(createPerson)} hidden={isLoading}>
            <div className={"flex-column"}>
                <div className={"form-column flex-column"}>
                    <div className={"form-row flex-row form-field-container"}>
                        <FormField key={"title"}
                                   classAddition={''}
                                   type={"text"}
                                   label={t("person.titulatuur")}
                                   isRequired={false}
                                   isSmallField={true}
                                   error={errors["title"]}
                                   name={"title"}
                                   register={register}
                                   setValue={setValue}
                        />
                        <FormField key={"firstName"}
                                   type={"text"}
                                   label={t("person.firstName")}
                                   isSmallField={true}
                                   classAddition={''}
                                   isRequired={true}
                                   error={errors["firstName"]}
                                   name={"firstName"}
                                   register={register}
                                   setValue={setValue}/>
                        <FormField key={"surnamePrefix"}
                                   type={"text"}
                                   isSmallField={true}
                                   classAddition={''}
                                   label={t("person.surnamePrefix")}
                                   error={errors["surnamePrefix"]}
                                   name={"surnamePrefix"}
                                   register={register}
                                   setValue={setValue}/>
                        <FormField key={"surname"}
                                   type={"text"}
                                   isSmallField={true}
                                   isRequired={true}
                                   classAddition={''}
                                   label={t("person.surname")}
                                   error={errors["surname"]}
                                   name={"surname"}
                                   register={register}
                                   setValue={setValue}/>
                    </div>
                    <div className={"form-row flex-row"}>
                        <div className={"flex-column form-field-container"}>
                            <FormField key={"persistentIdentifier"}
                                       classAddition={''}
                                       type={"text"}
                                       hardHint={"info:eu-repo/dai/nl/"}
                                       label={t("profile.profile_persistent_identifier")}
                                       isRequired={false}
                                       extraValidation={Mod11Helper.mod11Validator}
                                       validationRegex={"^[0-9]{8,9}[0-9X]$"}
                                       error={errors["persistentIdentifier"]}
                                       name={"persistentIdentifier"}
                                       register={register}
                                       setValue={setValue}
                            />
                            <Tooltip text={t("profile.tooltips.dai")}/>
                        </div>
                        <div className={"flex-column form-field-container"}>
                            <FormField key={"orcid"}
                                       classAddition={''}
                                       type={"text"}
                                       label={t("profile.profile_orcid")}
                                       isRequired={false}
                                       extraValidation={Mod11Helper.mod11_2Validator}
                                       error={errors["orcid"]}
                                       name={"orcid"}
                                       hardHint={"http://orcid.org/"}
                                       validationRegex={"^[0]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$"}
                                       register={register}
                                       setValue={setValue}
                            />
                            <Tooltip text={t("profile.tooltips.orcid")}/>
                        </div>
                        <div className={"flex-column form-field-container"}>
                            <FormField key={"isni"}
                                       classAddition={''}
                                       type={"text"}
                                       hardHint={"https://isni.org/isni/"}
                                       label={t("profile.profile_isni")}
                                       isRequired={false}
                                       extraValidation={Mod11Helper.mod11_2Validator}
                                       validationRegex={"^[0]{4}[0-9]{4}[0-9]{4}[0-9]{3}[0-9X]$"}
                                       error={errors["isni"]}
                                       name={"isni"}
                                       register={register}
                                       setValue={setValue}
                            />
                            <Tooltip text={t("profile.tooltips.isni")}/>
                        </div>
                    </div>
                    <div className={"form-row flex-row"}>
                        <div className={"flex-column form-field-container"}>
                            {!isEmailUnknown && <Required/>}
                            <FormField key={"email"}
                                       type={"email"}
                                       label={t("person.email")}
                                       isRequired={!isEmailUnknown}
                                       hideRequired={!isEmailUnknown}
                                       error={errors["email"]}
                                       name={"email"}
                                       register={register}
                                       setValue={setValue}/>
                            <FormField key={"institute"}
                                       type={"institute"}
                                       label={t("person.organisation")}
                                       isSearchable={true}
                                       isRequired={true}
                                       error={errors["institute"]}
                                       name={"institute"}
                                       register={register}
                                       setValue={setValue}/>
                        </div>
                    </div>
                    <div className={"form-row flex-row"}>
                        <div className={"flex-column form-field-container"}>
                            <FormField key={"skipEmail"}
                                       type={"checkbox"}
                                       options={[{
                                           value: 1,
                                           labelNL: t('person.skipEmailLabel'),
                                           labelEN: t('person.skipEmailLabel')
                                       }]}
                                       label={"\u00a0"}
                                       isRequired={false}
                                       onValueChanged={v => {
                                           if (v.length > 0) {
                                               setValue('email', undefined)
                                               setIsEmailUnknown(true)
                                           } else {
                                               setIsEmailUnknown(false)
                                           }
                                       }}
                                       error={errors["skipEmail"]}
                                       name={"skipEmail"}
                                       register={register}
                                       setValue={setValue}/>
                        </div>
                    </div>
                </div>
            </div>
            <div className={"save-button-wrapper"}>
                <button type="submit"
                        form="create-person"
                        ref={formSubmitButton}
                        style={{display: "none"}}/>
                <ButtonText text={t('action.next')}
                            buttonType={"callToAction"}
                            onClick={() => {
                                formSubmitButton.current.click();
                            }}/>
            </div>
        </form>
    </div>

    function createPerson(formData) {
        if (!formData.email && !formData.skipEmail) {
            return;
        }
        setIsLoading(true)

        if (formData.skipEmail) {
            delete formData.email;
        }

        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsLoading(false)
            const newlyCreatedPerson = Api.dataFormatter.deserialize(response.data);
            props.setSelectedPerson(newlyCreatedPerson)
        }

        function onLocalFailure(error) {
            setIsLoading(false)
            Toaster.showDefaultRequestError();
        }

        function onServerFailure(error) {
            setIsLoading(false)
            Toaster.showDefaultRequestError(t('error_message.duplicate_email'));
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const newPersonObject = {
            "data": {
                "type": "person",
                "attributes": formData
            }
        }
        Api.post('/persons', onValidate, onSuccess, onLocalFailure, onServerFailure, config, newPersonObject)
    }
}

export default CreatePersonForm;