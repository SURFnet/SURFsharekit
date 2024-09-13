//Javascript which mimics Sass mixins

export function maxNumberOfLines(numberOfLines, lineHeight = '1.2em') {
    return `
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: ${numberOfLines}; /* number of lines to show */
        line-height: ${lineHeight}; /* fallback */
        max-height: ${lineHeight} * ${numberOfLines};
    `;
}

export function openSans() {
    return `
        font-family: 'Open Sans', sans-serif;
        font-weight: 400;
    `
}

export function openSansBold() {
    return `
        font-family: 'Open Sans', sans-serif;
        font-weight: 700;
    `
}
export function nunitoBold() {
    return `
        font-family: 'Nunito', sans-serif;
        font-weight: 700;
    `
}

export function nunitoSemiBold() {
    return `
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
    `
}


export function nunitoBlack() {
    return `
        font-family: 'Nunito', sans-serif;
        font-weight: 900;
    `
}


export function nunitoRegular() {
    return `
        font-family: 'Nunito', sans-serif;
        font-weight: 400;
    `
}

export function nunitoExtraBold() {
    return `
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
    `
}
export function roundedBackgroundPointyUpperLeft(color = 'white', radius = '15px') {
    return `
        background: ${color};
        border-radius: 2px ${radius} ${radius};
    `;
}

export function SURFShapeRight() {
    return `
        border-radius: 15px 2px 15px 15px;
    `;
}

export function SURFShapeLeft() {
    return `
        border-radius: 2px 15px 15px 15px;
    `;
}

export const spaceCadet = "#2D364F";
export const cultured = "#F8F8F8";
export const spaceCadetLight = "#435075";
export const flame = "#E35F3C";
export const flameLight = "#F69E90";
export const majorelle = "#7344EE";
export const majorelleLight = "#906AF1";
export const maximumYellow = "#F3BA5A";
export const maximumYellowLight = "#FACD34";
export const oceanGreen = "#64C3A5";
export const oceanGreenLight = "#6FD8B6";
export const vividSky = "#5AC4ED";
export const vividSkyLight = "#80D7F1";

export const greyLighter = "#F3F3F3";
export const greyLight = "#E5E5E5";
export const greyMedium = "#D2D2D2";
export const greyDark = "#A5AAAE";
export const greyDarker = "#899194";
export const white = "#FFF"

export const textColor = spaceCadet; //used for text on background-color
export const textColorActive = majorelle;
export const textColorActiveLight = majorelleLight;
export const textColorError = flame;
export const textColorValid = oceanGreen;

export const desktopSideMenuWidth = '258px'
export const desktopTopMenuHeight = '60px'

export const mobileTabletMaxWidth = 1149;