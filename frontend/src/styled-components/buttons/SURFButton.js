import React from 'react';
import styled from "styled-components";
import {nunitoBold, spaceCadet, spaceCadetLight, SURFShapeRight, white} from "../../Mixins";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";

function SURFButton(props) {

    const {
        onClick,
        backgroundColor,
        disabled,
        border,
        highlightColor,
        width,
        minWidth,
        height,
        text,
        textSize,
        textColor,
        iconStart,
        iconStartSize,
        iconStartColor,
        iconEnd,
        iconEndSize,
        iconEndColor,
        padding,
        margin,
        shape,
        contentSpacing,
        setSpaceBetween,
        dropdownIsOpen
    } = props


    return (
        <SURFButtonRoot
            onClick={disabled ? undefined : onClick}
            backgroundColor={backgroundColor}
            disabled={disabled}
            border={border}
            highlightColor={highlightColor}
            minWidth={minWidth}
            width={width}
            height={height}
            padding={padding}
            margin={margin}
            shape={shape}
        >
            <DisabledOverlay shape={shape} isActive={disabled}/>
            <Content contentSpacing={getContentSpacing()}>
                {iconStart &&
                <Icon
                    icon={iconStart}
                    fontSize={iconStartSize}
                    color={iconStartColor}
                    onClick={(e) => {if (props.onIconStartClick) {props.onIconStartClick(e)}}}
                />
                }

                <Text
                    textSize={textSize}
                    textColor={textColor}
                >
                    {text}
                </Text>

                {iconEnd &&
                    <Icon
                        icon={iconEnd}
                        fontSize={iconEndSize}
                        color={iconEndColor}
                        rotation={dropdownIsOpen ? '180' : '0'}
                    />
                }
            </Content>
        </SURFButtonRoot>
    )

    function getContentSpacing() {
        if (contentSpacing) {
            return contentSpacing;
        }

        if((iconStart && iconEnd) || setSpaceBetween) {
            return "space-between"
        } else {
            return "center"
        }
    }
}


const SURFButtonRoot = styled.div`
    ${props => props.shape ?? SURFShapeRight};
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background: ${props => props.backgroundColor ?? spaceCadet};
    min-width: ${props => props.minWidth ?? undefined};
    width: ${props => props.width ?? undefined};
    padding: ${props => props.padding ?? '0px 70px 0px 70px'}; 
    border: ${props => props.border ?? undefined};
    height: ${props => props.height ?? "40px"};
    cursor: ${props => props.disabled ? "auto" : "pointer"};
    margin: ${props => props.margin ?? undefined};
    user-select: none;
    &:hover {
        background: ${props => (!props.disabled && props.highlightColor) ?? undefined};
    }
`;

const DisabledOverlay = styled.div`
    ${props => props.shape ?? SURFShapeRight};
    position: absolute;
    display: ${props => props.isActive ? "block" : "none"};
    background: ${white};
    width: 100%;
    height: 100%;
    opacity: 0.8;
    z-index: 10;
`;

const Content = styled.div`
    width: 100%;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: ${props => props.contentSpacing};
    gap: 12px;
`;

const Icon= styled(FontAwesomeIcon)`
    font-size: ${props => props.fontSize + " !important" ?? "initial"};
    color: ${props => props.color ?? "initial"};
    transform: rotate(${props => (props.rotation)});
`;

const Text = styled.div`
    color: ${props => props.textColor ?? 'white'};
    ${nunitoBold};
    font-size: ${props => props.textSize ?? '14px'};
    line-height: 19px;
`;

export default SURFButton;