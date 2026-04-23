import React, {useEffect, useState} from 'react';
import styled, {css, keyframes} from 'styled-components';
import {cultured, greyDarker, greyLight, greyMedium, majorelle, majorelleLight} from "../Mixins";

export function Checkbox({onChange, register, initialValue, style, name, disabled, type = "checkbox", ...rest}) {

    const registration = register && name ? register(name, {disabled: !!disabled}) : null
    const rhfOnChange = registration?.onChange
    const rhfOnBlur = registration?.onBlur
    const rhfRef = registration?.ref

    const [checked, setChecked] = useState(initialValue)

    useEffect(() => {
        setChecked(initialValue)
    }, [initialValue])

    const onInputChange = (e) => {
        const {checked: nextChecked} = e.target
        setChecked(nextChecked)
        rhfOnChange?.(e)
        if (onChange) {
            onChange(nextChecked)
        }
    }

    return (
        <CheckboxRoot>
            <Input id={name}
                   {...rest}
                   ref={rhfRef}
                   type={type}
                   name={name}
                   disabled={disabled}
                   style={style}
                   checked={checked}
                   onBlur={rhfOnBlur}
                   onChange={onInputChange}/>
            <CheckMark htmlFor={name} disabled={disabled} style={style} checked={checked}>
                <CheckIcon
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    checked={checked}
                >
                    <polyline strokeWidth={4} points="20 6 9 17 4 12" />
                </CheckIcon>
            </CheckMark>
        </CheckboxRoot>
    );
}

const bounceAnimation = keyframes`
  0% {
    transform: scale(1);
  }
  10% {
    transform: scale(1.3);
  }
  30% {
    transform: scale(0.8);
  }
  50% {
    transform: scale(1.2);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    transform: scale(1);
  }
`;

const CheckboxRoot = styled.div`
    display: inline-block;
    position: relative;
    cursor: pointer;
`;

const Input = styled.input`
    width: 100%;
    height: 100%;
    margin: 0;
    position: absolute;
    opacity: 0;
    cursor: pointer;
    pointer-events: none;
`;

const CheckMark = styled.label`
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    width: ${props => props.width ? props.width : "16px"};
    height: ${props => props.height ? props.height : "16px"};
    border: 1.5px solid ${greyDarker};
    border-radius: 2px;
    background-color: ${(props) => props.disabled ? (props.checked ? greyDarker : greyLight) : (props.checked ? majorelle : cultured)};
    transition: background-color 0.2s ease-in-out;
    &:hover {
        background-color: ${(props) => props.disabled ? (props.checked ? greyDarker : greyLight)  : (props.checked ? majorelle : majorelleLight)};
    }
`;

const CheckIcon = styled.svg`
  fill: none;
  stroke: white;
  stroke-width: 2px;
  visibility: ${(props) => (props.checked ? 'visible' : 'hidden')};
  transform: ${(props) => (props.checked ? 'scale(1)' : 'scale(0)')};
  transition: transform 0.2s ease-in-out;
  ${(props) =>
          props.checked &&
          css`
      animation: ${bounceAnimation} 0.4s;
    `}
`;
