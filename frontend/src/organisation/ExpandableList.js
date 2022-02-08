import React from "react"
import './expandablelist.scss'
import {ExpandableRow} from "./ExpandableRow";
import {ExpandableListLoadingIndicator} from "./ExpandableListLoadingIndicator";

export function ExpandableList(props) {

    if (props.isLoading || props.data === null) {
        return (
            <div className={"expandable-list"}>
                <ExpandableListLoadingIndicator loadingText={props.loadingText}/>
            </div>
        )
    }

    return (
        <div className={"expandable-list"}>
            {
                props.data.map((institute) =>
                    <ExpandableRow key={institute.id}
                                   data={institute}
                                   isRootInstitute={true}
                                   onClickExpand={props.onClickExpand}/>
                )
            }
        </div>
    )
}