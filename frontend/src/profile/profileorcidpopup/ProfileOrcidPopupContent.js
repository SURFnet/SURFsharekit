import React from "react";
import {HelperFunctions} from "../../util/HelperFunctions";
import {NextButton} from "../../styled-components/buttons/NavigationButtons";
import * as SwalPopupStyled from '../../styled-components/popup/SwalPopupStyling'
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import SwalClaimRequestPopup from "sweetalert2";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import {useTranslation} from "react-i18next";
import {OrcidBanner} from "../../styled-components/orcidbanner/OrcidBanner";

function ProfileOrcidPopupContent(props) {

    const { t } = useTranslation()

    return (
        <SwalPopupStyled.ContentRoot>
            <SwalPopupStyled.Content>
                <SwalPopupStyled.CloseButtonContainer onClick={() => SwalClaimRequestPopup.close()}>
                    <FontAwesomeIcon icon={faTimes}/>
                </SwalPopupStyled.CloseButtonContainer>

                <SwalPopupStyled.Header>
                    <SwalPopupStyled.Title>{t("profile.orcid_popup.title")}</SwalPopupStyled.Title>
                    <SwalPopupStyled.Paragraph>{t("profile.orcid_popup.subtitle")}</SwalPopupStyled.Paragraph>
                </SwalPopupStyled.Header>

                <OrcidBanner orcid={props.orcid} />
            </SwalPopupStyled.Content>

            <div className={"flex-row justify-between"}>
               <div></div>
                <NextButton
                    text={t("action.confirm")}
                    onClick={() => {
                        props.onCancel();
                    }}
                />
            </div>
        </SwalPopupStyled.ContentRoot>
    )
}

export default ProfileOrcidPopupContent;