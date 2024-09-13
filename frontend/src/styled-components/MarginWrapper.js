import React from 'react';
import styled from "styled-components";

function MarginWrapper (props) {

    return (
        <MarginWrapperRoot{...props}/>
    )
}

const MarginWrapperRoot = styled.div`
    width: 100%;
    margin-top: ${props => props.top ?? '0px'};
    margin-bottom: ${props => props.bottom ?? '0px'};
    margin-left: ${props => props.left ?? '0px'};
    margin-right: ${props => props.right ?? '0px'};
`;

export default MarginWrapper;