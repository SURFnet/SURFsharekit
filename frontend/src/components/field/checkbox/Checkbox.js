import React, {useState} from "react";
import './checkbox.scss'
import {useTranslation} from "react-i18next";

export function CheckBoxField(props) {
    const [selection, setSelection] = useState(props.defaultValue ?? []);
    const {t} = useTranslation();

    let classAddition = '';
    classAddition += (props.readonly ? ' disabled' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');

    function hasDefaultValue(option) {
        return selection && selection.includes(option.value)
    }

    function toggleSelection(option) {
        let newSelection = selection;

        if (hasDefaultValue(option)) {
            newSelection = selection.filter(s => s !== option.value)
        } else {
            newSelection.push(option.value);
        }

        setSelection(newSelection);
        props.onChange(newSelection)
    }

    let radioOptions = props.options.map((option, index) => {
        const unique = Math.random().toString(36).slice(2, 12);

        return <div key={index} className="option">
                <input
                    defaultChecked={hasDefaultValue(option)}
                    type="checkbox"
                    id={`${unique}-${option.value}`}
                    onChange={() => toggleSelection(option)}
                    disabled={props.readonly}
                    value={option.value}
                    ref={props.register({
                        required: props.isRequired
                    })}
                    name={props.name}/>

                <label htmlFor={`${unique}-${option.value}`}>
                    {t('language.current_code') === 'nl' ? option.labelNL : option.labelEN}
                </label>
            </div>
        }
    );

    return <fieldset className={"field-input checkbox " + classAddition}>
        {radioOptions}
    </fieldset>
}