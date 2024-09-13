import React from "react";

export function TextAreaField(props) {
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
    if(props.readonly) {
        readonlyValue = (props.defaultValue && props.defaultValue.length > 0) ? props.defaultValue : "-"
    }
    return <div className={"field-input-wrapper"}>
        {props.readonly && <div className={"field-input text readonly"}>{readonlyValue}</div>}
        <textarea
            disabled={props.readonly}
            className={"field-input text-area" + classAddition}
            defaultValue={props.defaultValue ?? ''}
            placeholder={props.placeholder}
            onInput={(e) => {
                if ((!oldValue || oldValue.length === 0) && e.target.value.length > 0) {
                    props.onChange(e)
                } else if ((oldValue && oldValue.length > 0) && e.target.value.length === 0) {
                    props.onChange(e)
                }
                oldValue = e.target.value;
            }}
            ref={props.register({
                required: props.isRequired,
                validate: (v => validateWithRegex(v, props.validationRegex))
            })}
            name={props.name}/>
    </div>
}