import styled from 'styled-components';
import { PrefixRadioButtons } from "../../../createpersonform/CreatePersonForm";
import { majorelle, greyLight, textColor } from "../../../../Mixins";

export const Container = styled.form`
    width: 100%;
    height: 100px;
    display: flex;
    gap: 10px;
`

export const VerticalContainer = styled(Container)`
    flex-direction: column;
`

export const Image = styled.img`
    height: 50px;
    color: #906AF1
`

export const OptionContainer = styled.div`
    width: 50%;
    height: 100%;
    position: relative;
    border: ${props => props.disabled ? `${greyLight}` : `${majorelle}`} 2px solid;
    border-radius: 20px;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: ${props => props.disabled ? 'none' : 'auto'};
    opacity: ${props => props.disabled ? 0.5 : 1};
    cursor: ${props => props.disabled ? 'not-allowed' : 'pointer'};

    ${props => !props.disabled && `
        &:hover {
            box-shadow: 0 0 0 4px ${props.color}20;
            transform: scale(1.02);
            transition: all 0.1s ease;
        }
    `}`

export const RadioButton = styled(PrefixRadioButtons)`
    position: absolute;
    left: 5px;
    top: 5px;
    pointer-events: ${props => props.disabled ? 'none' : 'auto'};
    opacity: ${props => props.disabled ? 0.5 : 1};
    cursor: ${props => props.disabled ? 'not-allowed' : 'pointer'};

    input[type='radio'] {
        width: ${props => props.width} !important;
        height: ${props => props.width} !important;
        accent-color: ${props => props.color} !important;
        cursor: ${props => props.disabled ? 'not-allowed' : 'pointer'}
    }
`
export const Text = styled.p`
    font-size: 12px;
    line-height: 1.2rem;
    margin: 0 auto;
    font-weight: 600;
    color: ${majorelle}
`

const WarningMessage = styled.div`
    width: 100%;
    background: rgba(144,106,241, 0.25);
    border: 1px solid rgb(144,106,241);
    border-radius: 5px;
    display: flex;
    align-items: center;
    font-size: 12px;
    padding: 16px 20px;
    color: rgb(144,106,241);
`
export const WarningBox = styled(WarningMessage)`
    color: ${textColor};
    accent-color: #906AF1;
    display: flex;
    justify-items: center;
    gap: 10px;
`