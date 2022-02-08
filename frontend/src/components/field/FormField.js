import React, {useEffect, useRef, useState} from "react";
import './formfield.scss'
import FormFieldHelper from "../../util/FormFieldHelper";
import {useTranslation} from "react-i18next";
import ProgressBar from "../progressbar/ProgressBar";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faCaretLeft, faCaretRight} from "@fortawesome/free-solid-svg-icons";
import RepoItemHelper from "../../util/RepoItemHelper";
import {PersonField} from "./personfield/PersonField";
import {FileField} from "./filefield/FileField";
import {DragHandle, RepoItemField} from "./repoitem/RepoItemField";
import {SwitchField} from "./switch/Switch";
import SingleDatePickerField from "./singledatepicker/SingleDatePickerField";
import {CheckBoxField} from "./checkbox/Checkbox";
import {SelectField} from "./select/SelectField";
import {TextAreaField} from "./textarea/TextArea";
import {TagField} from "./tag/TagField";
import {TextField} from "./text/TextField";
import {EmailField} from "./email/EmailField";
import {DisciplineField} from "./discipline/DisciplineField";
import {LectorateField} from "./lectorate/LectorateField";
import {SwitchRowField} from "./switchrow/SwitchRowField";
import {SingleRepoItemField} from "./singlerepoitemfield/SingleRepoItemField";
import {OrganisationDropdownField} from "./organisationdropdown/OrganisationDropdownField";
import MultiSelectDropdown from "./multiselectdropdown/MultiSelectDropdown";
import {HelperFunctions} from "../../util/HelperFunctions";
import VerificationPopup from "../../verification/VerificationPopup";
import {NumberField} from "./number/NumberField";
import Api from "../../util/api/Api";
import MultiSelectSuborganisation from "./multiselectsuborganisation/MultiSelectSuborganisation";
import MultiSelectPublisher from "./multiselectpublisher/MultiSelectPublisher"
import {DoiField} from "./doi/DoiField";
import {TreeMultiSelectField} from "./treemultiselect/TreeMultiSelectField";

