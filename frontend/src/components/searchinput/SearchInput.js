import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faSearch} from "@fortawesome/free-solid-svg-icons";
import React from "react";
import './searchinput.scss'

export function SearchInput(props) {

    return (
        <div className={'search-input-container'}>
            <input type="text"
                   defaultValue={props.defaultValue}
                   className={"field-input text"}
                   placeholder={props.placeholder}
                   onKeyDown={props.onKeyDown}
                   onChange={props.onChange}
                   value={props.value}
            />
            <FontAwesomeIcon icon={faSearch}/>
        </div>
    )
}