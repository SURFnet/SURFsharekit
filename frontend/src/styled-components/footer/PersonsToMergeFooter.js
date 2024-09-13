import React, {useEffect, useState} from 'react';
import styled, {keyframes} from "styled-components";
import {
    desktopSideMenuWidth,
    majorelle,
    nunitoBold,
    spaceCadet,
    spaceCadetLight,
    SURFShapeRight,
    white
} from "../../Mixins";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faChevronDown, faChevronUp} from "@fortawesome/free-solid-svg-icons";
import AppStorage, {StorageKey} from "../../util/AppStorage";
import {useGlobalState} from "../../util/GlobalState";

function PersonsToMergeFooter(props) {

    const [dropdownOpened, setDropdownOpened] = useState(false);
    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);

    return (
        <Footer isSideMenuCollapsed={isSideMenuCollapsed}>
            <FooterButtonContainer>
                <TotalPersonCountContainer>
                    <TotalPersonCountText onClick={props.dropdown}>
                        {props.totalPersons}
                        <ProfilesSelectedText>{props.text}</ProfilesSelectedText>
                    </TotalPersonCountText>
                    <ChevronIcon
                        icon={dropdownOpened ? faChevronDown : faChevronUp}
                        onClick={() => setDropdownOpened(!dropdownOpened)}
                    />
                </TotalPersonCountContainer>
                <FooterButton className={"flex-row"}>
                    {props.stopButton} {props.continueButton}
                </FooterButton>
            </FooterButtonContainer>
            <PersonDropdown displayDropdown={dropdownOpened}>
                {props.profileList}
            </PersonDropdown>
        </Footer>
    )
}

const ChevronIcon = styled(FontAwesomeIcon)`
    color: white;
    cursor: pointer;
`;

const PersonDropdown = styled.div `
    min-height: 71px;
    max-height: 71px; 
    display: ${props => props.displayDropdown ? 'flex' : 'none'};
    position: absolute; 
    padding: 0 15px 0 0;
    bottom: 70px;
    z-index: 99;
    left: 338px;
    border-radius: 20px 20px 0 20px;
    background-color: ${spaceCadet};
    align-items: center;
`

const Footer = styled.div `
    width: ${props => props.isSideMenuCollapsed ? "100%" : `calc(100% - ${desktopSideMenuWidth})`};
    height: 60px;
    position: fixed;
    bottom: 0;
    display: flex;
    background-color: ${spaceCadet};
    margin-left: ${props => props.isSideMenuCollapsed ? 0 : desktopSideMenuWidth };
    transition: margin width 0.2s ease;
    padding-right: 95px;
`;

const TotalPersonCountText = styled.div `
    display: flex;
    align-items: center;
    color: white;
    ${nunitoBold};
    font-size: 14px;
    line-height: 19px;
    padding-left: 105px;
    padding-right: 10px;
    float: right;
`

const DropDown = styled.div`
    display: inline-block;
    color: white;
    text-align: center;
    padding: 14px 16px;
    text-decoration: none;
`;

const FooterButtonContainer = styled.div`
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
`;

const TotalPersonCountContainer = styled.div `
    display: flex;
    align-items: center;
`;

const FooterButton = styled.div `gap: 8px`;

const ProfilesSelectedText = styled.div `margin-left:3px;`;

export default PersonsToMergeFooter;