export function Form(props) {
    const formFieldHelper = new FormFieldHelper();
    const {t} = useTranslation();

    return (
        <form id={`surf-form${props.formId ? "-" + props.formId : ""}`} onSubmit={props.onSubmit}>
            <div className={"form-sections-container"}>
                {
                    props.repoItem.sections.map((section, i) => {
                            const fieldRows = [];
                            let fieldsInFieldRow = []

                            section.fields.forEach(field => {
                                if (field.isSmallField) {
                                    fieldsInFieldRow.push(field);
                                    if (fieldsInFieldRow.length > 3) {
                                        pushFieldRow(fieldsInFieldRow);
                                        fieldsInFieldRow = [];
                                    }
                                } else {
                                    if (fieldsInFieldRow.length > 0) {
                                        pushFieldRow(fieldsInFieldRow);
                                        fieldsInFieldRow = [];
                                    }
                                    fieldsInFieldRow.push(field);
                                    pushFieldRow(fieldsInFieldRow);
                                    fieldsInFieldRow = [];
                                }
                            });

                            pushFieldRow(fieldsInFieldRow);

                            function pushFieldRow(fieldsInFieldRow) {
                                fieldRows.push(
                                    <div className={"form-field-container"} key={'container_' + fieldRows.length}>
                                        {
                                            fieldsInFieldRow.map((fieldInRow) => {
                                                let fieldLabel = ((t('language.current_code') === 'nl' ? fieldInRow.titleNL : fieldInRow.titleEN) ?? '')
                                                if (!fieldLabel || fieldLabel.length === 0) {
                                                    fieldLabel = "\u00a0";
                                                }

                                                let fieldDescription = ((t('language.current_code') === 'nl' ? fieldInRow.descriptionNL : fieldInRow.descriptionEN) ?? '');

                                                if (fieldInRow.fieldType.toLowerCase() === 'switch-row') {
                                                    return <SwitchRowField key={fieldInRow.key}
                                                                           onValueChanged={(changedValue) => {
                                                                               props.onValueChanged(fieldInRow, changedValue)
                                                                           }}
                                                                           setValue={props.setValue}
                                                                           label={fieldLabel}
                                                                           description={fieldDescription}
                                                                           defaultValue={formFieldHelper.getFieldAnswer(props.repoItem, fieldInRow)}
                                                                           name={fieldInRow.key}
                                                                           register={props.register}
                                                                           isRequired={fieldInRow.required}
                                                                           readonly={props.readonly || fieldInRow.readonly === 1}
                                                                           hidden={fieldInRow.hidden === 1}/>
                                                } else {
                                                    fieldLabel = fieldLabel.toUpperCase();
                                                    const debouncedQueryFunction = HelperFunctions.debounce(HelperFunctions.getGetOptionsCallForFieldKey(fieldInRow.key,
                                                        (resultOption) => {
                                                            return {
                                                                "label": t('language.current_code') === 'nl' ? resultOption.labelNL : resultOption.labelEN,
                                                                "labelNL": resultOption.labelNL,
                                                                "labelEN": resultOption.labelEN,
                                                                "coalescedLabelNL": resultOption.coalescedLabelNL,
                                                                "coalescedLabelEM": resultOption.coalescedLabelEM,
                                                                "value": resultOption.id
                                                            }
                                                        }));
                                                    return <FormField key={fieldInRow.key}
                                                                      getOptions={debouncedQueryFunction}
                                                                      classAddition={fieldInRow.isSmallField ? 'small' : ''}
                                                                      type={formFieldHelper.getFieldType(fieldInRow.fieldType)}
                                                                      onValueChanged={(changedValue) => {
                                                                          props.onValueChanged(fieldInRow, changedValue)
                                                                      }}
                                                                      label={fieldLabel}
                                                                      isRequired={fieldInRow.required}
                                                                      options={formFieldHelper.getFieldOptions(fieldInRow)}
                                                                      defaultValue={formFieldHelper.getFieldAnswer(props.repoItem, fieldInRow)}
                                                                      tooltip={t('language.current_code') === 'nl' ? fieldInRow.infoTextNL : fieldInRow.infoTextEN}
                                                                      file={props.file}
                                                                      person={props.person}
                                                                      error={props.errors[fieldInRow.key]}
                                                                      name={fieldInRow.key}
                                                                      register={props.register}
                                                                      setValue={props.setValue}
                                                                      readonly={props.readonly || fieldInRow.readOnly === 1 || fieldInRow.readOnly === true}
                                                                      hidden={fieldInRow.hidden === 1}
                                                                      repoItem={props.repoItem}
                                                                      relatedRepoItem={props.relatedRepoItem}
                                                                      validationRegex={fieldInRow.validationRegex}
                                                                      formReducerState={props.formReducerState}/>
                                                }
                                            })
                                        }
                                    </div>);
                            }

                            let progress = RepoItemHelper.getProgressForSection(props.repoItem, section);
                            if (progress > 0 && section.isUsedForSelection) {
                                progress = 100
                            }
                            progress = Math.round(progress)

                            return (
                                <div className={"form-section"} key={section.id}>
                                    {
                                        props.showSectionHeaders && <div className={"form-section-header"}>
                                            <div className={'section-column'}>
                                                <div className={'section-row'}>
                                                    <h3 style={{flexGrow: 1}}>{t('language.current_code') == 'nl' ? section.titleNL : section.titleEN}</h3>
                                                    <div style={{flexGrow: 0}} className={"progress-bar-wrapper"}>
                                                        <ProgressBar className={"form-section-progressbar"}
                                                                     progress={progress}/>
                                                    </div>
                                                </div>
                                                <div
                                                    className={"section-subtitle"}>{t('language.current_code') == 'nl' ? section.subtitleNL : section.subtitleEN}</div>
                                            </div>
                                        </div>
                                    }
                                    {fieldRows}
                                    {section === props.repoItem.sections[props.repoItem.sections.length - 1] && props.submitButton}
                                </div>
                            )
                        }
                    )
                }
                {
                    props.extraContent
                }
            </div>
        </form>
    )
}

