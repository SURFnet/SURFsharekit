import React from "react";

export function TextField(props) {
    let classAddition = '';
    classAddition += (props.readonly ? ' disabled readonly-hidden' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');
    let oldValue = props.defaultValue;

    function validateWithRegex(value, validationRegex) {
        if (!validationRegex) {
            return true;
        }
        if (!value && !props.isRequired) {
            return true
        }
        return RegExp(validationRegex).test(value);
    }

    let readonlyValue = ""
    if (props.readonly) {
        readonlyValue = (props.defaultValue && props.defaultValue.length > 0) ? props.defaultValue : "-"
    }

    function extraValidation(value) {
        if (!props.extraValidation){
            return true;
        }
        if (!value && !props.isRequired) {
            return true
        }
        return props.extraValidation(value)
    }

    return (
        <div className={"field-input-wrapper"}>
            {props.readonly && <div className={"field-input text readonly"}>{readonlyValue}</div>}
            <input type="text"
                   disabled={props.readonly}
                   className={"field-input text " + classAddition}
                   defaultValue={props.defaultValue}
                   placeholder={props.placeholder}
                   size={1}
                   onChange={(e) => {

                       if (props.onValueChangedUnchecked) {
                           props.onValueChangedUnchecked(e)
                       }

                       if ((!oldValue || oldValue.length === 0) && e.target.value.length > 0) {
                           props.onChange(e)
                       } else if ((oldValue && oldValue.length > 0) && e.target.value.length === 0) {
                           props.onChange(e)
                       }

                       oldValue = e.target.value;
                   }}
                   ref={props.register && props.register({
                       required: props.isRequired,
                       validate: (v => validateWithRegex(v, props.validationRegex) && extraValidation(v))
                   })}
                   name={props.name}
                   onClick={(e) => {
                       e.stopPropagation()
                   }}
            />
            {props.hardHint && <div className={'hard-hint'}>{props.hardHint}</div>}
        </div>
    )
}