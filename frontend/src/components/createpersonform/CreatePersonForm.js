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
import styled from "styled-components";
import {cultured, greyLight, SURFShapeLeft} from "../../Mixins";

function CreatePersonForm(props) {
    const {register, handleSubmit, errors, setValue, getValues, trigger} = useForm();
    const [isLoading, setIsLoading] = useState(false);
    const [emailState, setEmailState] = useState('known')
    const [isInstituteKnown, setIsInstituteKnown] = useState(true);
    const emailStateIsNotDisabled = emailState !== 'no-permission'
    const disableEmailChange = emailState === 'no-permission'
    const emailKnown = (emailState === 'known')
    const history = useHistory()
    const {t} = useTranslation();
    const formSubmitButton = useRef();

    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            trigger();
        }
    }, [emailState])

    useEffect(() => {
        register('institute', {required: isInstituteKnown})

        if (!isInstituteKnown) {
            setValue('institute', null)
        }
    }, [isInstituteKnown]);

    return <div>
        <div hidden={!isLoading}>
            <LoadingIndicator/>
        </div>
        <form id={"create-person"} onSubmit={handleSubmit(createPerson)} hidden={isLoading}>
            <CreatePersonTitle>{t("repoitem.personinvolved_field.create_person")}</CreatePersonTitle>
            <CreatePersonWrapper>
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
                                           validationRegex={"^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$"}
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
                                <FormField key={"email"}
                                           type={"email"}
                                           label={t("person.email")}
                                           isRequired={emailKnown}
                                           readonly={!emailKnown}
                                           error={errors["email"]}
                                           name={"email"}
                                           prefixElement={
                                               <EmailRadioButtons className={"flex-row radio"}
                                                                  onChange={({target: {value}}) => {
                                                                      setValue('email', null)
                                                                      setEmailState(value)
                                                                  }}>
                                                   <input type="radio" id={'email-known'} name={'emailKnown'}
                                                          checked={emailState === 'known'} value={'known'}/>
                                                   <label htmlFor={"email-known"}>{t("email_field.known")}</label>

                                                   <input type="radio" id={'email-unknown'} name={'emailUnknown'}
                                                          checked={emailState === 'unknown'} value={'unknown'}/>
                                                   <label
                                                       htmlFor={"email-unknown"}>{t("email_field.unknown")}</label>

                                                   <input type="radio" id={'email-no-permission'}
                                                          name={'emailNoPermission'}
                                                          checked={emailState === 'no-permission'}
                                                          value={'no-permission'}/>
                                                   <label
                                                       htmlFor={"email-no-permission"}>{t("email_field.no_permission")}</label>
                                               </EmailRadioButtons>
                                           }
                                           register={register}
                                           setValue={setValue}/>
                            </div>
                        </div>
                        <div className={"flex-column"}>
                            <div className={"flex-column form-field-container"}>
                                <FormField key={"institute"}
                                           type={"institute"}
                                           label={t("person.organisation")}
                                           inputHidden={!isInstituteKnown}
                                           isSearchable={true}
                                           isRequired={isInstituteKnown}
                                           error={errors["institute"]}
                                           name={"institute"}
                                           register={register}
                                           setValue={setValue}
                                           prefixElement={
                                               <PrefixRadioButtons className={'flex-row radio'}
                                                                   style={{paddingBottom: '6px'}}
                                                                   onChange={({target: {value}}) => {
                                                                       setIsInstituteKnown(!!parseInt(value))
                                                                   }}>
                                                   <input type='radio' id={'instituteKnown-known'}
                                                          name={'instituteKnown'} checked={isInstituteKnown}
                                                          value={1}/>
                                                   <label htmlFor={'instituteKnown-known'}
                                                          style={{marginRight: '8px'}}>{t('person.instituteKnownLabel')}</label>
                                                   <input type='radio' id={'instituteKnown-unknown'}
                                                          name={'instituteKnown'} checked={!isInstituteKnown}
                                                          value={0}/>
                                                   <label htmlFor={'instituteKnown-unknown'}
                                                          style={{marginRight: '8px'}}>{t('person.instituteUnknownLabel')}</label>
                                               </PrefixRadioButtons>
                                           }
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </CreatePersonWrapper>

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
        formData.isInstituteKnown = isInstituteKnown

        if (!formData.isInstituteKnown) {
            delete formData.institute;
        }

        if (!formData.email && emailKnown) {
            return;
        }
        setIsLoading(true)

        if (!emailKnown) {
            delete formData.email;
        }

        formData['skipEmail'] = !emailKnown
        formData['disableEmailChange'] = disableEmailChange

        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsLoading(false)
            const newlyCreatedPerson = Api.dataFormatter.deserialize(response.data);
            props.onPersonSelect(newlyCreatedPerson)
            props.selectPreviousMode()
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

export const PrefixRadioButtons = styled.fieldset`
    padding-bottom: 6px;

    label {
        font-size: 12px;
        margin-right: 8px;
    }

    input {
        height: 10px;
        width: 10px;
    }
`

export const EmailRadioButtons = styled.fieldset`
    padding-bottom: 6px;

    label {
        font-size: 12px;
        margin-right: 8px;
    }

    input {
        height: 10px;
        width: 10px;
    }
`

export const CreatePersonTitle = styled.h5`
    font-weight: 700;
    font-size: 16px;
    margin-bottom: 6px;
`

export const CreatePersonWrapper = styled.div`
    padding: 10px;
    display: flex;
    flex-direction: column;
    background-color: ${cultured};
    border: 1px solid ${greyLight};
    margin-bottom: 12px;
    ${SURFShapeLeft};
`