export function FormField(props) {
    const {t} = useTranslation();

    return <div className={"form-field " + props.classAddition + ((props.hidden) ? " hidden" : "")}>
        <div className="form-row">
            <div className={"form-column" + ((props.isRequired && !props.readonly) ? "" : " hidden")}>
                {
                    !props.hideRequired &&
                    <Required/>
                }
            </div>
            <div className="form-column">
                <Label text={props.label} hardHint={props.hardHint}/>
                <div className="form-row">
                    <InputField type={props.type}
                                hardHint={props.hardHint}
                                readonly={props.readonly}
                                placeholder={props.placeholder}
                                extraValidation={props.extraValidation}
                                defaultValue={props.defaultValue}
                                isValid={props.isValid}
                                hasError={props.error}
                                isSearchable={props.isSearchable}
                                options={props.options}
                                isRequired={props.isRequired}
                                getOptions={props.getOptions}
                                onValueChanged={props.onValueChanged}
                                onValueChangedUnchecked={props.onValueChangedUnchecked}
                                validationRegex={props.validationRegex}
                                file={props.file}
                                person={props.person}
                                register={props.register}
                                repoItem={props.repoItem}
                                relatedRepoItem={props.relatedRepoItem}
                                name={props.name}
                                setValue={props.setValue}
                                formReducerState={props.formReducerState}
                                inputRef={props.inputRef}/>
                    {props.tooltip && <Tooltip text={props.tooltip}/>}
                </div>
                <div
                    className={"field-error " + (props.error ? '' : 'hidden')}>{props.error ? errorToLabel(props.error) : 'No error'}</div>
            </div>
        </div>
    </div>

    function errorToLabel(error) {
        switch (error.type) {
            case 'required':
                return t('error_message.field_required');
            default:
                return t('error_message.field_invalid');
        }
    }
}

