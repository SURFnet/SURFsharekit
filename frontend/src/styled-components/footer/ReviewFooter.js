import React from 'react';
import styled from "styled-components";
import {
    cultured,
    desktopSideMenuWidth,
    flame,
    flameLight,
    oceanGreen,
    oceanGreenLight,
    openSans,
    spaceCadet,
    spaceCadetLight,
    white
} from "../../Mixins";
import ReviewIcon from '../../resources/icons/ic-review.svg'
import {useGlobalState} from "../../util/GlobalState";
import {useTranslation} from "react-i18next";
import {ThemedH3} from "../../Elements";
import SURFButton from "../buttons/SURFButton";
import ModalButton, {MODAL_ALIGNMENT, MODAL_VERTICAL_ALIGNMENT} from "../buttons/ModalButton";
import {faChevronDown} from "@fortawesome/free-solid-svg-icons";
import TextInputModal from "../modals/TextInputModal";

function ReviewFooter(props) {

    const {
        onStop,
        onApprove,
        onDecline
    } = props

    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);
    const {t} = useTranslation()

    return (
        <Footer isSideMenuCollapsed={isSideMenuCollapsed}>
            <LeftContainer>
                <IconContainer>
                    <img src={ReviewIcon} alt=""/>
                </IconContainer>
                <Title>{t('review_footer.title')}</Title>
                <Subtitle>{t('review_footer.subtitle')}</Subtitle>

            </LeftContainer>
            <RightContainer>
                <SURFButton
                    border={`2px solid ${white}`}
                    backgroundColor={spaceCadet}
                    highlightColor={spaceCadetLight}
                    text={t("action.stop")}
                    textSize={"14px"}
                    textColor={white}
                    padding={"0 30px"}
                    onClick={() => {
                        onStop()
                    }}
                />
                <SURFButton
                    backgroundColor={oceanGreen}
                    highlightColor={oceanGreenLight}
                    text={t("dashboard.tasks.actions.approve")}
                    textSize={"14px"}
                    textColor={white}
                    padding={"0 30px"}
                    onClick={() => {
                        onApprove()
                    }}
                />

                <ModalButton
                    modalHorizontalAlignment={MODAL_ALIGNMENT.RIGHT}
                    modalVerticalAlignment={MODAL_VERTICAL_ALIGNMENT.TOP}
                    modalButtonSpacing={20}
                    modal={<TextInputModal onSendButtonClick={(declineReason) => {onDecline(declineReason)}}/>}
                    button={
                        <SURFButton
                            backgroundColor={flame}
                            highlightColor={flameLight}
                            text={t("dashboard.tasks.actions.decline")}
                            textSize={"14px"}
                            textColor={white}
                            width={"130px"}
                            iconEnd={faChevronDown}
                            iconEndColor={white}
                            iconEndSize={"8px"}
                        />
                    }
                />
            </RightContainer>
        </Footer>
    )
}

const LeftContainer = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 20px;
`;

const RightContainer = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
`;

const IconContainer = styled.div`
    width: 33px;
    height: 33px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: ${cultured};
    border-radius: 4px;
    flex: 0 0 auto;
`;

const Title = styled(ThemedH3)`
    color: ${white};
`;

const Subtitle = styled.p`
    ${openSans};
    font-size: 12px;
    color: ${white};
    margin: 0;
`;

const Footer = styled.div `
    width: ${props => props.isSideMenuCollapsed ? "100%" : `calc(100% - ${desktopSideMenuWidth})`};
    margin-left: ${props => props.isSideMenuCollapsed ? 0 : desktopSideMenuWidth };
    height: 60px;
    position: fixed;
    bottom: 0;
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    background-color: ${spaceCadet};
    transition: width margin 0.2s ease;
    padding: 0px 190px;
    border-radius: 15px 15px 0 0;
    
    @media only screen and (max-width: 1500px) {
        padding: 0px 95px;
    }
`;

export default ReviewFooter;