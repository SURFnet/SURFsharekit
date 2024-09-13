import React from 'react';
import styled from "styled-components";
import {ThemedH1} from "../Elements";

function PageHeader (props) {

    return (
        <PageHeaderRoot>
            <TextContainer>
                <Text>{props.title}</Text>
            </TextContainer>

            {props.button}

        </PageHeaderRoot>
    )
}

const PageHeaderRoot = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    width: 100%;
`;

const TextContainer = styled.div`
    
`;

const Text = styled(ThemedH1)``;

export default PageHeader;