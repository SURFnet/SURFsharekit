import React from 'react';
import SURFButton from './SURFButton';
import {black, greyLighter, majorelle, majorelleLight, spaceCadet, spaceCadetLight, white} from '../../Mixins';

// Hover effect only components
export const WhiteHoverButton = (props) => {
    return (
        <SURFButton
            {...props}
            backgroundColor={white}
            highlightColor={greyLighter}
        />
    );
};

export const MajorelleHoverButton = (props) => {
    return (
        <SURFButton
            {...props}
            backgroundColor={majorelle}
            highlightColor={majorelleLight}
        />
    );
};

export const SpaceCadetHoverButton = (props) => {
    return (
        <SURFButton
            {...props}
            backgroundColor={spaceCadet}
            highlightColor={spaceCadetLight}
        />
    );
};

// Original components kept for backward compatibility
export const PreviousButton = ({ 
    text, 
    onClick,
    width = '90px',
    buttonText,
    ...props 
}) => {
    return (
        <SURFButton
            text={buttonText ?? text}
            backgroundColor={white}
            highlightColor={greyLighter}
            borderColor={black}
            border={'2px solid black'}
            textColor={black}
            width={width}
            onClick={onClick}
            {...props}
        />
    );
};

export const NextButton = ({ 
    text, 
    onClick,
    width = '190px',
    buttonText,
    disabled,
    ...props 
}) => {
    return (
        <SURFButton
            disabled={disabled}
            text={buttonText ?? text}
            backgroundColor={majorelle}
            highlightColor={majorelleLight}
            width={width}
            padding={"0 10px"}
            onClick={onClick}
            {...props}
        />
    );
};

export const BlackButton = ({
    text,
    onClick,
    width = '190px',
    buttonText,
    disabled,
    ...props
}) => {
    return (
        <SURFButton
            disabled={disabled}
            text={buttonText ?? text}
            backgroundColor={spaceCadet}
            highlightColor={spaceCadetLight}
            width={width}
            padding={"0 10px"}
            onClick={onClick}
            {...props}
        />
    );
};