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
import {Tooltip, WarningMessage, WarningMessageContent} from "../FormField";
import {faInfoCircle} from "@fortawesome/free-solid-svg-icons/faInfoCircle";
import i18n from "i18next";
import styled from "styled-components";

function SingleDatePickerField(props) {
    const date = props.defaultValue ? new Date(props.defaultValue) : null;
    const [isCalendarFocused, setIsCalendarFocused] = useState(false);
    const [calendarDate, setCalendarDate] = useState(date ? moment(date) : null);
    const [message, setMessage] = useState();
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

    useEffect(() => {
        if (props.formState) {
            if (props.attributeKey === 'EmbargoDate') {
                setMessage({
                    'nl': 'Wanneer het embargo verloopt, geldt het eerder gekozen toegangsrecht.',
                    'en': 'When the embargo date expires, the access right previously chosen will apply.'
                })
            }
        }
    }, [props.formState])

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
        <DatePickerContainer>
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
            { message && <Tooltip position={'right'} width={'196px'} element={<FontAwesomeIcon style={{alignSelf: "center", marginLeft: "6px"}} icon={faInfoCircle} />} text={message[i18n.language]} />}
        </DatePickerContainer>
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

const DatePickerContainer = styled.div`
  display: flex;
  flex-direction: row;
`

export default SingleDatePickerField;