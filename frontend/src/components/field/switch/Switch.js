import React, {useEffect, useState} from "react";
import './switch.scss'
import {useTranslation} from "react-i18next";

export function SwitchField(props) {
    const {t} = useTranslation();
    const [isChecked, setIsChecked] = useState(props.defaultValue !== "0" && props.defaultValue !== 0 && props.defaultValue !== undefined && props.defaultValue !== null);

    let classAddition = '';
    classAddition += (props.readonly ? ' disabled readonly' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');

    useEffect(() => {
        if (props.register) {
            props.register({name: props.name}, {required: props.isRequired})
            props.setValue(props.name, isChecked)
        }
    }, [props.register])

    useEffect(() => {
        if (props.setValue) {
            props.setValue(props.name, isChecked, {shouldDirty: true})
        }
    }, [isChecked])

    function Switch(switchProps) {
        return (
            <div className='switch'
                 onClick={(e) => {
                     e.stopPropagation()
                     if (!switchProps.disabled) {
                         const newIsChecked = !isChecked
                         setIsChecked(newIsChecked)
                         switchProps.onChange(newIsChecked)
                     }
                 }}
            >
                {isChecked ? <OnIcon props={switchProps}/> : <OffIcon props={switchProps}/>}
                <label>
                    {switchProps.placeholder}
                </label>
            </div>
        )
    }

    function OnIcon(iconProps) {
        return <div className={'switch-icon on'}>
            <div className={'switch-text'}>
                {t('switch_field.on')}
            </div>
            <div className={'switch-bulb'}>
                {iconProps.props.disabled === 1 && <i className="fas fa-lock"/>}
            </div>
        </div>
    }

    function OffIcon(iconProps) {
        return <div className={'switch-icon off'}>
            <div className={'switch-bulb'}>
                {iconProps.props.disabled === 1 && <i className="fas fa-lock"/>}
            </div>
            <div className={'switch-text'}>
                {t('switch_field.off')}
            </div>
        </div>
    }

    return <fieldset className={"field-input switch " + classAddition}>
        <Switch
            isChecked={isChecked}
            onChange={props.onChange}
            setValue={props.setValue}
            placeholder={props.placeholder}
            disabled={props.readonly}
            register={props.register}
            name={props.name}/>
    </fieldset>
}


