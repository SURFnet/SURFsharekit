import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import React from "react";
import "./verificationpopupcontent.scss"
import ButtonText from "../components/buttons/buttontext/ButtonText";
import {useTranslation} from "react-i18next";

export function VerificationPopupContent(props) {
    const {t} = useTranslation();

    return (
        <div className={"verification-popup-content-wrapper"}>
            <div className={"verification-popup-content"}>
                <div className={"close-button-container"}
                     onClick={props.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                <div className={"verification-title"}>
                    {props.title}
                </div>
                <div className={"verification-subtitle"}>
                    {props.subtitle}
                </div>
            </div>
                {!props.displayConfirmButtonOnly &&
                    <div className={'button-wrapper'}>
                        <ButtonText text={t('action.cancel')}
                        onClick={() => {
                        props.onCancel();
                        }}/>
                        <ButtonText text={t('action.confirm')}
                        buttonType={"callToAction"}
                        onClick={() => {
                        props.onConfirm();
                        }}/>
                    </div>
                }
                {props.displayConfirmButtonOnly &&
                    <div className={'button-wrapper center-button'}>
                        <ButtonText text={t('action.ok')}
                                    buttonType={"callToAction"}
                                    onClick={() => {
                                        props.onConfirm();
                                    }}/>
                    </div>
                }
        </div>
    )
}