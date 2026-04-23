import React from "react";
import {useTranslation} from "react-i18next";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import * as SwalPopupStyled from "../../styled-components/popup/SwalPopupStyling";
import {NextButton} from "../../styled-components/buttons/NavigationButtons";

function LmsFlaggedPopupContent(props) {
    const {t} = useTranslation();

    return (
        <SwalPopupStyled.ContentRoot>
            <SwalPopupStyled.Content>
                <SwalPopupStyled.CloseButtonContainer onClick={props.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </SwalPopupStyled.CloseButtonContainer>

                <SwalPopupStyled.Header>
                    <SwalPopupStyled.Title>{t('add_publication.popup.lms_flagged.title')}</SwalPopupStyled.Title>
                    <SwalPopupStyled.Paragraph>{t('add_publication.popup.lms_flagged.subtitle')}</SwalPopupStyled.Paragraph>
                </SwalPopupStyled.Header>
            </SwalPopupStyled.Content>

            <div className={"flex-row justify-between"}>
                <div></div>
                <NextButton
                    text={t('action.ok')}
                    onClick={props.onConfirm}
                />
            </div>
        </SwalPopupStyled.ContentRoot>
    );
}

export default LmsFlaggedPopupContent;
