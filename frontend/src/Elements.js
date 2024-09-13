import styled from "styled-components";
import {nunitoBlack, nunitoBold, nunitoExtraBold, openSans, spaceCadet} from "./Mixins";

export const ThemedButton = styled.button`
    font-size: 1.15em;
    padding: 12px 15px;
    display: flex;
    justify-content: space-around;
    align-items: center;
`;

export const ThemedH1 = styled.h1`
    ${nunitoExtraBold};
    font-size: 40px;
    color: ${spaceCadet};
    line-height: 56px;
`;

export const ThemedH2 = styled.h2`
    ${nunitoBold()};
    font-size: 32px;
    color: ${spaceCadet};
    line-height: 56px;
`;

export const ThemedH3 = styled.h3`
    ${nunitoExtraBold};
    font-size: 25px;
    color: ${spaceCadet};
    line-height: 34px;
`;

export const ThemedH4 = styled.h4`
    ${nunitoBold};
    font-size: 20px;
    color: ${spaceCadet};
    line-height: 27px;
`;

export const ThemedH5 = styled.h5`
    ${nunitoBlack};
    font-size: 16px;
    color: ${spaceCadet};
    line-height: 22px;
`;

export const ThemedH6 = styled.h6`
    ${nunitoBold};
    font-size: 12px;
    color: ${spaceCadet};
    line-height: 16px;
    margin: 0;
`;

export const ThemedP = styled.p`
    ${openSans};
    font-size: 12px;
    color: ${spaceCadet};
    line-height: 20px;
    text-align: left;
    margin: 0;
    padding: 0;
`;

export const ThemedA = styled.a`
    &:focus {
        outline: none;
    }

    &:link,
    &:visited,
    &:hover,
    &:active {
        color: $text-color;
        text-decoration: none;
    }
`;

// export const FA = styled.i`
//     font-family: 'Font Awesome 5 Free';
//     font-weight: 900;
// `;
//
// export const FAS = styled.i`
//     font-family: 'Font Awesome 5 Free';
//     font-weight: 900;
// `;