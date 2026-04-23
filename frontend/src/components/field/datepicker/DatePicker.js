import React, {useEffect, useRef, useState} from "react";
import "./datepicker.scss";
import {default as Base} from "react-multi-date-picker";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faCalendar, faChevronDown} from "@fortawesome/free-solid-svg-icons";
import datepicker_nl from "../../../resources/locales/nl/datepicker_nl";
import {useTranslation} from "react-i18next";
import datepicker_en from "../../../resources/locales/en/datepicker_en";
import {validateDependencyKeyGroup} from "../../../util/DependencyKeyValidation";

function DatePicker({name, placeholder, register, isRequired, defaultValue, dependencyKey, dependencyGroupKeys, dependencyGroupLabels, getValues, ...props}) {
    const {i18n, t} = useTranslation()
    const inputRef = useRef();
    const [pickerValue, setPickerValue] = useState(defaultValue ? new Date(defaultValue) : null);

    useEffect(() => {
        register(name, {
            required: isRequired,
            validate: () => validateDependencyKeyGroup({
                dependencyKey,
                dependencyGroupKeys,
                dependencyGroupLabels,
                getValues
            })
        });
    }, [register]);

    useEffect(() => {
        if (pickerValue) {
            props.onChange(pickerValue.toISOString().substring(0, 10))
            props.setValue(name, pickerValue.toISOString().substring(0, 10))
        } else {
            props.onChange(null)
            props.setValue(name, null)
        }
    }, [pickerValue]);

    const onChange = ({ validatedValue }) => {
        if (Array.isArray(validatedValue) === false || validatedValue.length === 0 || validatedValue[0] == null) {
            setPickerValue(null)
            return
        }

        const dateParts = validatedValue[0]?.split("-")

        setPickerValue(new Date(Date.UTC(dateParts[2], dateParts[1] - 1, dateParts[0])))
    }

    return <div className={`field-input-wrapper text-field ${props.hasError && 'invalid'}`}>
        <div className={`datepicker-wrapper ${props.readonly && 'readonly'}`}>
            {!props.readonly && <FontAwesomeIcon icon={faCalendar} /> }
            <Base
                ref={inputRef}
                disabled={props.readonly}
                name={name}
                shadow={false}
                format={"DD-MM-YYYY"}
                placeholder={placeholder ?? t('datepicker.placeholder')}
                value={pickerValue}
                onChange={(e, ele) => onChange(ele) }
                locale={i18n.language === 'nl' ? datepicker_nl : datepicker_en}
                offsetY={5}
                highlightToday={false}
            />
            {!props.readonly &&
                <FontAwesomeIcon icon={faChevronDown} onClick={() => console.log(inputRef.current?.querySelector('input')?.focus())} />
            }

        </div>
    </div>
}

export default DatePicker