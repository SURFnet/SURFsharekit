import React from "react";

export function EmailField(props) {
    let classAddition = '';
    classAddition += (props.readonly ? ' disabled readonly-hidden' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');

    let readonlyValue = ""
    if(props.readonly) {
        readonlyValue = (props.defaultValue && props.defaultValue.length > 0) ? props.defaultValue : "-"
    }

    return <div className={"field-input-wrapper"}>
        {props.readonly && <div className={"field-input text readonly"}>{readonlyValue}</div>}
        <input type="email"
               disabled={props.readonly}
               className={"field-input email" + classAddition}
               defaultValue={props.defaultValue}
               placeholder={props.placeholder}
               onChange={(event) => {
                   props.onChange(event);
               }}
               ref={props.register && props.register({
                   required: props.isRequired
               })}
               name={props.name}/>
    </div>
}