export function InputField(props) {
    const {t} = useTranslation();

    //onChange returns a string, so if we want to correctly use radio, checkboxes and selects, we need to convert it to an int before posting the onChange value
    let onChange = (v) => {
        if (props.onValueChanged) {
            if (v === "") {
                v = null;
            }
            props.onValueChanged(v);
        }
    };

    switch (props.type) {
        case "email":
            return <EmailField readonly={props.readonly}
                               defaultValue={props.defaultValue}
                               placeholder={props.placeholder}
                               isValid={props.isValid}
                               isRequired={props.isRequired}
                               hasError={props.hasError}
                               onChange={(event) => onChange(event.target.value)}
                               register={props.register}
                               name={props.name}
                               formReducerState={props.formReducerState}/>;
        case "doi":
            return <DoiField readonly={props.readonly}
                             defaultValue={props.defaultValue}
                             isRequired={props.isRequired}
                             placeholder={props.placeholder}
                             isValid={props.isValid}
                             repoItem={props.repoItem}
                             hasError={props.hasError}
                             setValue={props.setValue}
                             onChange={(event) => onChange(event.target.value)}
                             onValueChangedUnchecked={props.onValueChangedUnchecked}
                             register={props.register}
                             name={props.name}
                             validationRegex={props.validationRegex}
                             formReducerState={props.formReducerState}
                             inputRef={props.inputRef}/>;
        case "text":
            return <TextField readonly={props.readonly}
                              defaultValue={props.defaultValue}
                              isRequired={props.isRequired}
                              hardHint={props.hardHint}
                              extraValidation={props.extraValidation}
                              placeholder={props.placeholder}
                              isValid={props.isValid}
                              hasError={props.hasError}
                              onChange={(event) => onChange(event.target.value)}
                              onValueChangedUnchecked={props.onValueChangedUnchecked}
                              register={props.register}
                              name={props.name}
                              validationRegex={props.validationRegex}
                              formReducerState={props.formReducerState}
                              inputRef={props.inputRef}/>;
        case "number":
            return <NumberField readonly={props.readonly}
                                defaultValue={props.defaultValue}
                                isRequired={props.isRequired}
                                placeholder={props.placeholder}
                                isValid={props.isValid}
                                hasError={props.hasError}
                                onChange={(event) => onChange(event.target.value)}
                                onValueChangedUnchecked={props.onValueChangedUnchecked}
                                register={props.register}
                                name={props.name}
                                validationRegex={props.validationRegex}
                                formReducerState={props.formReducerState}
                                inputRef={props.inputRef}/>;
        case "tag":
            return <TagField readonly={props.readonly}
                             defaultValue={props.defaultValue}
                             isRequired={props.isRequired}
                             isValid={props.isValid}
                             options={props.options}
                             hasError={props.hasError}
                             onChange={(event) => onChange(event.target.value)}
                             register={props.register}
                             setValue={props.setValue}
                             name={props.name}/>;
        case "textarea":
            return <TextAreaField readonly={props.readonly}
                                  defaultValue={props.defaultValue}
                                  placeholder={props.placeholder}
                                  isValid={props.isValid}
                                  hasError={props.hasError}
                                  isRequired={props.isRequired}
                                  onChange={(event) => onChange(event.target.value)}
                                  register={props.register}
                                  validationRegex={props.validationRegex}
                                  name={props.name}/>;
        case "dropdown":
            return <SelectField readonly={props.readonly}
                                isSearchable={props.isSearchable}
                                defaultValue={props.defaultValue}
                                placeholder={props.placeholder}
                                isValid={props.isValid}
                                options={props.options}
                                hasError={props.hasError}
                                isRequired={props.isRequired}
                                onChange={(event) => onChange(event)}
                                register={props.register}
                                name={props.name}
                                setValue={props.setValue}/>;
        case "multiselectdropdown":
            return <MultiSelectDropdown readonly={props.readonly}
                                        defaultValue={props.defaultValue}
                                        placeholder={props.placeholder}
                                        isValid={props.isValid}
                                        options={props.options}
                                        hasError={props.hasError}
                                        isRequired={props.isRequired}
                                        onChange={(event) => onChange(event)}
                                        register={props.register}
                                        name={props.name}
                                        getOptions={props.getOptions}
                                        setValue={props.setValue}/>;
        case "multiselectsuborganisation":
            return <MultiSelectSuborganisation readonly={props.readonly}
                                               defaultValue={props.defaultValue}
                                               placeholder={props.placeholder}
                                               isValid={props.isValid}
                                               options={props.options}
                                               hasError={props.hasError}
                                               isRequired={props.isRequired}
                                               onChange={(event) => onChange(event)}
                                               register={props.register}
                                               name={props.name}
                                               setValue={props.setValue}/>;
        case "multiselectpublisher":
            return <MultiSelectPublisher readonly={props.readonly}
                                         defaultValue={props.defaultValue}
                                         placeholder={props.placeholder}
                                         isValid={props.isValid}
                                         options={props.options}
                                         hasError={props.hasError}
                                         isRequired={props.isRequired}
                                         onChange={(event) => onChange(event)}
                                         register={props.register}
                                         name={props.name}
                                         setValue={props.setValue}/>;

        case "discipline":
            return <DisciplineField readonly={props.readonly}
                                    defaultValue={props.defaultValue}
                                    placeholder={props.placeholder}
                                    isValid={props.isValid}
                                    hasError={props.hasError}
                                    isRequired={props.isRequired}
                                    onChange={(event) => onChange(event)}
                                    register={props.register}
                                    name={props.name}
                                    setValue={props.setValue}/>;
        case "lectorate":
            return <LectorateField readonly={props.readonly}
                                   defaultValue={props.defaultValue}
                                   placeholder={props.placeholder}
                                   isValid={props.isValid}
                                   hasError={props.hasError}
                                   isRequired={props.isRequired}
                                   onChange={(event) => onChange(event)}
                                   register={props.register}
                                   name={props.name}
                                   setValue={props.setValue}/>;
        case "institute":
            return <OrganisationDropdownField readonly={props.readonly}
                                              defaultValue={props.defaultValue}
                                              placeholder={props.placeholder}
                                              isSearchable={props.isSearchable}
                                              isValid={props.isValid}
                                              hasError={props.hasError}
                                              isRequired={props.isRequired}
                                              onChange={(event) => onChange(event)}
                                              register={props.register}
                                              name={props.name}
                                              setValue={props.setValue}/>;
        case "checkbox":
            return <CheckBoxField readonly={props.readonly}
                                  defaultValue={props.defaultValue}
                                  isValid={props.isValid}
                                  isRequired={props.isRequired}
                                  options={props.options}
                                  hasError={props.hasError}
                                  onChange={(event) => onChange(event)}
                                  register={props.register}
                                  name={props.name}/>;
        case "singledatepicker":
            return <SingleDatePickerField readonly={props.readonly}
                                          defaultValue={props.defaultValue}
                                          placeholder={props.placeholder}
                                          isRequired={props.isRequired}
                                          isValid={props.isValid}
                                          options={props.options}
                                          hasError={props.hasError}
                                          onChange={(event) => onChange(event)}
                                          register={props.register}
                                          name={props.name}
                                          setValue={props.setValue}/>;
        case "switch":
            return <SwitchField readonly={props.readonly}
                                defaultValue={props.defaultValue}
                                isValid={props.isValid}
                                hasError={props.hasError}
                                placeholder={props.placeholder}
                                onChange={(event) => onChange(event)}
                                setValue={props.setValue}
                                register={props.register}
                                name={props.name}/>;
        case "datetime":
            return <DateTimeField readonly={props.readonly}
                                  defaultValue={props.defaultValue}
                                  isValid={props.isValid}
                                  options={props.options}
                                  hasError={props.hasError}
                                  onChange={(event) => onChange(event)}/>;
        case "personinvolved":
            return <RepoItemField readonly={props.readonly}
                                  defaultValue={props.defaultValue}
                                  isValid={props.isValid}
                                  hasFileDrop={false}
                                  isRequired={props.isRequired}
                                  options={props.options}
                                  hasError={props.hasError}
                                  onChange={(event) => onChange(event)}
                                  register={props.register}
                                  name={props.name}
                                  itemToComponent={getPersonInvolvedRepoItemRow}
                                  setValue={props.setValue}
                                  addText={t('personinvolved_field.add')}
                                  formReducerState={props.formReducerState}/>;
        case "repoitemlink":
            return <RepoItemField readonly={props.readonly}
                                  defaultValue={props.defaultValue}
                                  isValid={props.isValid}
                                  hasFileDrop={false}
                                  isRequired={props.isRequired}
                                  options={props.options}
                                  hasError={props.hasError}
                                  onChange={(event) => onChange(event)}
                                  register={props.register}
                                  name={props.name}
                                  itemToComponent={getRelatedLinkRepoItemRow}
                                  setValue={props.setValue}
                                  addText={t("repoitem.addlink")}
                                  formReducerState={props.formReducerState}/>;
        case "repoitemlearningobject":
            return <RepoItemField readonly={props.readonly}
                                  defaultValue={props.defaultValue}
                                  isValid={props.isValid}
                                  hasFileDrop={false}
                                  isRequired={props.isRequired}
                                  options={props.options}
                                  hasError={props.hasError}
                                  onChange={(event) => onChange(event)}
                                  register={props.register}
                                  name={props.name}
                                  itemToComponent={getRelatedLearningObjectRepoItemRow}
                                  setValue={props.setValue}
                                  addText={t("repoitem.addlearningobject")}
                                  formReducerState={props.formReducerState}/>;
        case "attachment":
            return <RepoItemField readonly={props.readonly}
                                  defaultValue={props.defaultValue}
                                  isValid={props.isValid}
                                  hasFileDrop={true}
                                  isRequired={props.isRequired}
                                  options={props.options}
                                  hasError={props.hasError}
                                  onChange={(event) => onChange(event)}
                                  register={props.register}
                                  name={props.name}
                                  itemToComponent={getRelatedAttachmentRepoItemRow}
                                  setValue={props.setValue}
                                  formReducerState={props.formReducerState}/>;
        case "repoitems":
            return <RepoItemField readonly={true}
                                  defaultValue={props.defaultValue}
                                  isValid={props.isValid}
                                  hasFileDrop={false}
                                  isRequired={props.isRequired}
                                  options={props.options}
                                  hasError={props.hasError}
                                  onChange={(event) => onChange(event)}
                                  register={props.register}
                                  name={props.name}
                                  itemToComponent={getRelatedLearningObjectRepoItemRow}
                                  setValue={props.setValue}
                                  formReducerState={props.formReducerState}/>;
        case "file":
            return <FileField readonly={props.readonly}
                              isValid={props.isValid}
                              options={props.options}
                              hasError={props.hasError}
                              onChange={onChange}
                              isRequired={props.isRequired}
                              file={props.file}
                              defaultValue={props.defaultValue}
                              register={props.register}
                              name={props.name}
                              setValue={props.setValue}/>;
        case "person":
            return <PersonField readonly={props.readonly}
                                isValid={props.isValid}
                                options={props.options}
                                hasError={props.hasError}
                                onChange={onChange}
                                isRequired={props.isRequired}
                                formReducerState={props.formReducerState}
                                defaultValue={props.defaultValue}
                                person={props.person}
                                register={props.register}
                                name={props.name}
                                setValue={props.setValue}/>;
        case "repoitem":
            return <SingleRepoItemField readonly={props.readonly}
                                        isValid={props.isValid}
                                        options={props.options}
                                        hasError={props.hasError}
                                        onChange={onChange}
                                        isRequired={props.isRequired}
                                        formReducerState={props.formReducerState}
                                        defaultValue={props.defaultValue}
                                        relatedRepoItem={props.relatedRepoItem}
                                        register={props.register}
                                        name={props.name}
                                        setValue={props.setValue}/>;
        case "tree-multiselect":
            return <TreeMultiSelectField readonly={props.readonly}
                                         defaultValue={props.defaultValue}
                                         isValid={props.isValid}
                                         hasFileDrop={false}
                                         isRequired={props.isRequired}
                                         addText={t('vocabulary_field.add')}
                                         options={props.options}
                                         hasError={props.hasError}
                                         onChange={(event) => onChange(event)}
                                         register={props.register}
                                         name={props.name}
                                         itemToComponent={getValueRow}
                                         setValue={props.setValue}
                                         formReducerState={props.formReducerState}/>;
        default:
            return null;
    }
}

