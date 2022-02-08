import React, {useState} from "react";
import '../checkbox/checkbox.scss'
import {useTranslation} from "react-i18next";

export function CheckboxGroup(props) {
    const [selection, setSelection] = useState(props.defaultValue ?? []);
    const {t} = useTranslation();

    let classAddition = '';
    classAddition += (props.readonly ? ' disabled' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');

    function hasDefaultValue(option) {
        return selection && selection.includes(option.value);
    }

    function toggleSelection(option) {
        let newSelection = selection;

        if (hasDefaultValue(option)) {
            newSelection = selection.filter(s => s !== option.value)
        } else {
            if(selection.includes(option.value)){
                newSelection.splice(newSelection.indexOf(option.value), 1)
            } else {
                newSelection.push(option.value);
            }
        }
        setSelection(newSelection);
        props.onChange(newSelection)
    }

    let options = props.options.map((option, index) => {
            return <div key={index} className="option">
                <input
                    defaultChecked={hasDefaultValue(option)}
                    type="checkbox"
                    id={option.value}
                    onChange={() => toggleSelection(option)}
                    disabled={props.readonly}
                    value={option.value}
                    name={props.name}/>

                <label htmlFor={option.value}>
                    {t('language.current_code') === 'nl' ? option.labelNL : option.labelEN}
                </label>
            </div>
        }
    );

    return <fieldset className={"field-input checkbox " + classAddition}>
        {options}
    </fieldset>
}