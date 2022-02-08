import React from "react";
import {InputField} from "../FormField";
import './SwitchRowField.scss'

export function SwitchRowField(props) {

    return(
        <div className={"switch-row-field" + ((props.hidden) ? " hidden" : "")}>
            <InputField readonly={props.readonly}
                        defaultValue={props.defaultValue}
                        type={"switch"}
                        name={props.name}
                        onValueChanged={props.onValueChanged}
                        register={props.register}
                        setValue={props.setValue}
            />
            <div className={"switch-row-text"}>
                <h5 className={"bold-text"}>{props.label}</h5>
                <div className={"normal-text"}>{props.description && <span className={"white-space"}> - </span>}{props.description}</div>
            </div>
        </div>
    )
}