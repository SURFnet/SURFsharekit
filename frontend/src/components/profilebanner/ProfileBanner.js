import React from "react";
import './profilebanner.scss'
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faUser} from "@fortawesome/free-solid-svg-icons";

export function ProfileBanner(props) {
    return <div className={"profile-banner"}>
        {!props.imageUrl && <div className={"profile-banner-image placeholder"}><FontAwesomeIcon className={"icon"} icon={faUser}/></div>}
        {props.imageUrl && <img className={"profile-banner-image"} alt={""} src={props.imageUrl}/>}
        {props.name && <div className="name">{props.name}</div>}
    </div>
}