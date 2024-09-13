import React, {useState} from 'react';
import styled from "styled-components";
import {greyDarker, majorelle, majorelleLight, openSans, SURFShapeLeft, white} from "../../Mixins";
import {ThemedH6} from "../../Elements";
import SURFButton from "../buttons/SURFButton";
import {useTranslation} from "react-i18next";

function TextInputModal(props) {

    const {
        onSendButtonClick,
        modalButtonClose  // This prop is only defined when TextInputModal is part of the ModalButton component
    } = props

    const [textAreaValue, setTextAreaValue] = useState("");
    const {t} = useTranslation();

    return (
        <Modal>
            <Title>{t("dashboard.tasks.decline_modal.title")}</Title>
            <TextArea
                value={textAreaValue}
                onChange={(e) => setTextAreaValue(e.target.value)}
            />
            <ButtonContainer>
                <SURFButton
                    onClick={() => {
                        onSendButtonClick(textAreaValue)
                        if(modalButtonClose) {
                            // Closes the TextInputModal if part of the ModalButton component
                            modalButtonClose()
                        }
                    }}
                    backgroundColor={majorelle}
                    highlightColor={majorelleLight}
                    width={"115px"}
                    text={t("dashboard.tasks.decline_modal.send_button")}
                    textSize={"14px"}
                    padding={"20px"}
                />
            </ButtonContainer>

        </Modal>
    )
}

export default TextInputModal;

const Modal = styled.div`
    display: flex;
    flex-direction: column;
    gap: 5px;
    width: 265px;
    height: 180px;
    background: ${white};
    padding: 10px;
    ${SURFShapeLeft};
    box-shadow: 0px 4px 10px rgba(45, 54, 79, 0.2);
`;

const Title = styled(ThemedH6)`
    margin: 0;
`;

const TextArea = styled.textarea`
    background: white;
    border-radius: 2px 15px 15px 15px;
    border: 1px solid ${greyDarker};
    ${openSans};
    font-size: 12px;
    line-height: 16px;
    vertical-align: center;
    height: 66px;
    width: 100%;
    margin-top: 5px;
    padding: 12px;
    outline: none;
    resize: none;
    &:focus {
        border: 1px solid ${majorelleLight};
    }
`;

const ButtonContainer = styled.div`
    width: 100%;
    display: flex;
    flex-direction: row;
    flex: 1;
    align-items: flex-end;
`;