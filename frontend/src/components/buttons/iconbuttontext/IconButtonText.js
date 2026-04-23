import React from "react";
import './iconbuttontext.scss'
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";

function IconButtonText(props) {
    const isDisabled = props.disabled || (props.className && props.className.includes('disabled'));

    const handleClick = (event) => {
        if (isDisabled) {
            return;
        }
        props.onClick?.(event);
    };

    return (
        <div className={`surf-icon-button-text ${props.className ?? ""}`}
             onClick={handleClick}
             style={props.style}
             aria-disabled={isDisabled}>
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