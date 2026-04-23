import {useTranslation} from "react-i18next";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import ButtonText from "../../components/buttons/buttontext/ButtonText";
import React, {useRef} from "react";
import {FormField} from "../../components/field/FormField";
import {useForm} from "react-hook-form";

export function RestorePublicationPopupContent(props) {
    const {t} = useTranslation();
    const formRef = useRef(null)
    const {register, handleSubmit, formState: { errors}} = useForm()

    const submit = (data) => {
        props.onConfirm(props.source === 'tasks' ? data.reason : null)
    }

    return (
        <div className={"restore-publication-popup-content-wrapper"}>
            <form
                id={'restore-publication-form'}
                ref={formRef}
                className={"restore-publication-popup-content"}
                onSubmit={handleSubmit(submit)}
            >
                <div className={"close-button-container"}
                     onClick={props.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                <h3 className={"restore-publication-title"}>
                    {t('verification.restore.title')}
                </h3>
                {props.source === 'tasks' && (
                    <div className={"form-field-container"}>
                        <FormField
                            label={t('verification.restore.reason')}
                            type={'textarea'}
                            name={'reason'}
                            id={'reason'}
                            error={errors['reason']}
                            register={register}
                            isRequired={true}
                        />
                    </div>
                )}
            </form>
            <div className={'button-wrapper'}>
                <ButtonText text={t('action.cancel')}
                           onClick={() => {
                               props.onCancel();
                           }}/>
                <ButtonText text={t('action.confirm')} 
                           buttonType={"callToAction"} 
                           onClick={() => {
                               formRef.current.requestSubmit();
                           }} />
            </div>
        </div>
    )
} 