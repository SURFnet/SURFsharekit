import React, {useEffect, useRef, useState} from "react";
import "../../../components/field/datepicker/datepicker.scss";
import {default as Base} from "react-multi-date-picker";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faCalendar, faChevronDown} from "@fortawesome/free-solid-svg-icons";
import datepicker_nl from "../../../resources/locales/nl/datepicker_nl";
import {useTranslation} from "react-i18next";
import datepicker_en from "../../../resources/locales/en/datepicker_en";


function ReportsDatePicker({
                               name,
                               placeholder,
                               isRequired,
                               defaultValue,
                               onChange,
                               onBlur,
                           }) {
    const { i18n, t } = useTranslation();
    const inputRef = useRef();
    const [pickerValue, setPickerValue] = useState(defaultValue ? new Date(defaultValue) : null);
    const [value, setValue] = useState(defaultValue ? defaultValue : '');
    const [error, setError] = useState('');

    /**
     * Handles the change event when a date is selected.
     * It formats the date and updates the state, while also handling validation.
     *
     * @param {object} dateClicked - The selected date object from the date picker.
     */
    const handleChange = ({ dateClicked }) => {
        const selectedDate = dateClicked?.format?.();

        if (!selectedDate) {
            resetDate();
            return;
        }

        saveDate(selectedDate);
    };

    /**
     * Saves and formats the selected date.
     *
     * This function takes a date string in 'DD-MM-YYYY' format, converts it to a
     * JavaScript Date object in UTC, and then formats it to 'YYYY-MM-DD' for
     * consistency with ISO standards. The formatted date is stored in the component's state,
     * and validation is applied if the field is required.
     *
     * @param {string} selectedDate - The selected date in 'DD-MM-YYYY' format from the date picker.
     */
    const saveDate = (selectedDate) => {
        const [day, month, year] = selectedDate.split('-').map(Number);
        const newDate = new Date(Date.UTC(year, month - 1, day));
        const formattedDate = newDate.toISOString().substring(0, 10);

        setPickerValue(newDate);
        setValue(formattedDate);
        setError(isRequired && !formattedDate ? 'This field is required' : '');

        onChange && onChange(formattedDate);
    }

    /**
     * Resets the date picker to its default state, clearing the value and setting errors if required.
     */
    const resetDate = () => {
        setPickerValue(null);
        setValue('');
        setError(isRequired ? 'This field is required' : '');
        onChange && onChange(null);
    };

    /**
     * Format any given date to DD-MM-YYYY
     */
    const formatDateToDDMMYYYY = (date) => {
        if (!date) return '';

        const [year, month, day] = date.split('-');
        return `${day}-${month}-${year}`;
    };

    return (
        <div className={`field-input-wrapper text-field ${error ? 'invalid' : ''}`}>
            <div className="datepicker-wrapper">
                <FontAwesomeIcon icon={faCalendar} />
                <Base
                    ref={inputRef}
                    name={name}
                    shadow={false}
                    format={"DD-MM-YYYY"}
                    placeholder={placeholder ?? t('datepicker.placeholder')}
                    value={pickerValue}
                    onFocusedDateChange={(dateFocused, dateClicked) => { // onFocusDateChange works the same as an onChange, however it focuses on the calender input view.
                        handleChange({dateClicked});
                    }}
                    locale={i18n.language === 'nl' ? datepicker_nl : datepicker_en}
                    offsetY={5}
                    render={() => (
                        <input
                            className="rmdp-input"
                            value={formatDateToDDMMYYYY(value) || ''}
                            placeholder={t('datepicker.placeholder')}
                            readOnly
                            onClick={() => inputRef.current.openCalendar()}  // Open calendar on click
                        />
                    )}
                    highlightToday={false}
                />
                <FontAwesomeIcon
                    icon={faChevronDown}
                    onClick={() => inputRef.current?.querySelector('input')?.focus()}
                />
            </div>
            {error && <div className="error-message">{error}</div>}
            <input
                type="hidden"
                name={name}
                value={value}
                required={isRequired}
            />
        </div>
    );
}
export default ReportsDatePicker