export function Required(props) {
    return <i className={"fas fa-star-of-life field-required"}/>
}

export function Label(props) {
    return <label className="field-label">{props.text}</label>;
}

export function Tooltip(props) {
    const popup = useRef();
    const [isTooltipShown, setIsTooltipShown] = useState(false);
    const [isOutsideWindow, setIsOutsideWindow] = useState(null);

    useEffect(() => {
        if(isTooltipShown && !isOutsideWindow){
            setIsOutsideWindow(isOutsideViewport(popup))
        }
    }, [isTooltipShown])

    const tooltipIcon = <i className={"fas fa-info"}/>;

    return <div className="field-tooltip" onClick={() => setIsTooltipShown(!isTooltipShown)}>
        {isTooltipShown ?
            <div>
                <OutsideAlerter onClick={() => setIsTooltipShown(!isTooltipShown)} children={tooltipIcon}/>

                <div className={`tooltip-popup ${isOutsideWindow ? "left" : "right"}`}>
                    <div className={"tooltip-content"} ref={popup}>
                        {props.text}
                    </div>
                    <FontAwesomeIcon className={"arrow-left"} icon={isOutsideWindow ? faCaretRight : faCaretLeft}/>
                </div>
            </div>
            :
            tooltipIcon}
    </div>;

    function isOutsideViewport(element) {
            const rect = element.current.getBoundingClientRect()
            return (
                rect.right >= (window.innerWidth || document.documentElement.clientWidth)
            )
    }
}


