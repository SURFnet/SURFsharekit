import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import './expandablelistloadingindicator.scss'
import React from "react";

export function ExpandableListLoadingIndicator({loadingText}) {
    return (
        <div className={"expandable-list-loading-indicator"}>
            <LoadingIndicator/>
            <div className={"loading-subtitle"}>{loadingText}</div>
        </div>
    )
}