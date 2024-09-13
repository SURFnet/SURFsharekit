import React, {useEffect} from 'react';
import styled from "styled-components";
import {
    greyDark, greyDarker,
    greyLight, majorelle, nunitoBold, nunitoRegular, nunitoSemiBold, oceanGreen, openSans
} from "../../Mixins";
import checkmark from "../../resources/icons/checkmark.svg";

function PublicationFormStep(props) {

    useEffect(() => {
        if (props.index) {
            console.log(props.index)
        }
    }, [props.index])

    function getCorrectStep(){
        if (props.isActive) {
            return <StepCircle active={props.active}>
                {props.number}
            </StepCircle>
        } else {
            if (props.isValidated) {
                return  <StepValidated>
                    <img src={checkmark} alt="Checkmark"/>
                </StepValidated>
            } else {
                return <StepCircle active={props.active}>
                    {props.number}
                </StepCircle>
            }
        }
    }

    return (
        <>
            <Container onClick={props.handleStepClick}>
                {getCorrectStep()}
                <StepTextColumn>
                    <StepTextHeader>
                        {props.subheader}
                    </StepTextHeader>
                    <StepTextSubheader>
                        {props.header}
                    </StepTextSubheader>
                </StepTextColumn>
            </Container>
            {props.index < 4 && <hr />}
        </>
    )
}

const Container = styled.div`
    width: auto;
    height: auto;
    display: flex;
    flex-direction: row;
    gap: 10px;
    margin: 0 20px;
    cursor: pointer;
`;

const StepCircle = styled.div`
    width: 40px;
    height: 40px;
    min-width: 40px;
    min-height: 40px;
    background-color: ${props => props.active ? majorelle : greyLight};
    border-radius: 50%;
    color: white;
    text-align: center;
    line-height: 40px;
`;

const StepValidated = styled.div`
    width: 40px;
    height: 40px;
    background-color: ${oceanGreen};
    border-radius: 50%;
    color: white;
    text-align: center;
    line-height: 40px;
`;

const StepTextColumn = styled.div`
    display: flex;
    flex-direction: column;
    justify-content: center;
`;

const StepTextHeader = styled.div`
    color: ${greyDarker};
    font-size: 10px;
    font-family: ${openSans()};
    font-weight: bold;
`;

const StepTextSubheader = styled.div`
    color: black;
    font-size: 12px;
    font-family: ${nunitoSemiBold()}
`;

export default PublicationFormStep;