/**
 * Hook that alerts clicks outside of the passed ref
 */
function useOutsideAlerter(callback, ref) {
    useEffect(() => {
        /**
         * Alert if clicked on outside of element
         */
        function handleClickOutside(event) {
            if (ref.current && !ref.current.contains(event.target)) {
                callback();
            }
        }

        // Bind the event listener
        document.addEventListener("mousedown", handleClickOutside);
        return () => {
            // Unbind the event listener on clean up
            document.removeEventListener("mousedown", handleClickOutside);
        };
    }, [ref]);
}

/**
 * Component that alerts if you click outside of it
 */
export default function OutsideAlerter(props) {
    const wrapperRef = useRef(null);
    useOutsideAlerter(props.onClick, wrapperRef);

    return <div ref={wrapperRef}>{props.children}</div>;
}


function DateTimeField(props) {
    let valueToShow = null;
    if (props.field.value && !isNaN(props.field.value)) {
        valueToShow = props.field.value
    } else if (!props.field.readonly) {
        valueToShow = Date.now();
    }
    let date = valueToShow ? new Date(valueToShow) : new Date(); //start with timezone as offset

    //Set time to current time as default if no value is set
    useEffect(() => {
        if (!props.field.readonly && !props.field.value) {
            let changeEvent = {
                target: {
                    value: null
                }
            };
            changeEvent.target.value = date.getTime();
            props.onChange(changeEvent);
        }
    }, []);

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const dateString = `${year}-${month}-${day}`;

    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    const timeString = `${hours}:${minutes}`;

    const dateDOMRef = useRef();
    const timeDOMRef = useRef();

    return <div className="datetime-holder">
        <input type="date"
               ref={dateDOMRef}
               className={"form-field-input datetime" + (props.field.readonly ? ' disabled' : '')}
               disabled={props.field.readonly}
               defaultValue={valueToShow ? dateString : null}
               onChange={(e) => {
                   if (!props.field.readonly) {
                       dateStringToFieldValueBeforeOnChange(e);
                   }
               }}/>

        <input type="time"
               ref={timeDOMRef}
               className={"form-field-input datetime" + (props.field.readonly ? ' disabled' : '')}
               disabled={props.field.readonly}
               defaultValue={valueToShow ? timeString : null}
               onChange={(e) => {
                   if (!props.field.readonly) {
                       dateStringToFieldValueBeforeOnChange(e);
                   }
               }}/>
    </div>;

    function dateStringToFieldValueBeforeOnChange(event) {
        const inputDate = dateDOMRef.current.value;
        const inputTime = timeDOMRef.current.value;

        let inputDateTime = null;
        if (inputTime && inputDate) {
            inputDateTime = (new Date(Date.parse(inputDate + 'T' + inputTime + ':00'))).getTime();
        }

        let changeEvent = {
            target: {
                value: inputDateTime
            }
        };
        props.onChange(changeEvent);
    }
}

