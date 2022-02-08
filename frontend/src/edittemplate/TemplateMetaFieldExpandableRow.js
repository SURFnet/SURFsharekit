import {useTranslation} from "react-i18next";
import React, {useRef, useState} from "react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faChevronDown, faChevronRight} from "@fortawesome/free-solid-svg-icons";
import './templatemetafieldexpandablerow.scss'
import {FormField, InputField} from "../components/field/FormField";

export function TemplateMetaFieldExpandableRow(props) {

    const {t} = useTranslation()
    const [templateMetaField, setTemplateMetaField] = useState(props.metaField)
    const [isExpanded, setIsExpanded] = useState(false)
    const templateMetaFieldTitle = t('language.current_code') === 'nl' ? templateMetaField.titleNL : templateMetaField.titleEN;
    const templateMetaFieldDescription = t('language.current_code') === 'nl' ? templateMetaField.descriptionNL : templateMetaField.descriptionEN;
    const formRef = useRef()

    function onClickExpand() {
        setIsExpanded(!isExpanded)
    }

    function onValueChanged(e) {
        if (formRef) {
            formRef.current[e.target.name] = e.target.value
        }
    }

    let readOnly = templateMetaField.locked || !props.template.permissions.canEdit

    return (
        <div className={"template-meta-field-expandable-row-wrapper"} ref={formRef}>
            <div className={"template-meta-field-expandable-row-container"}
                 onClick={onClickExpand}>
                <div className={"meta-field-row"}>
                    <div className={"meta-field-row-information"}>
                        <FontAwesomeIcon icon={isExpanded ? faChevronDown : faChevronRight} className={"icon-chevron"}/>
                        <InputField key={templateMetaField.key}
                                    readonly={readOnly}
                                    defaultValue={templateMetaField.enabled}
                                    type={"switch"}
                                    register={props.register}
                                    name={templateMetaField.key}
                                    setValue={props.setValue}
                                    onValueChanged={(value) => {
                                        templateMetaField.enabled = value;
                                    }}
                        />
                        <div className={"row-text"}>
                            <h5 className={"bold-text"}>{templateMetaFieldTitle}</h5>
                            <div className={"normal-text"}>{templateMetaFieldDescription &&
                            <span className={"white-space"}> - </span>}{templateMetaFieldDescription}</div>
                        </div>
                    </div>

                    <div className={"checkbox-wrapper"}>
                        {!templateMetaField.locked && <div className={"checkbox"}>
                            <div className="option">
                                <input id={templateMetaField.key}
                                       defaultChecked={templateMetaField.required}
                                       type="checkbox"
                                       onChange={(e) => {
                                           templateMetaField.required = e.target.checked;
                                       }}
                                       disabled={readOnly}/>
                                <label htmlFor={templateMetaField.key}>
                                    {t('templates.required')}
                                </label>
                            </div>
                        </div>}
                    </div>
                </div>
                <div className={"template-meta-field-form-wrapper"}>
                    <div className={"template-meta-field-form-container"}
                         style={{"display": isExpanded ? "block" : "none"}}>
                        <ExpandedTemplateMetaFieldForm/>
                    </div>
                </div>
            </div>
        </div>
    )

    function ExpandedTemplateMetaFieldForm() {
        return (
            <div className={"flex-column"}>
                <div className={"form-columns-container flex-row"}>
                    <div className={"flex-column form-field-container"}>
                        <FormField key={getFieldName(templateMetaField, "titleNL")}
                                   name={getFieldName(templateMetaField, "titleNL")}
                                   defaultValue={getFieldDefaultValue(templateMetaField, "titleNL")}
                                   type={"text"}
                                   label={t('templates.titleNL')}
                                   readonly={readOnly}
                                   register={props.register}
                                   setValue={props.setValue}
                                   onValueChangedUnchecked={onValueChanged}
                        />
                    </div>
                </div>
                <div className={"form-columns-container flex-row"}>
                    <div className={"flex-column form-field-container"}>
                        <FormField key={getFieldName(templateMetaField, "titleEN")}
                                   name={getFieldName(templateMetaField, "titleEN")}
                                   defaultValue={getFieldDefaultValue(templateMetaField, "titleEN")}
                                   type={"text"}
                                   label={t('templates.titleEN')}
                                   readonly={readOnly}
                                   register={props.register}
                                   setValue={props.setValue}
                                   onValueChangedUnchecked={onValueChanged}
                        />
                    </div>
                </div>
                <div className={"form-columns-container flex-row"}>
                    <div className={"flex-column form-field-container"}>
                        <FormField key={getFieldName(templateMetaField, "infoTextNL")}
                                   name={getFieldName(templateMetaField, "infoTextNL")}
                                   defaultValue={getFieldDefaultValue(templateMetaField, "infoTextNL")}
                                   type={"text"}
                                   label={t('templates.infoTextNL')}
                                   readonly={readOnly}
                                   register={props.register}
                                   setValue={props.setValue}
                                   onValueChangedUnchecked={onValueChanged}
                        />
                    </div>
                </div>
                <div className={"form-columns-container flex-row"}>
                    <div className={"flex-column form-field-container"}>
                        <FormField key={getFieldName(templateMetaField, "infoTextEN")}
                                   name={getFieldName(templateMetaField, "infoTextEN")}
                                   defaultValue={getFieldDefaultValue(templateMetaField, "infoTextEN")}
                                   type={"text"}
                                   label={t('templates.infoTextEN')}
                                   readonly={readOnly}
                                   register={props.register}
                                   setValue={props.setValue}
                                   onValueChangedUnchecked={onValueChanged}
                        />
                    </div>
                </div>
            </div>
        )
    }

    function getFieldName(templateMetaFieldTemp, key) {
        return `${templateMetaFieldTemp.key}_${key}`;
    }

    function getFieldDefaultValue(templateMetaFieldTemp, key) {
        const formDataKeyName = `${templateMetaFieldTemp.key}_${key}`
        if (formRef && formRef.current && formRef.current[formDataKeyName]) {
            return formRef.current[formDataKeyName]
        } else {
            return templateMetaFieldTemp[key]
        }
    }
}