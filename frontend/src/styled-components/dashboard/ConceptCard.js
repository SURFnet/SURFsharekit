import React from 'react';
import styled from "styled-components";
import {greyMedium, nunitoBold, spaceCadet, spaceCadetLight, SURFShape, SURFShapeLeft} from "../../Mixins";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import RepoItemHelper from "../../util/RepoItemHelper";
import {useTranslation} from "react-i18next";
import StatusIcon from "../../components/statusicon/StatusIcon";
import {faCopy, faTrash} from "@fortawesome/free-solid-svg-icons";

function ConceptCard(props) {

    const {t} = useTranslation();

    const getStatusText = () => {
        switch(props.subtitle.toLowerCase()){
            case 'repoitem': return "RepoItem";
            case 'publicationrecord': return t('publication.type.publication_record');
            case 'researchobject': return t('publication.type.research_object');
            case 'learningobject': return t('publication.type.learning_object');
            case 'dataset': return t('publication.type.dataset');
            case 'project': return t('publication.type.project');
            default: return "-"
        }
    }

    return (
        <CardContainer onClick={props.onEditClick}>
            <StatusLabel>
                <StatusIcon style={{width: "fit-content"}} color="gray" text={t('publication.state.draft')}/>
                <LastEditedLabel>
                    Laatste bijgewerkt op {RepoItemHelper.getLastEditedDate(props.lastEdited)}
                </LastEditedLabel>
            </StatusLabel>

            <CardHeading>
                <CardTitle>{props.title}</CardTitle>
                <CardSubtitle>{getStatusText()}</CardSubtitle>
            </CardHeading>

            <CardButtonContainer>
                <FontAwesomeIcon icon={faCopy} onClick={props.onCopyClick}/>
                <FontAwesomeIcon icon={faTrash} onClick={props.onDeleteClick}/>
            </CardButtonContainer>
        </CardContainer>
    )
}

const CardContainer = styled.div `
    ${SURFShapeLeft}
    display: flex;
    flex-direction: column;
    flex: 1 1 31%;
    max-width: 33%;
    background-color: white;
    height: 191px;
    padding: 18px 20px;
    cursor: pointer;
`;

const CardHeading = styled.div `
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-height: 110px;
`;

const StatusLabel = styled.div`
    height: 23px;
    display: flex;
    justify-content: space-between;
    align-items: center;
`;

const LastEditedLabel = styled.div`
    font-size: 10px;
`;

const CardTitle = styled.div`
    ${nunitoBold}
    font-size: 16px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3; 
    -webkit-box-orient: vertical;
`;

const CardSubtitle = styled.div`
    justify-content: flex-end;
    font-size: 12px;
`;

const CardButtonContainer = styled.div `
    display: flex;
    justify-content: flex-end;
    align-self: flex-end;
    gap: 20px;
    cursor: pointer;
`;


export default ConceptCard;