import React, {useEffect, useState} from "react";
import styled from "styled-components";
import {openSans, white} from "../../../Mixins";
import ChevronDown from "../../../resources/icons/ic-chevron-down.svg";
import i18n from "i18next";
import {useTranslation} from "react-i18next";
import moment from "moment";
import {StorageKey, useAppStorageState} from "../../../util/AppStorage";
import ModalButton, {
    MODAL_ALIGNMENT as DropdownListShape,
    MODAL_ALIGNMENT, MODAL_VERTICAL_ALIGNMENT
} from "../../../styled-components/buttons/ModalButton";
import BasicDropdownList from "../../../styled-components/dropdowns/dropdown-lists/BasicDropdownList";
import BasicDropdownItem from "../../../styled-components/dropdowns/dropdown-items/BasicDropdownItem";

function LanguageSwitch() {

    const [languageLocale, setLanguageLocale] = useAppStorageState(StorageKey.LANGUAGE_LOCALE);
    const [isExpanded, setExpanded] = useState(false);
    const {t, i18n} = useTranslation();

    useEffect(() => {
        i18n.changeLanguage(languageLocale, (error, t) => {
            if (error) {
                return console.log('something went wrong loading', error);
            }
            changeMomentLocale(languageLocale)
        });
    }, [languageLocale])

    const dropdownItems = [
        new BasicDropdownItem(t("language.dutch"), null, () =>{setLanguageLocale("nl")}),
        new BasicDropdownItem(t("language.english"), null, () =>{setLanguageLocale("en")})
    ]

    return (

        <ModalButton
            onModalVisibilityChanged={(modalIsVisible) => setExpanded(modalIsVisible)}
            modalHorizontalAlignment={MODAL_ALIGNMENT.RIGHT}
            modalVerticalAlignment={MODAL_VERTICAL_ALIGNMENT.BOTTOM}
            modal={
                <BasicDropdownList
                    listShape={DropdownListShape.RIGHT}
                    listWidth={'100px'}
                    dropdownItems={dropdownItems}
                />
            }
            button={
                <LanguageSwitchRoot>
                    <Language>{i18n.t('language.current_code')}</Language>
                    <Icon dropdownIsOpen={isExpanded} src={ChevronDown}/>
                </LanguageSwitchRoot>
            }
        />
    )

    function changeMomentLocale(languageCode) {
        //Changes moment's locale. This is used in the DatePickers to set the correct language and date notation.
        moment.locale(languageCode);
    }
}

const LanguageSwitchRoot = styled.div`
    height: 34px;
    display: flex;
    flex-direction: row;
    gap: 3px;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    user-select: none;
    padding: 0px 15px;
`;

const Icon = styled.img`
    transform: ${props => props.dropdownIsOpen ? 'rotate(180deg)' : 'none'};
    -webkit-user-drag: none;
    -khtml-user-drag: none;
    -moz-user-drag: none;
    -o-user-drag: none;
    user-drag: none;
`;

const Language = styled.div`
    ${openSans};
    color: ${white};
    font-size: 12px;
    line-height: 16px;
    text-transform: uppercase;
`;

export default LanguageSwitch;