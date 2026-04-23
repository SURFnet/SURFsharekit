import StatusIcon from "../components/statusicon/StatusIcon";
import RepoItemHelper from "../util/RepoItemHelper";
import warningIcon from "../resources/icons/warning_icon.svg"
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import {faCaretLeft, faCaretRight, faTrash} from "@fortawesome/free-solid-svg-icons";
import React, {useEffect, useRef, useState} from "react";
import {useGlobalState} from "../util/GlobalState";
import {useTranslation} from "react-i18next";
import styled from "styled-components";
import {desktopSideMenuWidth, spaceCadet, SURFShapeLeft} from "../Mixins";
import {ThemedH3} from "../Elements";
import {useOutsideElementClicked} from "../util/hooks/useOutsideElementClicked";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import "../../src/default-publication-view/default-publication-view.scss"
import SURFButton from "../styled-components/buttons/SURFButton";
import {SwitchField} from "../components/field/switch/Switch";
import {faArchive} from "@fortawesome/free-solid-svg-icons/faArchive";
import {faUndo} from "@fortawesome/free-solid-svg-icons/faUndo";
import DeletePublicationPopup from "../publications/deletepublicationpopup/DeletePublicationPopup";
import {StorageKey, useAppStorageState} from "../util/AppStorage";

function DefaultPublicationViewHeader({showOnlyRequiredFields, setShowOnlyRequiredFields, ...props}){
    const {t} = useTranslation()
    const [isSideMenuCollapsed, setIsSideMenuCollapsed] = useGlobalState('isSideMenuCollapsed', false);
    const [user] = useAppStorageState(StorageKey.USER);

    const isOwner = props.repoItem?.creator?.id === user?.id;
    const permissionCanEdit =  props.repoItem ? props.repoItem.permissions.canEdit && !props.repoItem.isRemoved : null
    const permissionCanPublish = props.repoItem ? props.repoItem.permissions.canPublish && !props.repoItem.isRemoved : null
    const permissionCanDelete = props.repoItem ? props.repoItem.permissions.canDelete : null
    const permissionCanArchive = props.repoItem ? props.repoItem.permissions.canArchive : null

    const repoItemIsDraft = props.repoItem ? props.repoItem.status.toLowerCase() === "draft" : null
    const repoItemIsRevising = props.repoItem ? props.repoItem.status.toLowerCase() === "revising" : null
    const repoItemIsSubmitted = props.repoItem ? props.repoItem.status.toLowerCase() === "submitted" : null
    const repoItemIsPublished = props.repoItem ? props.repoItem.status.toLowerCase() === "published" : null
    const repoItemIsArchived = props.repoItem ? props.repoItem.status.toLowerCase() === "archived" : null
    const isRemoved = props.repoItem ? props.repoItem.isRemoved : false
    const needsToBeFinished = props.repoItem ? props.repoItem.needsToBeFinished : false

    return (
        <>
            {
                props.repoItem &&
                    <PublicationHeaderWrapper ref={props.publicationHeaderRef} isSideMenuCollapsed={isSideMenuCollapsed}>
                        <PublicationHeader isSideMenuCollapsed={isSideMenuCollapsed}>
                            <PublicationHeaderTop>
                                <PublicationStatus>
                                    <StatusIcon
                                        colorHex={RepoItemHelper.getStatusColor(props.repoItem)}
                                        text={RepoItemHelper.getStatusText(props.repoItem)}
                                    />
                                    { (repoItemIsDraft && !props.repoItem.isRemoved && props.repoItem.isHistoricallyPublished) ?
                                        <WarningIcon /> : <></> }
                                </PublicationStatus>
                                <HeaderButtonContainer>
                                    {(repoItemIsRevising || (repoItemIsDraft && props.isEditing)) && (
                                        <SwitchField
                                            placeholder={t("switch_field.only_required_fields")}
                                            defaultValue={showOnlyRequiredFields}
                                            onChange={() => setShowOnlyRequiredFields(prevState => (prevState === 0 ? 1 : 0))}
                                            customCss={"show_required_fields"}
                                            extraSwitchCss={"show_required_fields_switch"}
                                        />
                                    )}
                                    <PublicationActions>
                                        {getArchiveButton()}
                                        {getDeleteButton()}
                                        <FormActions>
                                            {getButtons()}
                                        </FormActions>
                                    </PublicationActions>
                                </HeaderButtonContainer>
                            </PublicationHeaderTop>
                            <PublicationTitle>{ props.repoItem.title ? props.repoItem.title : RepoItemHelper.getTranslatedRepoType(props.repoItem.type)}</PublicationTitle>
                        </PublicationHeader>
                    </PublicationHeaderWrapper>
            }

        </>

    )

    function getButtons() {
        let buttons = [];

        if ((permissionCanEdit || (permissionCanDelete && isRemoved)) && props.repoItem.status !== "Archived") {
            buttons.push(props.saveEditButton)
        }

        if (repoItemIsDraft && !isRemoved && !needsToBeFinished) {
            buttons.push(props.saveAndPublishButton)
        }

        if (permissionCanPublish && (repoItemIsSubmitted || repoItemIsRevising)) {
            buttons.push(props.reviseButton)
        }

        return buttons;
    }

    function getArchiveButton() {
        if (!permissionCanArchive) {
            return
        }

        if (repoItemIsArchived) {
            return <IconButtonText
                faIcon={faUndo}
                onClick={props.restorePublicationFromArchive}
            />
        } else {
            return <IconButtonText
                faIcon={faArchive}
                onClick={props.archivePublication}
            />
        }
    }

    function getDeleteButton() {
        const canDeleteUnpublishedPublications =  permissionCanDelete && !isRemoved && !repoItemIsPublished
        const canDeletePublishedPublications = permissionCanPublish && repoItemIsPublished

        if ((repoItemIsPublished && canDeletePublishedPublications) || (!repoItemIsPublished && canDeleteUnpublishedPublications)) {
            return (
                <IconButtonText
                    faIcon={faTrash}
                    onClick={props.deletePublication}
                />
            )
        }

        if ((repoItemIsPublished || repoItemIsArchived) && isOwner && !permissionCanDelete) {
            return (
                <SURFButton
                    padding={"0px 32px"}
                    backgroundColor={'transparent'}
                    textColor={spaceCadet}
                    border={`${spaceCadet} 2px solid`}
                    text={t("publication.delete_request.title")}
                    onClick={() => {
                        const confirmAction = (reason) => {
                            if (props.requestDeletePublication) {
                                props.requestDeletePublication(reason)
                            }
                        }
                        DeletePublicationPopup.show(confirmAction)
                    }}
                />
            )
        }
    }
}

