import React from "react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import './icontitleheader.scss'

export function IconTitleHeader(props) {

    return (
        <div className={"icon-title-header"}>
            <div className={"icon-header-container"}>
                <FontAwesomeIcon icon={props.icon}/>
            </div>
            <h1>{props.title}</h1>
        </div>
    )
}