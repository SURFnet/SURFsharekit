import React from 'react';
import styled from "styled-components";
import {ThemedH1} from "../Elements";
import StatusIcon from "../components/statusicon/StatusIcon";
import {useTranslation} from "react-i18next";

function PageHeader (props) {

    const {t} = useTranslation()

    return (
        <PageHeaderRoot>
            <TextContainer>
                <Text>{props.title}</Text>
                <StatusIcon color={props.active ? 'green' : 'red'} text={props.active ? t('person.active') : t('person.inactive')} backgroundColor={'white'}/>
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
    display: flex;
    align-items: center;
    gap: 15px;
`;

const Text = styled(ThemedH1)``;

export default PageHeader;