function getRelatedLinkRepoItemRow(valuePart, onItemAction, readonly) {
    return <div className='surf-sortable-row'>
        {!readonly && <DragHandle/>}
        <div className="related-repoitem-title">
            <a href={valuePart.summary.url} target={"_blank"}>{valuePart.summary.title}</a>
        </div>
        <div className="related-repoitem-subtitle">
            {valuePart.summary.subtitle}
        </div>
        {!readonly && <i className="fas fa-edit edit-icon" onClick={
            () => {
                onItemAction({
                        type: "edit",
                        value: valuePart.id
                    }
                )
            }
        }/>}
        {!readonly && <i className="fas fa-trash delete-icon" onClick={
            () => {
                onItemAction({
                        type: "delete",
                        value: valuePart.id
                    }
                )
            }
        }/>}
    </div>
}


function getRelatedAttachmentRepoItemRow(valuePart, onItemAction, readonly) {
    return <div className='surf-sortable-row'>
        {!readonly && <DragHandle/>}
        <div className="related-repoitem-title">
            <a href={valuePart.summary.url} target={"_blank"}
               onClick={(e) => {
                   e.preventDefault()
                   Api.downloadFileWithAccessToken(valuePart.summary.url, valuePart.summary.title, false)
               }}>{valuePart.summary.title}</a>
        </div>
        <div className="related-repoitem-subtitle">
            {valuePart.summary.subtitle}
        </div>
        {!readonly && <i className="fas fa-edit edit-icon" onClick={
            () => {
                onItemAction({
                        type: "edit",
                        value: valuePart.id
                    }
                )
            }
        }/>}
        {!readonly && <i className="fas fa-trash delete-icon" onClick={
            () => {
                onItemAction({
                        type: "delete",
                        value: valuePart.id
                    }
                )
            }
        }/>}
    </div>
}

