import React from "react";
import './buttontext.scss'

function ButtonText(props) {

    let buttonClass;

    switch (props.buttonType) {
        case "callToAction":
            buttonClass = "call-to-action"
            break;
        case "add":
            buttonClass = "add"
            break;
        default:
            buttonClass = "primary"
    }

    return (
        <div className={`surf-button-text ${props.className ?? ""}`}
             onClick={props.disabled ? undefined : props.onClick}>
            <div
                className={`surf-button-text-container ${buttonClass}${props.disabled ? " button-text-disabled" : ""}`}>
                {props.text}
            </div>
        </div>
    );
}

export default ButtonText;