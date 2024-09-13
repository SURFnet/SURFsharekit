import React from "react";
import './iconbuttontext.scss'
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";

function IconButtonText(props) {
    return (
        <div className={`surf-icon-button-text ${props.className ?? ""}`} onClick={props.onClick} style={props.style}>
            <div className="icon-button">
                { props.faIcon ?
                    <FontAwesomeIcon icon={props.faIcon}/>
                    :
                    <img src={props.icon} alt="icon"/>
                }
            </div>
            {
               props.buttonText && <span className={"button-text"}><h5>{props.buttonText}</h5></span>
            }
        </div>
    );
}

export default IconButtonText;