import React from "react";
import './statusicon.scss'

function StatusIcon(props) {
    return (
        <div className={"status-icon"} onClick={props.onClick}>
            <div className={'circle ' + props.color} style={(props.colorHex) ? {backgroundColor: props.colorHex} : {}}/>
            <div className={"text"}>{props.text}</div>
        </div>
    );
}

export default StatusIcon;
