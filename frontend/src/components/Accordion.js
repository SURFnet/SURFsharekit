import React, {useEffect, useState} from 'react';
import styled from "styled-components";
import {cultured, majorelle, openSans, roundedBackgroundPointyUpperLeft} from "../Mixins";
import {ThemedH5} from "../Elements";
import IconButtonText from "./buttons/iconbuttontext/IconButtonText";
import {faChevronDown, faChevronUp} from "@fortawesome/free-solid-svg-icons";
import {useTranslation} from "react-i18next";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import debounce from "debounce-promise";

export function Accordion(props) {

    const [isExtended, setIsExtended] = useState(props.isExtended ? props.isExtended : false);
    const [overflow, setOverflow] = useState("hidden");
    const {t} = useTranslation();
    let timeout = null;

    const {
        isVisible = true,
        title,
        titleComponent,
        subtitle,
        faIcon,
        children,
        onChange
    } = props;

    useEffect(() => {
        let isExtended = props.isExtended ?? false
        setIsExtended(isExtended)
    }, [props.isExtended]);

    useEffect(() => {
        if (isExtended) {
            changeOverflowValue(isExtended)
        } else {
            setOverflow("hidden")
            changeOverflowValue(isExtended)
        }
    }, [isExtended])

    function handleStateChange() {
        let newExtendedValue = !isExtended
        setIsExtended(newExtendedValue)

        if (onChange) {
            onChange()
        }
    }

    const changeOverflowValue = debounce((isExtended) => {
        if (isExtended) {
            setOverflow("visible")
        } else {
            setOverflow("hidden")
        }
    }, 200)

    function getTitle() {
        if (titleComponent) {
            return titleComponent
        } else {
            return <Title>{title}</Title>
        }
    }

    return (
        <AccordionRoot isHidden={props.isHidden} $isExtended={isExtended} $isVisible={isVisible}>
            <Header onClick={handleStateChange}>
                <HeaderLeftContentContainer>
                    {faIcon && (
                        <HeaderIconContainer>
                            <HeaderIcon icon={faIcon}/>
                        </HeaderIconContainer>
                    )}
                    {getTitle()}
                    <Subtitle>{subtitle}</Subtitle>
                </HeaderLeftContentContainer>
                <HeaderRightContentContainer>
                    <IconButtonText
                        faIcon={isExtended ? faChevronUp : faChevronDown}
                        buttonText={isExtended ? t("action.close") : t("action.open")}
                        onClick={handleStateChange}
                    />
                </HeaderRightContentContainer>
            </Header>
            <ContentContainer $isExtended={isExtended} $overflow={overflow}>
                <Content $overflow={overflow}>{children}</Content>
            </ContentContainer>
        </AccordionRoot>
    )
}

const AccordionRoot = styled.div`
    background: white;
    transition: padding 200ms;
    border-radius: 2px 15px 15px 15px;
    height: ${props => props.isHidden ? "0" : "auto"};
    visibility: ${props => props.isHidden ? "collapse" : "visible"};
    display: ${props => (props.$isVisible || props.isHidden) ? "block" : "none"};
`;

const Title =  styled(ThemedH5)``;
const Subtitle =  styled.div`
    ${openSans()}
    font-size: 12px;
    line-height: 16px;
    color: #2D364F;
`;
const Header =  styled.div`
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    padding: 20px 35px 20px 35px;
`;

const ContentContainer = styled.div`
    display: grid;
    grid-template-rows: ${props => props.$isExtended ? "1fr" : "0fr"};
    transition: grid-template-rows 200ms;
    overflow-y: ${props => props.$overflow};
    position: relative;
    padding: ${props => props.$isExtended ? "0 35px 50px 35px" : "0 35px 0 35px"};
`;

const Content = styled.div`
    overflow-y: ${props => props.$overflow};
`;

const HeaderLeftContentContainer =  styled.div`
    display: flex;
    flex-direction: row;
    column-gap: 10px;
    align-items: center;
`;

const HeaderRightContentContainer =  styled.div``;

const HeaderIconContainer = styled.div`
    background-color: ${cultured};
    width: 33px;
    height: 33px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    color: ${majorelle};
`;

const HeaderIcon = styled(FontAwesomeIcon)`
    font-size: 14px;
`;