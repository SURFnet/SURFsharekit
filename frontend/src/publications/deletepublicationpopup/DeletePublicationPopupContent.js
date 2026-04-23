import {useTranslation} from "react-i18next";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import ButtonText from "../../components/buttons/buttontext/ButtonText";
import React, {useRef} from "react";
import {FormField} from "../../components/field/FormField";
import {useForm} from "react-hook-form";
import SURFButton from "../../styled-components/buttons/SURFButton";

export function DeletePublicationPopupContent(props) {
    const {t} = useTranslation();
    const formRef = useRef(null)
    const {register, handleSubmit, formState: { errors}} = useForm()

    const submit = (data) => {
        props.onConfirm(data.reason)
    }

    return (
        <div className={"delete-publication-popup-content-wrapper"}>
            <form
                id={'delete-publication-form'}
                  ref={formRef}
                  className={"delete-publication-popup-content"}
                  onSubmit={handleSubmit(submit)}
            >
                <div className={"close-button-container"}
                     onClick={props.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                <div className={"delete-publication-title"}>
                    {t('verification.repoItem.delete_request.title')}
                </div>
                <div className={"delete-publication-subtitle"}>
                    {t('verification.repoItem.delete_request.subtitle')}
                </div>
                <div className={"form-field-container"}>
                    <FormField
                        label={t('verification.repoItem.delete_request.reason')}
                        type={'textarea'}
                        name={'reason'}
                        id={'reason'}
                        error={errors['reason']}
                        register={register}
                        isRequired={true}
                    />
                </div>
            </form>
            <div className={'button-wrapper'}>
                <ButtonText text={t('action.cancel')}
                            onClick={() => {
                                props.onCancel();
                            }}/>
                <ButtonText text={t('action.confirm')} buttonType={"callToAction"} onClick={() => {
                    formRef.current.requestSubmit();
                }} />
            </div>
        </div>
    )
}