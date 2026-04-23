import styled from "styled-components";
import {nunitoBold, openSansBold, textColor} from "../../Mixins";

export const Step2SelectionContainer = styled.div`
    display: flex;
    flex-direction: column;
    gap: 20px;
`
export const UseTemplateContainer = styled.div`
    display: flex;
    flex-direction: column;
    gap: 5px;
`

export const UseTemplateLabel = styled.label`
    ${openSansBold};
    color: ${textColor};
    font-size: 10px !important;
`

