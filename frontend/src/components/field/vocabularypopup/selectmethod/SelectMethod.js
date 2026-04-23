import React, {useState} from "react";
import {majorelle} from "../../../../Mixins";
import {Tooltip} from "../../FormField";
import AIicon from "../../../../resources/icons/AI-icon.svg";
import OwnInputIcon from "../../../../resources/icons/own-input-icon.svg";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faInfoCircle} from "@fortawesome/free-solid-svg-icons/faInfoCircle";
import {Container, OptionContainer, RadioButton, VerticalContainer, Text, Image, WarningBox} from "./SelectMethodStyled.js";
import {useTranslation} from "react-i18next";

/**
 * SelectMethod - a component that lets you choose between generating suggestions or manually adding vocabularies
 *
 * Features:
 * - provides two options: generating or manually adding
 * - Warns the user about sharing data information
 *
 * @component
 * @param {Callback} onMethodSelect - Callback function for option selection
 * @param {Object} selectedVocabulary - Currently selected option UUIDs
 *
 */

const SelectMethod = ({ onMethodSelect, selectedVocabulary }) => {
    const [selectedMethod, setSelectedMethod] = useState(null)
    const {t} = useTranslation()

    const selectMethod = (method) => {
        onMethodSelect(method)
        setSelectedMethod(method)
    }

    return (
        <VerticalContainer>
            <Container>
                <OptionContainer
                    id={"method-ai"}
                    onClick={() => selectMethod("method-ai")}
                    disabled={selectedVocabulary === null}
                >
                    <RadioButton
                        width={'15px'}
                        height={'15px'}
                        color={majorelle}
                        disabled={selectedVocabulary === null}
                    >
                        <input
                            type="radio"
                            name="options"
                            value={0}
                            checked={selectedMethod === 'method-ai'}
                        />
                    </RadioButton>
                    <Image src={AIicon} />
                    <Text>{t("vocabulary_field.popup.ai")}</Text>
                </OptionContainer>

                <OptionContainer
                    id={"method-diy"}
                    onClick={() => selectMethod("method-diy")}
                    disabled={selectedVocabulary === null}
                >
                    <RadioButton
                        width={'15px'}
                        height={'15px'}
                        color={majorelle}
                        disabled={selectedVocabulary === null}
                    >
                        <input
                            type={"radio"}
                            name={"options"}
                            value={1}
                            checked={selectedMethod === 'method-diy'}
                        />
                    </RadioButton>
                    <Image src={OwnInputIcon} />
                    <Text>{t("vocabulary_field.popup.diy")}</Text>
                </OptionContainer>
            </Container>
            { selectedMethod === 'method-ai' &&
                <WarningBox>
                    <Tooltip width={"200px"} element={<FontAwesomeIcon icon={faInfoCircle} />} text={t("vocabulary_field.popup.ai_discretion")}/>
                    {t("vocabulary_field.popup.ai_warning")}
                </WarningBox>
            }
        </VerticalContainer>
    );
}

export default SelectMethod