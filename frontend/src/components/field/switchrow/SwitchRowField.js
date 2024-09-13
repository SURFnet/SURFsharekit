import React from "react";
import {InputField} from "../FormField";
import './SwitchRowField.scss'
import styled from "styled-components";
import {ThemedH5, ThemedH6} from "../../../Elements";
import {greyLight, openSans} from "../../../Mixins";

export function SwitchRowField(props) {
    return (
        <SwitchRowFieldRoot isHidden={props.isHidden}>
            <InputField readonly={props.readonly}
                        defaultValue={props.defaultValue}
                        type={"switch"}
                        name={props.name}
                        onValueChanged={props.onValueChanged}
                        register={props.register}
                        setValue={props.setValue}
            />
            <TextContainer>
                <Label>{props.description ? `${props.label} - `: props.label}</Label>
                <Description>{props.description}</Description>
            </TextContainer>
        </SwitchRowFieldRoot>
    )
}

const SwitchRowFieldRoot= styled.div`
    flex-grow: 1;
    display: flex;
    flex-direction: row;
    align-items: center;
    font-size: 12px;
    padding 20px 0;
    margin: 0 0 5px 10px;
    border-bottom: 1px solid ${greyLight};
    display: ${props => props.isHidden ? "none" : "flex"};
`;

const TextContainer = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
`;

const Label = styled(ThemedH6)`
     white-space: pre;
`;

const Description = styled.div`
    ${openSans};
    font-size: 12px;
`;