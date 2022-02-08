import React from "react";

export function SearchField(props) {
    let oldValue = props.defaultValue;

    return <input type="text"
                  disabled={props.readonly}
                  className={"field-input text"}
                  defaultValue={props.defaultValue}
                  placeholder={props.placeholder}
                  onChange={(e) => {
                      if ((!oldValue || oldValue.length === 0) && e.target.value.length > 0) {
                          props.onChange(e)
                      } else if ((oldValue && oldValue.length > 0) && e.target.value.length === 0) {
                          props.onChange(e)
                      }
                      oldValue = e.target.value;
                  }}
                  name={props.name}/>
}