import React from "react";
import RepoItemHelper from "../../../util/RepoItemHelper";
import i18n from "i18next";

export function ReactStatusTableCell(props) {
    const rowItem = props.props;

    return (
        <div className={"status-label-wrapper"}>
            <div className={"status-label-container"}>
                <div className={"status-label-indicator"} style={{backgroundColor: RepoItemHelper.getStatusColor(rowItem)}}/>
                <div className={"status-label-text"}>
                    {rowItem.isArchived ? i18n.t('publication.state.archived') : RepoItemHelper.getStatusText(rowItem)}
                </div>
            </div>
        </div>
    )
}