import React, {useEffect, useState} from "react";
import './publicationtable.scss'
import {StorageKey, useAppStorageState} from "../../util/AppStorage";
import StatusIcon from "../../components/statusicon/StatusIcon";
import {useTranslation} from 'react-i18next';
import LoadingIndicator from "../../components/loadingindicator/LoadingIndicator";
import RepoItemHelper from "../../util/RepoItemHelper";
import Api from "../../util/api/Api";
import {faArrowRight, faPlus} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../buttons/iconbuttontext/IconButtonText";
import Toaster from "../../util/toaster/Toaster";
import {createAndNavigateToRepoItem} from "../../publications/Publications";
import {useHistory} from "react-router-dom";

function PublicationTable(props) {
    const [user] = useAppStorageState(StorageKey.USER);
    const [userPublications, setUserPublications] = useState(null);
    const history = useHistory()
    const {t} = useTranslation();

    useEffect(() => {
        getUserPublications();
    }, []);

    const headerElement = <div className="header">
        <div className="cell title-cell">
            <input type="checkbox" className="checkbox gone"/>
            <i className="fas fa-file-invoice document-icon hidden"/>
            <div className="title"
                 style={{color: props.headerTextColor ?? 'white'}}>{t('dashboard.my_publications.header.title')}</div>
        </div>
        <div className="cell status-cell"
             style={{color: props.headerTextColor ?? 'white'}}>
            {t('dashboard.my_publications.header.status')}
        </div>
        <div className="cell date-cell">
            <div className="date"
                 style={{color: props.headerTextColor ?? 'white'}}>{t('dashboard.my_publications.header.date')}</div>
        </div>
        <div className="cell author-cell">
            {/*<div style={{color: props.headerTextColor ?? 'white'}}>{t('dashboard.my_publications.header.author')}</div>*/}
        </div>
        <div className="cell action-cell">
        </div>
    </div>;

    if (!user) {
        return null;
    } else if (!userPublications) {
        return <div className="user-publications">
            {headerElement}
            <div className="publication-list">
                <LoadingIndicator/>
                <div className={"loading-subtitle"}>{t('dashboard.my_publications.loading')}</div>
            </div>
        </div>
    }

    function PublicationSummaryRow(props) {
        let statusIcon = <StatusIcon color="green" text={t('publication.state.draft')}/>;
        if (props.repoItem.status === 'Published') {
            statusIcon = <StatusIcon color="purple" text={t('publication.state.published')}/>;
        }

        return <div className={'publication-summary'}>
            <div className="cell title-cell">
                <input type="checkbox" className="checkbox gone"/>
                <i className="fas fa-file-invoice document-icon"/>
                <div className="title">{RepoItemHelper.getTitle(props.repoItem)}</div>
            </div>
            <div className="cell status-cell">
                {statusIcon}
            </div>
            <div className="cell date-cell">
                <div className="date">{RepoItemHelper.getLastEditedDate(props.repoItem.lastEdited)}</div>
            </div>
            <div className="cell author-cell">
                {/*    <ProfileBanner id={1}*/}
                {/*                   imageUrl={"https://media.istockphoto.com/photos/young-boy-working-on-a-laptop-computer-stock-image-picture-id1077332896"}*/}
                {/*                   name={RepoItemHelper.getAuthor(props.repoItem)}/>*/}
            </div>
            <div className="cell action-cell">
                <div className="flex-grow"/>
                {/*<i className="fas fa-copy copy-icon"/>*/}
                <i className="fas fa-trash delete-icon"/>
                <IconButtonText className={"edit-button"}
                                faIcon={faArrowRight}
                                onClick={() => {
                                    props.onClickEditPublication(props)
                                }}/>
            </div>
        </div>
    }

    function TableRowDivider(props) {
        return <div className="table-row-divider"/>
    }

    function getUserPublications() {
        const config = {
            params: {
                'filter[repoType][NEQ]': 'RepoItemRepoItemFile',
                'fields[repoItems]': 'title,lastEdited,status,isArchived'
            }
        };

        Api.jsonApiGet('repoItems', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            setUserPublications(response.data);
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
        }
    }

    function getPublicationRows(props) {
        if (!userPublications || userPublications.length === 0) {
            return <EmptyState title={t('dashboard.my_publications.empty.title')}
                               subtitle={t('dashboard.my_publications.empty.subtitle')}
                               buttonTitle={t('dashboard.my_publications.empty.add')}
                               buttonIcon={faPlus}
                               onClick={() => {
                                   createAndNavigateToRepoItem(props)
                               }
                               }/>
        }

        return userPublications.map((repoItem, i) => {
            return <div key={i}>
                <PublicationSummaryRow repoItem={repoItem}
                                       onClickEditPublication={props.onClickEditPublication}/>
                {(i < userPublications.length - 1) && <TableRowDivider/>}
            </div>
        })
    }

    return (
        <div className="user-publications">
            {headerElement}
            <div className="publication-list">
                {getPublicationRows(props)}
            </div>
        </div>
    );
}

function EmptyState(props) {
    return <div className='empty-list'>
        <div className={'empty-list-title'}>
            {props.title}
        </div>
        <div className={'empty-list-subtitle'}>
            {props.subtitle}
        </div>
        <IconButtonText faIcon={props.buttonIcon}
                        buttonText={props.buttonTitle}
                        onClick={props.onClick}/>
    </div>
}

export default PublicationTable;