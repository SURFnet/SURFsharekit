import React from "react";
import {useTranslation} from "react-i18next";

export function ReactTypeTableCell(props) {

    const {t} = useTranslation();
    const rowItem = props.props;

    function getStatusText() {
        switch(rowItem.repoType.toLowerCase()){
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
        getStatusText()
    )
}