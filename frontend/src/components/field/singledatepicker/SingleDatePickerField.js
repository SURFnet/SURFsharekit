import React, {useEffect, useRef, useState} from "react";
import './singledatepicker.scss'
import 'react-dates/initialize';
import {SingleDatePicker} from 'react-dates';
import nl from 'moment/locale/nl';
import 'react-dates/lib/css/_datepicker.css';
import {faCalendar, faChevronLeft, faChevronRight} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import * as moment from "moment";
import {useTranslation} from "react-i18next";

function SingleDatePickerField(props) {
    const date = props.defaultValue ? new Date(props.defaultValue) : null;
    const [isCalendarFocused, setIsCalendarFocused] = useState(false);
    const [calendarDate, setCalendarDate] = useState(date ? moment(date) : null);
    const {t} = useTranslation();
    const hadError = useRef(false);
    const isFirstLoad = useRef(true);

    if (nl) {
        //used to fix bug of automarkup deleting the translation import
    }
    if (props.hasError) {
        hadError.current = true;
    }

    useEffect(() => {
        props.register({name: props.name}, {required: props.isRequired});
        if (calendarDate) {
            props.setValue(props.name, calendarDate.format('YYYY-MM-DD'))
        } else {
            props.setValue(props.name, null)
        }
    }, [props.register]);

    useEffect(() => {
        let options = {shouldValidate: hadError.current, shouldDirty: true}
        if (calendarDate) {
            props.setValue(props.name, calendarDate.format('YYYY-MM-DD'), options)
        } else {
            props.setValue(props.name, null, options)
        }
    }, [calendarDate]);


    if (calendarDate) {
        calendarDate.locale(t('language.current_code'));
    }

    function onFocusChanged(focusProps) {
        setIsCalendarFocused(focusProps.focused);
    }

    function onDateChanged(dateProps) {
        if (dateProps.datePickerDate) {
            props.onChange(dateProps.datePickerDate.format('YYYY-MM-DD'));
            setCalendarDate(dateProps.datePickerDate);
        } else {
            props.onChange(null);
            setCalendarDate(null)
        }
    }

    function CustomMonthNav(props) {
        return (
            <div className={"custom-month-nav"}>
                <FontAwesomeIcon icon={props.icon}/>
            </div>
        )
    }

    function renderDay(day) {
        return (
            <div className={"surf-calendar-day-wrapper"}>
                <div className={"surf-calendar-day-content"}>
                    <span>{day.format('D')}</span>
                </div>
            </div>
        );
    }

    let readonlyValue = '-'
    if (props.readonly && props.defaultValue && props.defaultValue.length > 0) {
        readonlyValue = moment(props.defaultValue).format(singleDatePickerDisplayFormat)
    }
    return (
        <div className={"field-input-wrapper"}>
            {props.readonly && <div className={"field-input readonly"}>{readonlyValue}</div>}
            <div className={"single-date-picker-wrapper" + ((props.readonly) ? " readonly-hidden" : "")}>
                <SingleDatePicker
                    {...props.datePickerProps}
                    id={"surf-single-date-picker " + props.name}
                    disabled={props.readonly}
                    date={calendarDate}
                    focused={isCalendarFocused}
                    onFocusChange={({focused}) => onFocusChanged({focused})}
                    isOutsideRange={() => false}
                    customInputIcon={<FontAwesomeIcon icon={faCalendar}/>}
                    navPrev={<CustomMonthNav icon={faChevronLeft}/>}
                    navNext={<CustomMonthNav icon={faChevronRight}/>}
                    renderDayContents={renderDay}
                    onDateChange={datePickerDate => onDateChanged({datePickerDate})}
                />
            </div>
        </div>
    )
}

const singleDatePickerDisplayFormat = moment.localeData().longDateFormat('LL')

SingleDatePickerField.defaultProps = {
    datePickerProps: {
        numberOfMonths: 1,
        verticalSpacing: 0,
        placeholder: '',
        displayFormat: () => singleDatePickerDisplayFormat,
        hideKeyboardShortcutsPanel: true,
        horizontalMargin: 50,
        enableOutsideDays: true,
        firstDayOfWeek: 1
    }
}

export default SingleDatePickerField;