function WarningIcon(props) {
    const {t} = useTranslation()
    const popup = useRef();
    const [isTooltipShown, setIsTooltipShown] = useState(false);
    const [isOutsideWindow, setIsOutsideWindow] = useState(null);
    useOutsideElementClicked(() => setIsTooltipShown(false), popup);

    useEffect(() => {
        if (isTooltipShown && !isOutsideWindow) {
            setIsOutsideWindow(isOutsideViewport(popup))
        }
    }, [isTooltipShown])

    return <div className="warning-icon"
                onMouseEnter={() => {
                    setIsTooltipShown(true)
                }}
                onMouseLeave={() => {
                    setIsTooltipShown(false)
                }}
    >
        <div className={"info-icon-wrapper"}>
            <img src={warningIcon}/>
        </div>
        {isTooltipShown &&
            <div>
                <div className={`tooltip-popup ${isOutsideWindow ? "left" : "right"}`}>
                    <div className={"tooltip-content"} ref={popup} dangerouslySetInnerHTML={{__html: t("publication.warning_tooltip")}}>
                    </div>
                    <FontAwesomeIcon className={"arrow-left"} icon={isOutsideWindow ? faCaretRight : faCaretLeft}/>
                </div>
            </div>
        }
    </div>;

    function isOutsideViewport(element) {
        const rect = element.current.getBoundingClientRect()
        return (
            rect.right >= (window.innerWidth || document.documentElement.clientWidth)
        )
    }
}


export const PublicationStatus = styled.div`
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    box-sizing:border-box;
`;

export const HeaderButtonContainer = styled.div`
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 30px;
  align-items: center;
`

export const PublicationActions = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    align-items: center;
`;

export const FormActions = styled.div`
  display: flex;
  gap: 10.5px;
`;

const PublicationHeaderWrapper = styled.div`
    background: linear-gradient(270deg, #F8F8F8 0%, #F0F0F0 82.57%);
    max-width: 1760px;
    width: ${props => props.isSideMenuCollapsed ? "100%" : `calc(100% - ${desktopSideMenuWidth})`};
    padding: 40px 160px 10px 160px;
    border-bottom-left-radius: 15px;
    border-bottom-right-radius: 15px;
    margin: ${props => props.isSideMenuCollapsed ? '0px auto' : `0px 0px 0px ${desktopSideMenuWidth}`};
    position: relative;
    transition: all 0.2s ease;
    transition-property: width padding margin;
`;

export const PublicationHeader = styled.div`
    background-color: white;
    min-height: 121px !important;
    display: flex;
    flex-direction: column;
    width: 100%;
    padding: 18px 23px 20px 48px;
    z-index: 100;
    box-shadow: 0px 4px 10px rgba(196, 196, 196, 0.2);
    ${SURFShapeLeft};
    gap: 15px !important;
    transition: all 0.2s ease;
    transition-property: width padding margin;
`;

export const PublicationHeaderTop = styled.div`
  width: 100%;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 10px;
  justify-content: space-between;
  box-sizing: border-box;
`;

export const PublicationTitle = styled(ThemedH3)`
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  word-break: break-word;
`;


export default DefaultPublicationViewHeader;
