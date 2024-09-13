import React, {useState} from 'react';
import {ThemedH1, ThemedH4} from "../Elements";
import styled from "styled-components";
import SURFButton from "../styled-components/buttons/SURFButton";
import {desktopSideMenuWidth, majorelle, majorelleLight, oceanGreen, spaceCadet, white} from "../Mixins";
import AppStorage, {StorageKey} from "../util/AppStorage";
import {useHistory} from "react-router-dom";
import {useTranslation} from "react-i18next";
import {useGlobalState} from "../util/GlobalState";
import ModalButton, {
    MODAL_ALIGNMENT,
    MODAL_ALIGNMENT as DropdownListShape,
    MODAL_VERTICAL_ALIGNMENT
} from "../styled-components/buttons/ModalButton";
import BasicDropdownList from "../styled-components/dropdowns/dropdown-lists/BasicDropdownList";
import DropdownItemWithIcon from "../styled-components/dropdowns/dropdown-items/DropdownItemWithIcon";
import SaveIcon from "../resources/icons/ic-save.svg";
import StopIcon from "../resources/icons/ic-stop.svg";
import TrashIcon from "../resources/icons/ic-trash.svg";
import EyeIcon from "../resources/icons/ic-eye.svg";
import InfoIcon from "../resources/icons/ic-info.svg";
import SignOutIcon from "../resources/icons/ic-sign-out.svg";
import TaskHelper, {TASK_ACTION_BUTTON_TYPE} from "../util/TaskHelper";
import {faCheck, faChevronDown, faEye, faTrash} from "@fortawesome/free-solid-svg-icons";
import {SwitchField} from "../components/field/switch/Switch";

function PublishPublicationViewHeader({showOnlyRequiredFields, setShowOnlyRequiredFields, ...props}) {

    const {t} = useTranslation();
    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);
    const [isExpanded, setExpanded] = useState(false);

    const {
        subtitle,
        title
    } = props;

    const dropdownItems = [
        new DropdownItemWithIcon(SaveIcon, t("publication_flow.header.save"), props.onSave),
        new DropdownItemWithIcon(StopIcon, t("publication_flow.header.stop"), props.onStop),
        new DropdownItemWithIcon(TrashIcon, t("publication_flow.header.delete"), props.onDelete),
        new DropdownItemWithIcon(EyeIcon, t("publication_flow.header.check_details"), props.onCheckDetails)
    ]

    return (
        <EditPublicationHeaderRoot isSideMenuCollapsed={isSideMenuCollapsed}>
            <LeftContent>
                <StepIndicator>{title}</StepIndicator>
                <StepTitle>{subtitle}</StepTitle>
            </LeftContent>

            <RightContent>
                <SwitchField
                    placeholder={t("switch_field.only_required_fields")}
                    defaultValue={showOnlyRequiredFields}
                    onChange={() => setShowOnlyRequiredFields(prevState => (prevState === 0 ? 1 : 0))}
                    customCss={"show_required_fields"}
                    extraSwitchCss={"show_required_fields_switch"}
                />
                <ModalButton
                    onModalVisibilityChanged={(isModalVisible) => setExpanded(isModalVisible)}
                    modalHorizontalAlignment={MODAL_ALIGNMENT.LEFT}
                    modalVerticalAlignment={MODAL_VERTICAL_ALIGNMENT.BOTTOM}
                    modal={
                        <BasicDropdownList
                            dropdownItems={dropdownItems}
                        />
                    }
                    button={
                        <SURFButton
                            width={"135px"}
                            backgroundColor={spaceCadet}
                            text={t("publication_flow.options")}
                            textSize={"12px"}
                            textColor={white}
                            iconEnd={faChevronDown}
                            iconEndColor={white}
                            iconEndSize={"14px"}
                            padding={"0 20px"}
                            setSpaceBetween={true}
                            dropdownIsOpen={isExpanded}
                        />
                    }
                />
            </RightContent>
        </EditPublicationHeaderRoot>
    )
}

const EditPublicationHeaderRoot = styled.div`
    background: linear-gradient(270deg, #F8F8F8 0%, #F0F0F0 82.57%);
    max-width: 1760px;
    width: ${props => props.isSideMenuCollapsed ? "100%" : `calc(100% - ${desktopSideMenuWidth})`};
    height: 130px;
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    padding: 40px 160px 10px 160px;
    margin: ${props => props.isSideMenuCollapsed ? '0px auto' : `0px 0px 0px ${desktopSideMenuWidth}`};
    transition: all 0.2s ease;
    transition-property: width padding margin;
`;

const LeftContent = styled.div`
    display: flex;
    flex-direction: column;
`;

const RightContent = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 30px;
`;

const StepIndicator = styled(ThemedH4)``;

const StepTitle = styled(ThemedH1)``;

export default PublishPublicationViewHeader;