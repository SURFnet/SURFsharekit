import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faSearch} from "@fortawesome/free-solid-svg-icons";
import React from "react";

export function ReactTableSearchInput(props) {
    return (
        <div className={'form-field'}>
            <input type="text"
                   className={"field-input text"}
                   placeholder={props.placeholder}
                   onChange={props.onChange}
            />
            <FontAwesomeIcon icon={faSearch}/>
        </div>
    )
}