import React, {useEffect, useRef} from "react";
import './personfield.scss'
import {ProfileBanner} from "../../profilebanner/ProfileBanner";

export function PersonField(props) {
    const hiddenInputPersonRef = useRef();

    useEffect(() => {
        props.register({name: props.name})
        props.setValue(props.name, JSON.stringify(props.person ?? props.defaultValue))
    }, []);

    function getPersonId() {
        if (props.person) {
            return props.person.id
        } else if (props.defaultValue) {
            return props.defaultValue.id
        }
        return null
    }

    function getPersonImageUrl() {
        if (props.person) {
            return props.person.imageURL
        } else if (props.defaultValue && props.defaultValue.summary) {
            return props.defaultValue.summary.imageURL
        }
        return null
    }

    function getPersonName() {
        if (props.person) {
            return props.person.name
        } else if (props.defaultValue && props.defaultValue.summary) {
            return props.defaultValue.summary.name
        }
        return null
    }

    return (
        <div className={"field person"} onClick={() => {
            window.open("../../profile/" + (props.person ? props.person.id : props.defaultValue.summary.id))
        }}>
            {<ProfileBanner id={getPersonId()}
                            name={getPersonName()}/>}
            <input type="hidden" ref={hiddenInputPersonRef} name={props.name}
                   value={props.defaultValue ? undefined : JSON.stringify(props.defaultValue)}/>
        </div>
    )
}