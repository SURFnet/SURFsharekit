import React from 'react';
import styled from "styled-components";
import {IconTitleHeader} from "../../components/icontitleheader/IconTitleHeader";
import {faFileInvoice} from "@fortawesome/free-solid-svg-icons";
import {useTranslation} from "react-i18next";
import {SURFShape} from "../../Mixins";

function ConceptContinuation(props) {

    const {t} = useTranslation();

    return (
        <>
            <div className={"flex-row"}>
                <IconTitleHeader
                    icon={faFileInvoice}
                />
                <h2>{t("dashboard.continue")}</h2>
            </div>
            <FlexContainer >
                {props.content}
            </FlexContainer>
        </>

    )
}

const FlexContainer = styled.div `
    display: flex;
    flex-wrap: wrap;
    margin-top: 23px;
    gap: 20px;
`


export default ConceptContinuation;