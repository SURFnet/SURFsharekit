import React from "react";
import './iconbutton.scss'
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";

function IconButton(props) {

    let buttonClass;

    switch (props.buttonType) {
        case "callToAction":
            buttonClass = "call-to-action"
            break;
        default:
            buttonClass = "primary"
            break;
    }

    return (
        <div className={`surf-icon-button ${props.className ?? ""}`}
             onClick={props.onClick}>
            <div className={`surf-icon-button-container ${buttonClass}`}>
                <FontAwesomeIcon className={"surf-icon-button-icon"} icon={props.icon}/>
                <div className={"surf-icon-button-text"}>{props.text}</div>
            </div>
        </div>
    );
}

export default IconButton;