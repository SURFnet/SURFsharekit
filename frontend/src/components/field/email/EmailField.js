import React from "react";
import {useTranslation} from "react-i18next";
import {validateDependencyKeyGroup} from "../../../util/DependencyKeyValidation";

export function EmailField(props) {
    const { t } = useTranslation();
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
               {...props.register(props.name, {
                   required: props.isRequired,
                   pattern: {
                       value: /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
                       message: t('validation.email')
                   },
                   validate: () => validateDependencyKeyGroup({
                       dependencyKey: props.dependencyKey,
                       dependencyGroupKeys: props.dependencyGroupKeys,
                       dependencyGroupLabels: props.dependencyGroupLabels,
                       getValues: props.getValues
                   })
               })}
               name={props.name}/>
    </div>
}