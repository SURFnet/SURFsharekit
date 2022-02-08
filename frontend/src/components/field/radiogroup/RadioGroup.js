import React from "react";
import './radiogroup.scss'
import {useTranslation} from "react-i18next";

export function RadioGroup(props) {
    const {t} = useTranslation();

    let classAddition = '';
    classAddition += (props.className ?? '');
    classAddition += (props.readonly ? ' disabled' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');


    function newSelection(option) {
        props.onChange(option.value)
    }

    let radioOptions = props.options.map((option, index) => {
            return <div key={index} className="option">
                <input
                    defaultChecked={props.defaultValue === option.value}
                    type="radio"
                    id={option.value}
                    disabled={props.readonly}
                    value={option.value}
                    name={props.name}
                    onChange={() => newSelection(option)}/>

                <label htmlFor={option.value}>
                    {(option.label) ? option.label : t('language.current_code') === 'nl' ? option.labelNL : option.labelEN}
                </label>
            </div>
        }
    );

    return <fieldset className={"field-input radio " + classAddition}>
        {radioOptions}
    </fieldset>
}