function getValueRow(valuePart, onDelete, readonly) {
    return <div className='surf-sortable-row'>
        <div className="related-repoitem-title">
            {valuePart.coalescedLabelNL}
        </div>
        {!readonly && <i className="fas fa-trash delete-icon" onClick={
            () => {
                onDelete({
                        type: "delete",
                        value: valuePart.value
                    }
                )
            }
        }/>}
    </div>
}

function getRelatedLearningObjectRepoItemRow(valuePart, onItemAction, readonly) {
    var titleElement = valuePart.summary.title

    if (valuePart.summary.repoItem !== undefined && valuePart.summary.repoItem.permissions.canView) {
        //try to link through another repoitem, e.g. RepoItemLearningObject
        titleElement =
            <a href={'../publications/' + valuePart.summary.repoItem.id} target={"_blank"}>{valuePart.summary.title}</a>
    } else if (valuePart.summary.permissions.canView && valuePart.summary.repoItem === undefined) {
        //try to link to another repoitem, e.g. LearningObject
        titleElement =
            <a href={'../publications/' + valuePart.summary.id} target={"_blank"}>{valuePart.summary.title}</a>
    }

    return <div className='surf-sortable-row'>
        {!readonly && <DragHandle/>}
        <div className="related-repoitem-title">
            {titleElement}
        </div>
        {!readonly && <i className="fas fa-edit edit-icon" onClick={
            () => {
                onItemAction({
                        type: "edit",
                        value: valuePart.id
                    }
                )
            }
        }/>}
        {!readonly && <i className="fas fa-trash delete-icon" onClick={
            () => {
                onItemAction({
                        type: "delete",
                        value: valuePart.id
                    }
                )
            }
        }/>}
    </div>
}

function getPersonInvolvedRepoItemRow(valuePart, onItemAction, readonly, t) {
    var titleElement = valuePart.summary.title
    if (valuePart.summary.person !== undefined && valuePart.summary.person.permissions.canView) {
        titleElement =
            <a href={'../profile/' + valuePart.summary.person.id} target={"_blank"}>{valuePart.summary.title}</a>
    }
    return <div className='surf-sortable-row'>
        {!readonly && <DragHandle/>}
        <div className="related-repoitem-title">
            {titleElement}
        </div>
        <div className="related-repoitem-subtitle person-involved-subtitle">
            {
                t('language.current_code') === 'nl' ? valuePart.summary.subtitleNL : valuePart.summary.subtitleEN
            }
        </div>
        {!readonly && <i className="fas fa-edit edit-icon" onClick={
            () => {
                onItemAction({
                        type: "edit",
                        value: valuePart.id
                    }
                )
            }
        }/>}
        {!readonly && <i className="fas fa-trash delete-icon" onClick={() => {
            VerificationPopup.show(t('verification.author.delete.title'), t('verification.author.delete.subtitle'), () => {
                onItemAction({
                    type: "delete",
                    value: valuePart.id
                })
            })
        }}/>}
    </div>
}