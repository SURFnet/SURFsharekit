import styled from 'styled-components';
import {majorelle, textColor} from "../../../../Mixins";

export const SuggestionBoxContainer = styled.div`
    display: flex;
    flex-direction: column;
    padding: 10px 10px 10px 0;
    gap: 10px;
    
    & div {
        align-self: start;  
        display: flex;
        justify-items: center;
        align-items: center;
    }
    
    & input[type='checkbox'] {
        width: 20px;
        height: 20px;
        min-width: 20px;
        min-height: 20px;
        margin-right: 5px;
        margin-left: 0px;
        cursor: pointer;    
        accent-color: ${majorelle};
    }
    
    & label {
        font-size: 14px;
        color: ${textColor};
    }
`