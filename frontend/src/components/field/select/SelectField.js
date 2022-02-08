import Constants from "../../../sass/theme/_constants.scss";
import Dropdown from "../../dropdown/Dropdown";
import React from "react";

export function SelectField(props) {
    let classAddition = '';
    classAddition += (props.readonly ? ' disabled readonly' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');

    let borderColor = Constants.inputBorderColor;
    if (props.isValid) {
        borderColor = Constants.textColorValid
    } else if (props.hasError) {
        borderColor = Constants.textColorError;
    }

    return <div className={"field-input" + classAddition} style={{padding: 0, border: 0}}>
        <Dropdown
            readonly={props.readonly}
            placeholder={props.placeholder}
            onChange={props.onChange}
            register={props.register}
            isSearcable={props.isSearchable}
            allowNullValue={true}
            defaultValue={props.defaultValue}
            options={props.options}
            setValue={props.setValue}
            isRequired={props.isRequired}
            borderColor={borderColor}
            name={props.name}
        />
    </div>
}