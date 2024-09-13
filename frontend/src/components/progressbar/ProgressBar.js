import React from "react";
import styled from "styled-components";
import {openSansBold} from "../../Mixins";

function ProgressBar(props) {
    return <SurfProgressBar>
        <ProgressBarContainer height={`${props.height ?? '6px'}`}>
            <ProgressBarBar width={`${props.progress}%`}/>
        </ProgressBarContainer>
        <ProgressPercentageText>{(Math.round(props.progress * 100) / 100)}%</ProgressPercentageText>
    </SurfProgressBar>
}

const SurfProgressBar = styled.div`
    display: flex;
    align-items: center;
    flex-shrink: 0;
    min-width: 75px;
`

const ProgressBarContainer = styled.div`
    flex: 1 1 auto;
    background-color: #E5E5E5;
    border-radius: 9px;
    height: ${props => props.height};
`

const ProgressPercentageText = styled.div`
    ${openSansBold()};
    min-width: 30px;
    margin-left: 7px;
    font-size: 10px;
`

const ProgressBarBar = styled.div`
    transition: width 0.5s ease;
    width: ${props => props.width};
    height: 100%;
    background-color: #64C3A5;
    border-radius: 9px;
`

export default ProgressBar