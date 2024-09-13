import React, {useEffect, useRef, useState} from "react";
import './formfield.scss'
import FormFieldHelper from "../../util/FormFieldHelper";
import {useTranslation} from "react-i18next";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {
    faAlignLeft,
    faCaretLeft,
    faCaretRight,
    faChevronDown,
    faChevronUp,
    faLink,
    faShareAlt,
    faTools,
    faUpload
} from "@fortawesome/free-solid-svg-icons";
import RepoItemHelper from "../../util/RepoItemHelper";
import {PersonField} from "./personfield/PersonField";
import {FileField} from "./filefield/FileField";
import styled from "styled-components";
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
import ValidationHelper from "../../util/ValidationHelper";
import {ThemedA, ThemedH3, ThemedH4, ThemedH5} from "../../Elements";
import {
    spaceCadet,
    nunitoExtraBold,
    cultured,
    greyLight,
    greyLighter,
    majorelle,
    maxNumberOfLines,
    openSans,
    roundedBackgroundPointyUpperLeft, SURFShapeLeft, SURFShapeRight
} from "../../Mixins";
import {useForm} from "react-hook-form";
import i18n from "i18next";
import {useOutsideElementClicked} from "../../util/hooks/useOutsideElementClicked";
import OpenAccessIcon from "../../resources/icons/ic-open-access.png";
import RestrictedAccessIcon from "../../resources/icons/ic-restricted-access.png";
import ClosedAccessIcon from "../../resources/icons/ic-closed-access.png";
import {Accordion} from "../Accordion";
import {CopyMetaFieldValueEvent, SetCopiedMetaField} from "../../util/events/Events";

export function Form(props) {
    const formFieldHelper = new FormFieldHelper();
    const {t} = useTranslation();
    const sections = RepoItemHelper.getSectionsFromSteps(props.repoItem)
    const [extendedSections, setExtendedSections] = useState([]);

    const allSectionsAreExtended = (extendedSections.length >= 0 && extendedSections.length < sections.length) || extendedSections.length === 0

    useEffect(() => {
        if (props.repoItem) {
            if (props.isEditing || props.isPublicationFlow) {
                extendAllSections()
            } else {
                setExtendedSections([])
            }
        }
    }, [props.isEditing, props.isPublicationFlow, props.repoItem])

    function collapseOrExtendAllSections() {
        if ((extendedSections.length >= 0 && extendedSections.length < sections.length) || extendedSections.length === 0) {
            extendAllSections()
        } else {
            setExtendedSections([])
        }
    }

    function collapseOrExtendSection(section) {
        if (extendedSections.includes(section)){
            setExtendedSections(extendedSections.filter((collapsedSection) => collapsedSection.id !== section.id))
        } else {
            setExtendedSections(collapsedSections => [...collapsedSections, section])
        }
    }

    function extendAllSections() {
        sections.forEach((section) => {
            if (!extendedSections.includes(section)){
                setExtendedSections(collapsedSections => [...collapsedSections, section])
            }
        })
    }

    function checkIfFormSectionIsActive(sectionId){
        if (props.containsHiddenSections === true){
            if (props.sectionsToShow) {
                const sectionsToShowIds = props.sectionsToShow.map((section) => {
                    return section.id
                })
                return sectionsToShowIds.includes(sectionId)
            }
            return false
        } else {
            return true
        }
    }

    function getSectionsFromStep(step){
        let sections = [];
        step.templateSections.forEach(section => {
            sections.push(section)
        })
        return sections
    }

    function isSectionHidden(section) {
        const sectionFields = section.fields
        const allFieldsNotRequired = sectionFields.every(field => !field.required);

        return !!(allFieldsNotRequired && props.showOnlyRequiredFields);
    }

    function isStepHidden(index = null) {
        let step;
        if (index !== null) {
            step = props.repoItem.steps[index];
        } else {
            step = props.repoItem.steps[props.currentlySelectedStep];
        }
        if (!step || !step.templateSections) {
            return false;
        }

        return step.templateSections.every(section => isSectionHidden(section));
    }


    return (
        <form id={`surf-form${props.formId ? "-" + props.formId : ""}`} onSubmit={props.onSubmit}>
            {!props.isPublicationFlow &&
                <FoldButton onClick={() => {
                    collapseOrExtendAllSections()
                }}>
                    <FontAwesomeIcon icon={allSectionsAreExtended ? faChevronDown : faChevronUp}/>
                    <div>{allSectionsAreExtended ? t("publication.sections.extend_all") : t("publication.sections.collapse_all")}</div>
                </FoldButton>
            }
            <FormSectionsContainer>
                {
                    props.repoItem.steps.map((step, i ) => {
                        return (
                            <Step $isPublicationFlow={props.isPublicationFlow} $isHidden={isStepHidden(i)} key={i}>
                                {!props.isPublicationFlow && !isStepHidden(i) && <StepTitle>{t("language.current_code") === 'nl' ? step.subtitleNL : step.subtitleEN}</StepTitle>}
                                <StepContainer key={i}>
                                    { isStepHidden() && i === props.currentlySelectedStep && props.isPublicationFlow && <EmptyStep>{t("publication.sections.empty")}</EmptyStep>}
                                    {
                                        getSectionsFromStep(step).map((section, i) => {
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
                                                                                               hidden={fieldInRow.hidden === 1 || !fieldInRow.required && props.showOnlyRequiredFields}/>
                                                                    } else {
                                                                        fieldLabel = fieldLabel.toUpperCase();
                                                                        const debouncedQueryFunction = HelperFunctions.debounce(HelperFunctions.getGetOptionsCallForFieldKey(fieldInRow.key,
                                                                            (resultOption) => {
                                                                                return {
                                                                                    "label": t('language.current_code') === 'nl' ? resultOption.labelNL : resultOption.labelEN,
                                                                                    "labelNL": resultOption.labelNL,
                                                                                    "labelEN": resultOption.labelEN,
                                                                                    "icon": resultOption.icon,
                                                                                    "coalescedLabelNL": resultOption.coalescedLabelNL,
                                                                                    "coalescedLabelEN": resultOption.coalescedLabelEN,
                                                                                    "metafieldOptionCategory": resultOption.metafieldOptionCategory,
                                                                                    "categorySort": resultOption.categorySort,
                                                                                    "value": resultOption.id
                                                                                }
                                                                            }));

                                                                        const fieldType = formFieldHelper.getFieldType(fieldInRow.fieldType)
                                                                        const fieldAnswer = formFieldHelper.getFieldAnswer(props.repoItem, fieldInRow)
                                                                        return <FormField key={fieldInRow.key}
                                                                                          hideField={(fieldType === 'repoitems') && !fieldAnswer}
                                                                                          getOptions={debouncedQueryFunction}
                                                                                          classAddition={fieldInRow.isSmallField ? 'small' : ''}
                                                                                          type={formFieldHelper.getFieldType(fieldInRow.fieldType)}
                                                                                          retainOrder={fieldInRow.retainOrder}
                                                                                          onValueChanged={(changedValue) => {
                                                                                              props.onValueChanged(fieldInRow, changedValue)
                                                                                          }}
                                                                                          label={fieldLabel}
                                                                                          isRequired={fieldInRow.required}
                                                                                          options={formFieldHelper.getFieldOptions(fieldInRow)}
                                                                                          defaultValue={fieldAnswer}
                                                                                          tooltip={t('language.current_code') === 'nl' ? fieldInRow.infoTextNL : fieldInRow.infoTextEN}
                                                                                          isReplicatable={fieldInRow.replicatable}
                                                                                          file={props.file}
                                                                                          person={props.person}
                                                                                          error={props.errors[fieldInRow.key]}
                                                                                          name={fieldInRow.key}
                                                                                          register={props.register}
                                                                                          setValue={props.setValue}
                                                                                          readonly={props.readonly || fieldInRow.readOnly === 1 || fieldInRow.readOnly === true}
                                                                                          hidden={fieldInRow.hidden === 1 || !fieldInRow.required && props.showOnlyRequiredFields}
                                                                                          repoItem={props.repoItem}
                                                                                          relatedRepoItem={props.relatedRepoItem}
                                                                                          validationRegex={fieldInRow.validationRegex}
                                                                                          formReducerState={props.formReducerState}
                                                                                          attributeKey={fieldInRow.attributeKey}/>
                                                                    }
                                                                })
                                                            }
                                                        </div>);
                                                }

                                                return (
                                                    <Accordion
                                                        isVisible={checkIfFormSectionIsActive(section.id)}
                                                        titleComponent={props.isPublicationFlow ? (
                                                            <SectionTitleH3>{t('language.current_code') === 'nl' ? section.titleNL : section.titleEN}</SectionTitleH3>
                                                        ) : (
                                                            <SectionTitleH5>{t('language.current_code') === 'nl' ? section.titleNL : section.titleEN}</SectionTitleH5>
                                                        )}
                                                        subtitle={t('language.current_code') === 'nl' ? (section.subtitleNL &&`\xa0\xa0-\xa0\xa0\xa0\xa0${section.subtitleNL}`) : (section.subtitleEN && `\xa0\xa0-\xa0\xa0\xa0\xa0${section.subtitleEN}`)}
                                                        faIcon={getSectionIcon(section.icon)}
                                                        isExtended={extendedSections.includes(section)}
                                                        isHidden={isSectionHidden(section)}
                                                        key={section.id} id={section.id}
                                                        onChange={() => collapseOrExtendSection(section)}
                                                    >
                                                        <FieldRowDisplay>
                                                            {fieldRows}
                                                        </FieldRowDisplay>
                                                    </Accordion>
                                                )
                                            }
                                        )
                                    }
                                </StepContainer>
                            </Step>
                        )
                    })
                }
            </FormSectionsContainer>
        </form>
    )

    function getSectionIcon(iconString){
        let icon;

        if (iconString) {
            switch (iconString.toUpperCase()) {
                case 'UPLOAD': return faUpload;
                case 'LINK': return faLink;
                case 'SHARE': return faShareAlt;
                case 'TOOLS': return faTools;
                default: return faAlignLeft;
            }
        }

        return null
    }
}

export function IndependentForm(props) {
    /*
        This component is an exact copy of Form(). The only difference is that the form in this component manages it's own state
        while the Form() component expects the useForm() handles to be passed to it as props.
        The usage of the Form() component caused problems when we tried to make use of multiple forms on one page,
        because useForm() should only be used once in a single component.
    */
    const {register, handleSubmit, errors, setValue, getValues, trigger} = useForm();
    const formFieldHelper = new FormFieldHelper();
    const {t} = useTranslation();
    const sections = RepoItemHelper.getSectionsFromSteps(props.repoItem)
    const [formState, setFormState] = useState()

    useEffect(() => {
        window.addEventListener("CopyMetaFieldValueEvent", handleCopyMetaFieldValue);
        return () => window.removeEventListener("CopyMetaFieldValueEvent", handleCopyMetaFieldValue);
    }, []);

    function handleCopyMetaFieldValue(event) {
        if (event.data) {
            const data = event.data
            setValue(data.key, data.value)

            const prev = Object.assign({}, formState ?? {});
            prev[data.key] = {
                field: findSectionById(data.key),
                state: data.value
            }

            setFormState(prev);

            window.dispatchEvent(new SetCopiedMetaField(data.key, data.value))
        }
    }

    const setFieldValue = (name, value, config) => {
        setValue(name, value, config)

        const prev = Object.assign({}, formState ?? {});

        prev[name] = {
            field: findSectionById(name),
            state: value
        }

        setFormState(prev);
    }

    const findSectionById = (id) => {
        const sectionFields = sections.map(section => section.fields).flat();

        return sectionFields.find(sectionField => sectionField.key === id)
    }

    function hideMultiSelectInstituteField(type) {
        if (type === "multiselectinstitute"){
            if (formState) {
                const accessRightState = Object.values(formState).find(state => state.field.attributeKey === 'AccessRight');
                if (accessRightState && accessRightState.state) {
                    const selectedOption = accessRightState.field.options.find(o => o.key === accessRightState.state);
                    if (selectedOption) {
                        return (selectedOption.value !== "restrictedaccess")
                    } else {
                        return true
                    }
                }
            }
            return true
        }
    }

    const getFieldDefaultValue = (repoItem, fieldInRow) => {

        // sets file title automatically if new file is uploaded
        if (repoItem.repoType === 'RepoItemRepoItemFile' && props.file !== null && props.file instanceof File) {
            if (fieldInRow.fieldType.toLowerCase() === 'text' && fieldInRow.attributeKey.toLowerCase() === 'title') {
                return props.file.name.substring(0, props.file.name.lastIndexOf('.'));
            }
        }

        return formFieldHelper.getFieldAnswer(repoItem, fieldInRow)
    }

    return <form
        id={`surf-form${props.formId ? "-" + props.formId : ""}`}
        onSubmit={handleSubmit((formData) => {
            props.onSubmit(formData)
        }, props.onSubmitError)}
    >
        <FormSectionsContainer>
            {
                sections.map((section, i) => {
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
                                                                   setValue={setValue}
                                                                   label={fieldLabel}
                                                                   description={fieldDescription}
                                                                   defaultValue={getFieldDefaultValue(props.repoItem, fieldInRow)}
                                                                   name={fieldInRow.key}
                                                                   register={register}
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
                                                        "icon": resultOption.icon,
                                                        "coalescedLabelNL": resultOption.coalescedLabelNL,
                                                        "coalescedLabelEN": resultOption.coalescedLabelEN,
                                                        "metafieldOptionCategory": resultOption.metafieldOptionCategory,
                                                        "categorySort": resultOption.categorySort,
                                                        "value": resultOption.id
                                                    }
                                                }));
                                            return <FormField key={fieldInRow.key}
                                                              getOptions={debouncedQueryFunction}
                                                              classAddition={fieldInRow.isSmallField ? 'small' : ''}
                                                              type={formFieldHelper.getFieldType(fieldInRow.fieldType)}
                                                              retainOrder={fieldInRow.retainOrder}
                                                              onValueChanged={(changedValue) => {
                                                                  props.onValueChanged(fieldInRow, changedValue)
                                                              }}
                                                              label={fieldLabel}
                                                              isRequired={fieldInRow.required}
                                                              options={formFieldHelper.getFieldOptions(fieldInRow)}
                                                              defaultValue={getFieldDefaultValue(props.repoItem, fieldInRow)}
                                                              tooltip={t('language.current_code') === 'nl' ? fieldInRow.infoTextNL : fieldInRow.infoTextEN}
                                                              file={props.file}
                                                              person={props.person}
                                                              error={errors[fieldInRow.key]}
                                                              name={fieldInRow.key}
                                                              attributeKey={fieldInRow.attributeKey}
                                                              isReplicatable={fieldInRow.replicatable}
                                                              register={register}
                                                              setValue={setFieldValue}
                                                              readonly={props.readonly || fieldInRow.readOnly === 1 || fieldInRow.readOnly === true}
                                                              hidden={fieldInRow.hidden === 1 || hideMultiSelectInstituteField(formFieldHelper.getFieldType(fieldInRow.fieldType)) === true}
                                                              repoItem={props.repoItem}
                                                              relatedRepoItem={props.relatedRepoItem}
                                                              validationRegex={fieldInRow.validationRegex}
                                                              formReducerState={props.formReducerState}
                                                              getValues={getValues}
                                                              formState={formState}
                                                              index={props.index}
                                                              repoItemCount={props.repoItemCount}
                                            />
                                        }
                                    })
                                }
                            </div>);
                        }

                        return (
                            <FormSection isActive={true} key={section.id} >
                                {
                                    props.showSectionHeaders && <FormSectionHeader>
                                        <SectionColumn>
                                            <SectionRow>
                                                <SectionHeader>{t('language.current_code') === 'nl' ? section.titleNL : section.titleEN}</SectionHeader>
                                            </SectionRow>
                                            <SectionSubtitle>{t('language.current_code') === 'nl' ? section.subtitleNL : section.subtitleEN}</SectionSubtitle>
                                        </SectionColumn>
                                    </FormSectionHeader>
                                }
                                {fieldRows}
                                {section === sections[sections.length - 1] && props.submitButton}
                            </FormSection>
                        )
                    }
                )
            }
            {
                props.extraContent
            }
        </FormSectionsContainer>
    </form>
}

export function FormField(props) {
    const {t} = useTranslation();
    const useGreyBackground = props.type === "tree-multiselect"

    return <div className={"form-field " + props.classAddition + ((props.hidden) ? " hidden" : "")}>
        <div className="form-row">
            <div className={"required-indicator" + ((props.isRequired && !props.readonly) ? "" : " hidden")}>
                {
                    !props.hideRequired && <Required />
                }
            </div>
            <div className={`form-column ${useGreyBackground && 'grey-background'}`}>
                { (props.hideField) ? null :
                    <>
                        { props.label && <Label text={props.label.toUpperCase()} hardHint={props.hardHint} />}
                        { props.prefixElement }
                        <div className={`form-row ${props.inputHidden && "gone"} ${props.attributeKey && props.attributeKey.toLowerCase()}`}>
                            <InputField
                                type={props.type}
                                hardHint={props.hardHint}
                                readonly={props.readonly}
                                retainOrder={props.retainOrder}
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
                                attributeKey={props.attributeKey}
                                setValue={props.setValue}
                                formReducerState={props.formReducerState}
                                inputRef={props.inputRef}
                                formState={props.formState}/>
                            <FlexContainer>
                                { props.tooltip && <Tooltip text={props.tooltip}/> }
                                { props.isReplicatable && props.index === 0 && props.repoItemCount > 1 ?
                                    <i className="fas fa-sm fa-copy copy-icon pointer"
                                       onClick={() => window.dispatchEvent(new CopyMetaFieldValueEvent(props.name, props.getValues(props.name)))}
                                    />
                                    :
                                    null
                                }
                            </FlexContainer>
                        </div>
                        <div className={"field-error " + (props.error ? '' : 'hidden')}>{props.error ? errorToLabel(props.error) : 'No error'}</div>
                    </>
                }
            </div>
        </div>
    </div>
}

export function errorToLabel(error) {
    switch (error.type) {
        case 'required':
            return i18n.t('error_message.field_required');
        default:
            return i18n.t('error_message.field_invalid');
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
                               hideInputField={props.hideInputField}
                               defaultValue={props.defaultValue}
                               placeholder={props.placeholder}
                               isValid={props.isValid}
                               isRequired={props.isRequired}
                               hasError={props.hasError}
                               onChange={(event) => onChange(event.target.value)}
                               register={props.register}
                               name={props.name}
                               formReducerState={props.formReducerState}
                               formState={props.formState}/>;
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
                             inputRef={props.inputRef}
                               formState={props.formState}/>;
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
                              inputRef={props.inputRef}
                               formState={props.formState}/>;
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
                                inputRef={props.inputRef}
                               formState={props.formState}/>;
        case "dropdowntag":
            return <MultiSelectDropdown readonly={props.readonly}
                                        defaultValue={props.defaultValue}
                                        placeholder={props.placeholder}
                                        allowCustomOption={true}
                                        isValid={props.isValid}
                                        options={props.options}
                                        hasError={props.hasError}
                                        isRequired={props.isRequired}
                                        onChange={(event) => onChange(event)}
                                        register={props.register}
                                        name={props.name}
                                        getOptions={props.getOptions}
                                        setValue={props.setValue}
                                        delimiters={[',',';']}
                               formState={props.formState}/>;
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
                             name={props.name}
                               formState={props.formState}/>;
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
                                  name={props.name}
                               formState={props.formState}/>;
        case "dropdown":
            return <SelectField readonly={props.readonly}
                                isSearchable={props.isSearchable}
                                defaultValue={props.defaultValue}
                                placeholder={props.placeholder}
                                isValid={props.isValid}
                                options={props.options}
                                retainOrder={props.retainOrder}
                                hasError={props.hasError}
                                isRequired={props.isRequired}
                                isReplicatable={props.isReplicatable}
                                onChange={(event) => onChange(event)}
                                register={props.register}
                                name={props.name}
                                setValue={props.setValue}
                                attributeKey={props.attributeKey}
                               formState={props.formState}/>;
        case "rightofusedropdown":
            return <SelectField readonly={props.readonly}
                                isSearchable={props.isSearchable}
                                defaultValue={props.defaultValue}
                                placeholder={props.placeholder}
                                isValid={props.isValid}
                                options={props.options}
                                retainOrder={props.retainOrder}
                                hasError={props.hasError}
                                isRequired={props.isRequired}
                                onChange={(event) => onChange(event)}
                                register={props.register}
                                name={props.name}
                                type={props.type}
                                setValue={props.setValue}
                                attributeKey={props.attributeKey}
                                formState={props.formState}/>;
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
                                        setValue={props.setValue}
                               formState={props.formState}/>;
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
                                               setValue={props.setValue}
                               formState={props.formState}/>;

        case "multiselectsuborganisationswitch":
            return <MultiSelectSuborganisation readonly={props.readonly}
                                               defaultValue={props.defaultValue}
                                               placeholder={props.placeholder}
                                               isValid={props.isValid}
                                               showInactiveSwitch={true}
                                               options={props.options}
                                               hasError={props.hasError}
                                               isRequired={props.isRequired}
                                               onChange={(event) => onChange(event)}
                                               register={props.register}
                                               name={props.name}
                                               setValue={props.setValue}
                               formState={props.formState}/>;
        case "multiselectpublisherswitch":
            return <MultiSelectPublisher readonly={props.readonly}
                                         defaultValue={props.defaultValue}
                                         placeholder={props.placeholder}
                                         isValid={props.isValid}
                                         showInactiveSwitch={true}
                                         options={props.options}
                                         hasError={props.hasError}
                                         isRequired={props.isRequired}
                                         onChange={(event) => onChange(event)}
                                         register={props.register}
                                         name={props.name}
                                         setValue={props.setValue}
                                         formState={props.formState}/>;
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
                                         setValue={props.setValue}
                                         formState={props.formState}/>;
        case "multiselectinstitute":
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
                                         setValue={props.setValue}
                                         formState={props.formState}
                                         attributeKey={props.attributeKey}/>;

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
                                    setValue={props.setValue}
                               formState={props.formState}/>;
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
                                   setValue={props.setValue}
                               formState={props.formState}/>;
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
                                              setValue={props.setValue}
                               formState={props.formState}/>;
        case "checkbox":
            return <CheckBoxField readonly={props.readonly}
                                  defaultValue={props.defaultValue}
                                  isValid={props.isValid}
                                  isRequired={props.isRequired}
                                  options={props.options}
                                  hasError={props.hasError}
                                  onChange={(event) => onChange(event)}
                                  register={props.register}
                                  name={props.name}
                               formState={props.formState}/>;
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
                                          setValue={props.setValue}
                                          attributeKey={props.attributeKey}
                               formState={props.formState}/>;
        case "switch":
            return <SwitchField readonly={props.readonly}
                                defaultValue={props.defaultValue}
                                isValid={props.isValid}
                                hasError={props.hasError}
                                placeholder={props.placeholder}
                                onChange={(event) => onChange(event)}
                                setValue={props.setValue}
                                register={props.register}
                                name={props.name}
                               formState={props.formState}/>;
        case "datetime":
            return <DateTimeField readonly={props.readonly}
                                  defaultValue={props.defaultValue}
                                  isValid={props.isValid}
                                  options={props.options}
                                  hasError={props.hasError}
                                  onChange={(event) => onChange(event)}
                               formState={props.formState}/>;
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
                                  showEmptyState={true}
                                  addText={t('personinvolved_field.add')}
                                  emptyText={t('personinvolved_field.empty')}
                                  formReducerState={props.formReducerState}
                               formState={props.formState}/>;
        case "repoitemresearchobject":
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
                                  itemToComponent={getRelatedResearchObjectRepoItemRow}
                                  setValue={props.setValue}
                                  showEmptyState={true}
                                  addText={t('repoitemresearchobject_field.add')}
                                  emptyText={t('repoitemresearchobject_field.empty')}
                                  formReducerState={props.formReducerState}
                               formState={props.formState}/>;
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
                                  showEmptyState={true}
                                  addText={t("link_field.add")}
                                  emptyText={t('link_field.empty')}
                                  formReducerState={props.formReducerState}
                               formState={props.formState}/>;
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
                                  showEmptyState={true}
                                  addText={t("learningobject_field.add")}
                                  emptyText={t('learningobject_field.empty')}
                                  formReducerState={props.formReducerState}
                               formState={props.formState}/>;
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
                                  showEmptyState={true}
                                  emptyText={t('attachment_field.empty')}
                                  formReducerState={props.formReducerState}
                                  formState={props.formState} />;
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
                                  formReducerState={props.formReducerState}
                               formState={props.formState}/>;
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
                              setValue={props.setValue}
                               formState={props.formState}/>;
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
                                setValue={props.setValue}
                               formState={props.formState}/>;
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
                                        setValue={props.setValue}
                               formState={props.formState}/>;
        case "tree-multiselect":
            return <TreeMultiSelectField readonly={props.readonly}
                                         defaultValue={props.defaultValue}
                                         isValid={props.isValid}
                                         hasFileDrop={false}
                                         isRequired={props.isRequired}
                                         addText={t('vocabulary_field.add')}
                                         options={props.options}
                                         retainOrder={props.retainOrder}
                                         hasError={props.hasError}
                                         onChange={(event) => onChange(event)}
                                         register={props.register}
                                         name={props.name}
                                         itemToComponent={getValueRow}
                                         setValue={props.setValue}
                                         showEmptyState={true}
                                         emptyText={t('treemultiselect_field.empty')}
                                         formReducerState={props.formReducerState}
                               formState={props.formState}/>;
        default:
            return null;
    }
}

export function Required(props) {
    return <RequiredIcon isEmailField={props.isEmailField} className={"fas fa-star-of-life field-required"}/>
}

export function Label(props) {
    return <label className="field-label">{props.text}</label>;
}

export function Tooltip(props) {
    const popup = useRef();
    const [isTooltipShown, setIsTooltipShown] = useState(false);
    const [isOutsideWindow, setIsOutsideWindow] = useState(null);
    useOutsideElementClicked(() => setIsTooltipShown(false), popup);

    useEffect(() => {
        if (isTooltipShown && !isOutsideWindow) {
            setIsOutsideWindow(isOutsideViewport(popup))
        }
    }, [isTooltipShown])

    const getPosition = () => {
        return props.position ?? 'left'
    }

    if (props.element) {
        return <div className={'element-tooltip'}
                    onMouseEnter={() => {
                        setIsTooltipShown(true)
                    }}
                    onMouseLeave={() => {
                        setIsTooltipShown(false)
                    }}
        >
            { props.element }
            {isTooltipShown && <div className={`tooltip-wrapper ${getPosition()}`} style={{ width: props.width ?? "128px" }} ref={popup}>
                {props.contentElement ? props.contentElement : <div dangerouslySetInnerHTML={{__html: props.text}} />}
                <FontAwesomeIcon className={"tooltip-arrow"} style={{fontSize: "20px"}} icon={getPosition() === 'left' ? faCaretRight : faCaretLeft}/>
            </div>}
        </div>
    }

    return <div className="field-tooltip"
                onMouseEnter={() => {
                    setIsTooltipShown(true)
                }}
                onMouseLeave={() => {
                    setIsTooltipShown(false)
                }}
    >
        <div className={"info-icon-wrapper"}>
            <i className={"fas fa-info"}/>
        </div>
        {isTooltipShown &&
        <div>
            <div className={`tooltip-popup ${(isOutsideWindow || props.forceLeft) ? "left" : "right"}`}>
                <div className={"tooltip-content"} ref={popup} dangerouslySetInnerHTML={{__html: getText()}}>
                </div>
                <FontAwesomeIcon className={"arrow-left"} icon={(isOutsideWindow || props.forceLeft) ? faCaretRight : faCaretLeft}/>
            </div>
        </div>
        }
    </div>;

    function getText() {
        const arrayOfWords = props.text ? props.text.split(' ') : []

        let stringToReturn = ''
        arrayOfWords.forEach((word) => {
            if (ValidationHelper.isURL(word)) {
                let url = word.trim()
                if (!(url.startsWith("https://") || url.startsWith("http://"))) {
                    url = "https://" + url
                }
                stringToReturn += `<a class="tooltip-link" href="${url}" target="_blank">${word}</a> `
            } else {
                stringToReturn += (word + ' ')
            }
        })
        return `<span>${stringToReturn}</span>`
    }

    function isOutsideViewport(element) {
        const rect = element.current.getBoundingClientRect()
        return (
            rect.right >= (window.innerWidth || document.documentElement.clientWidth)
        )
    }
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

function getRelatedLinkRepoItemRow(valuePart, onItemAction, readonly, t) {
    const getAccessRightTooltipText = (valuePart) => {
        let text = `<div><div><span style="font-weight: bold;">${t('repoitem.access_right.title')}</span><span>${t(`repoitem.access_right.options.${valuePart.summary.accessRight}`, '')}</span></div>`

        if (valuePart.summary.embargoDate) {
            const embargoDate = HelperFunctions.getDateFormat(valuePart.summary.embargoDate, {
                day: "2-digit",
                month: "2-digit",
                year: "numeric"
            })

            text += `<br><div><span style="font-weight: bold;">${t('repoitem.visible_on')}</span><span>${embargoDate.day}/${embargoDate.month}/${embargoDate.year}</span></div></div>`
        }

        return text;
    }
    return <RepoItemFieldRow>
        {!readonly && <DragHandle/>}
        <SortableRow disabled={readonly}>
            {valuePart.summary.accessRight !== null &&
                <Tooltip
                    isOutsideWindow={false}
                    element={<AccessRightIcon src={resolveIcon(valuePart.summary.accessRight)}/>}
                    width={'160px'}
                    text={getAccessRightTooltipText(valuePart)}
                />
            }
            <RelatedRepoitemTitle>
                <MarkedupLink href={valuePart.summary.url}
                              target={"_blank"} enabled={readonly}>{valuePart.summary.title}</MarkedupLink>
            </RelatedRepoitemTitle>
            <RelatedRepoitemSubtitle>{valuePart.summary.subtitle}</RelatedRepoitemSubtitle>
            <RelatedRepoItemLabelContainer>
                { valuePart.summary.important && <LinkRepoItemLabel>{t('link_field.important')}</LinkRepoItemLabel> }
            </RelatedRepoItemLabelContainer>
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
                    const confirmAction = () => onItemAction({type: "delete", value: valuePart.id})
                    VerificationPopup.show(t('verification.repoItem.delete_repoitemlink.title'), t('verification.repoItem.delete_repoitemlink.subtitle'), confirmAction)
                }
            }/>}
        </SortableRow>
    </RepoItemFieldRow>
}

function getRelatedResearchObjectRepoItemRow(valuePart, onItemAction, readonly, t) {
    var titleElement = valuePart.summary.title

    if (valuePart.summary.repoItem !== undefined && valuePart.summary.repoItem.permissions.canView) {
        //try to link through another repoitem, e.g. RepoItemLearningObject
        titleElement =
            <MarkedupLink href={'/publications/' + valuePart.summary.repoItem.id}
                          target={"_blank"} enabled={readonly}>{valuePart.summary.title}</MarkedupLink>
    } else if (valuePart.summary.permissions.canView && valuePart.summary.repoItem === undefined) {
        //try to link to another repoitem, e.g. LearningObject
        titleElement =
            <MarkedupLink href={'/publications/' + valuePart.summary.id}
                          target={"_blank"} enabled={readonly}>{valuePart.summary.title}</MarkedupLink>
    }

    return <RepoItemFieldRow>
        {!readonly && <DragHandle/>}
        <SortableRow disabled={readonly}>
            <RelatedRepoitemTitle>
                {titleElement}
            </RelatedRepoitemTitle>
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
                    const confirmAction = () => onItemAction({type: "delete", value: valuePart.id})
                    VerificationPopup.show(t('verification.repoItem.delete_repoitemresearchobject.title'), "", confirmAction)
                }
            }/>}
        </SortableRow>
    </RepoItemFieldRow>
}

function resolveIcon (accessRight){
    const accessRightCapitalized = accessRight.toUpperCase();

    const iconMap = {
        'OPENACCESS': OpenAccessIcon,
        'RESTRICTEDACCESS': RestrictedAccessIcon,
        'CLOSEDACCESS': ClosedAccessIcon
    };

    if (iconMap.hasOwnProperty(accessRightCapitalized)) {
        return iconMap[accessRightCapitalized]
    }
}

function getRelatedAttachmentRepoItemRow(valuePart, onItemAction, readonly, t) {
    const getAccessRightTooltipText = (valuePart) => {
        let text = `<div><div><span style="font-weight: bold;">${t('repoitem.access_right.title')}</span><span>${t(`repoitem.access_right.options.${valuePart.summary.accessRight}`, '')}</span></div>`

        if (valuePart.summary.embargoDate) {
            const embargoDate = HelperFunctions.getDateFormat(valuePart.summary.embargoDate, {
                day: "2-digit",
                month: "2-digit",
                year: "numeric"
            })

            text += `<br><div><span style="font-weight: bold;">${t('repoitem.visible_on')}</span><span>${embargoDate.day}/${embargoDate.month}/${embargoDate.year}</span></div></div>`
        }

        return text;
    }

    return <RepoItemFieldRow>
        {!readonly && <DragHandle hasAccessRight={!!valuePart.summary.accessRight}/>}
        <SortableRow disabled={readonly}>
            {valuePart.summary.accessRight !== null &&
                <Tooltip
                    isOutsideWindow={false}
                    element={<AccessRightIcon src={resolveIcon(valuePart.summary.accessRight)}/>}
                    width={'160px'}
                    text={getAccessRightTooltipText(valuePart)}
                />
            }
            <RelatedRepoitemTitle>
                <MarkedupLink href={valuePart.summary.url} target={"_blank"} enabled={readonly}
                              onClick={(e) => {
                                  e.preventDefault()
                                  Api.downloadFileWithAccessToken(valuePart.summary.url, valuePart.summary.title, false)
                              }}>{valuePart.summary.title}</MarkedupLink>
            </RelatedRepoitemTitle>
            <RelatedRepoitemSubtitle>
                {valuePart.summary.subtitle}
            </RelatedRepoitemSubtitle>
            <RelatedRepoItemLabelContainer>
                { valuePart.summary.important && <AttachmentRepoItemLabel>{t('attachment_field.important')}</AttachmentRepoItemLabel> }
            </RelatedRepoItemLabelContainer>
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
                    const confirmAction = () => onItemAction({type: "delete", value: valuePart.id})
                    VerificationPopup.show(t('verification.repoItem.delete_repoitemrepoitemfile.title'), "", confirmAction)
                }
            }/>}
        </SortableRow>
    </RepoItemFieldRow>
}

function getValueRow(valuePart, onDelete, readonly) {
    return <SortableRow disabled={readonly}>
        <RelatedRepoitemTitle>
            {valuePart.coalescedLabelNL}
        </RelatedRepoitemTitle>
        {!readonly && <i className="fas fa-trash delete-icon" onClick={
            () => {
                onDelete({
                        type: "delete",
                        value: valuePart.value
                    }
                )
            }
        }/>}
    </SortableRow>
}

function getPersonInvolvedRepoItemRow(valuePart, onItemAction, readonly, t) {
    var titleElement = valuePart.summary.title
    if (valuePart.summary.person !== undefined && valuePart.summary.person.permissions.canView){
        titleElement = <MarkedupLink href={'/profile/' + valuePart.summary.person.id}
                                      target={"_blank"} enabled={readonly}>{valuePart.summary.title}</MarkedupLink>
    }

    console.log(valuePart);
    return <RepoItemFieldRow>
        {!readonly && <DragHandle/>}
        <SortableRow disabled={readonly}>
            <RelatedRepoitemTitle>{titleElement}</RelatedRepoitemTitle>
            <RelatedRepoItemLabelContainer>
                { valuePart.summary.subtitleNL && valuePart.summary.subtitleNL !== '' && <PersonInvolvedRepoItemLabel>{valuePart.summary.subtitleNL}</PersonInvolvedRepoItemLabel> }
                { valuePart.summary.external && <PersonInvolvedRepoItemLabel>{t('repoitem.personinvolved_field.external')}</PersonInvolvedRepoItemLabel> }
            </RelatedRepoItemLabelContainer>
            {!readonly && <i className="fas fa-edit edit-icon" onClick={
                () => {
                    onItemAction({
                            type: "edit",
                            value: valuePart.summary.id
                        }
                    )
                }
            }/>}
            {!readonly && <i className="fas fa-trash delete-icon" onClick={() => {
                const confirmAction = () => onItemAction({type: "delete", value: valuePart.summary.id})
                VerificationPopup.show(t('verification.author.delete.title'), "", confirmAction)
            }}/>}
        </SortableRow>
    </RepoItemFieldRow>
}

function getRelatedLearningObjectRepoItemRow(valuePart, onItemAction, readonly, t) {
    var titleElement = valuePart.summary.title

    if (valuePart.summary.repoItem !== undefined && valuePart.summary.repoItem.permissions.canView) {
        //try to link through another repoitem, e.g. RepoItemLearningObject
        titleElement =
            <MarkedupLink href={'../publications/' + valuePart.summary.repoItem.id}
                          target={"_blank"} enabled={readonly}>{valuePart.summary.title}</MarkedupLink>
    } else if (valuePart.summary.permissions.canView && valuePart.summary.repoItem === undefined) {
        //try to link to another repoitem, e.g. LearningObject
        titleElement =
            <MarkedupLink href={'../publications/' + valuePart.summary.id}
                          target={"_blank"} enabled={readonly}>{valuePart.summary.title}</MarkedupLink>
    }

    return <RepoItemFieldRow>
        {!readonly && <DragHandle/>}
        <SortableRow disabled={readonly}>
            <RelatedRepoitemTitle>
                {titleElement}
            </RelatedRepoitemTitle>
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
                    const confirmAction = () => onItemAction({type: "delete", value: valuePart.id})
                    VerificationPopup.show(t('verification.repoItem.delete_repoitemlearningobject.title'), "", confirmAction)
                }
            }/>}
        </SortableRow>
    </RepoItemFieldRow>
}

const RelatedRepoitemTitle = styled.div`
    flex-grow: 1;
    font-size: 12px;

    //This will happen in read only
    &:not(:first-child) {
        margin-left: 30px;
    }
`;

const MarkedupLink = styled(ThemedA)`
    color: ${props => props.enabled ? '#000000' : 'default'} !important;
    text-decoration: ${props => props.enabled ? 'underline' : 'default'} !important;
`;

const PersonInvolvedMarkedUp = styled(ThemedA)`
  color: #000000;
  text-decoration: none;
  margin-left: 12px;
`;

const RelatedRepoitemSubtitle = styled.div`
    margin-left: 20px;
    font-size: 12px;
`;

const RelatedRepoItemLabelContainer = styled.div`
    display: flex;
    flex-direction: row;
    margin-right: 12px;
    
    div { margin-left: 8px; }
`

const PersonInvolvedRepoItemLabel = styled.div`
    background: ${majorelle};
    padding: 12px 12px;
    color: white;
    font-size: 12px;
    border-radius: 2px 8px 8px 8px;
`

const AttachmentRepoItemLabel = styled.div`
    background: ${majorelle};
    padding: 12px 12px;
    color: white;
    font-size: 12px;
    border-radius: 2px 8px 8px 8px;
`
const LinkRepoItemLabel = styled.div`
    background: ${majorelle};
    padding: 12px 12px;
    color: white;
    font-size: 12px;
    border-radius: 2px 8px 8px 8px;
`
const PersonInvolvedSubtitle = styled(RelatedRepoitemSubtitle)`
    ${maxNumberOfLines(2)}
    min-width: 240px;
    max-width: 240px;
`;

const PersonInvolvedRow = styled.div`
  flex-grow: 1;
  background-color: #F8F8F8;
  border-radius: 5px;
  border: 1px solid #F3F3F3;
  height: 50px;
  padding-left: 15px;
  padding-right: 15px;

  display: flex;
  flex-direction: row;
  align-items: center;
  margin-bottom: 5px;

  ${props => !!props.disabled && `
        border: 1px solid $background-color-dark;
        background-color: transparent;
    `}

  .order-icon {
    color: $vivid-sky;
    cursor: grab;
  }

  .document-icon {
    margin-left: 33px;
  }

  .edit-icon {
    margin-left: 7px;
    font-size: 12px;
    cursor: pointer;
  }

  .delete-icon {
    margin-left: 22px;
    font-size: 12px;
    cursor: pointer;
  }
`;

const RepoItemFieldRow = styled.div`
  display: flex;
  align-items: center;
  gap: 5px;
`;


const SortableRow = styled.div`
    flex-grow: 1;
    background-color: #F8F8F8;
    border-radius: 5px;
    height: 50px;
    padding-left: 15px;
    padding-right: 15px;

    display: flex;
    flex-direction: row;
    align-items: center;
    margin-bottom: 5px;
  
    border: 1px solid ${greyLighter};
    
    ${props => !!props.disabled && `
        border: 1px solid $background-color-dark;
        background-color: transparent;
    `}

    .order-icon {
        color: $vivid-sky;
        cursor: grab;
    }

    .document-icon {
        margin-left: 33px;
    }

    .edit-icon {
        margin-left: 7px;
        font-size: 12px;
        cursor: pointer;
    }

    .delete-icon {
        margin-left: 22px;
        font-size: 12px;
        cursor: pointer;
    }
`;


const SectionTitleH5 = styled(ThemedH5)`
    flex-grow: 1;
    @media only screen and (max-width: 1250px) {
        padding-right: 0;
    }
`;

const SectionTitleH3 = styled(ThemedH3)`
    flex-grow: 1;
    @media only screen and (max-width: 1250px) {
        padding-right: 0;
    }
`;



const SectionDescription = styled.div``;

const SectionHeader = styled.div`
  display: flex;
  flex-direction: row;
  gap: 20px;
  align-items: center;
  cursor: pointer;
`;

const SectionColumn = styled.div`
    width: 100%;
    display: flex;
    flex-direction: column;
`;

const SectionRow = styled.div`
    width: 100%;
    display: flex;
    flex-direction: row;
    justify-content: space-between;
`;

const FormSectionHeader = styled.div`
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    padding-left: 10px;
    
    margin-bottom: ${props => props.isExtended ? "10px" : "0"};

    @media only screen and (max-width: 1250px) {
        flex-direction: column;
    }
`;

const FlexContainer = styled.div`
  display: flex;
  align-items: center;
  gap: 5px;
`

const SectionSubtitle = styled.div`
    ${openSans()}
    font-size: 12px;
    line-height: 16px;
    color: #2D364F;
`;

export const FormSectionsContainer = styled.div`
    flex: 1 1 auto;
`;

export const FormSection = styled.div`
    ${SURFShapeLeft};
     ${props => props.isPublicationFlow ? (
        `padding: ${props.isExtended ? '20px 15px 30px 15px' : '20px 15px 20px 15px'};`
    ) : (
        `padding: ${props.isExtended ? '20px 35px 50px 35px' : '20px 35px 20px 35px'};`
    )}
    display: ${props => props.isActive ? 'block' : 'none'};
    &:not(:first-child) {
        margin-top: 10px;
    }
`;

export const Step = styled.div`
    margin-bottom: ${props => props.$isPublicationFlow || props.$isHidden ? "0" : "40px"};
    height: ${props => props.$isHidden && '0'};
`;

export const StepTitle = styled(ThemedH4)`
    margin-bottom: ${props => !props.$isHidden && '10px'};
`;

export const StepContainer = styled.div`
    display: flex;
    flex-direction: column;
    gap: 10px;
    visibility: ${props => props.isHidden ? "collapse" : "visible"};
`;

export const FoldButton = styled.div`
  float: right;
  top: 20px;
  font-size: 12px;
  font-weight: 400;
  ${openSans};
  display: flex;
  flex-direction: row;
  gap: 5px;
  align-items: center;
  cursor: pointer; 
`;

export const FieldRowDisplay = styled.div`
    margin-top: 40px;
`;

const AccessRightIcon = styled.img`
    -webkit-user-drag: none;
    -khtml-user-drag: none;
    -moz-user-drag: none;
    -o-user-drag: none;
    user-drag: none;
    height: 16px;
    width: 16px;
`;

export const WarningMessage = styled.div`
  width: 100%;
  background: rgba(144,106,241, 0.25);
  border: 1px solid rgb(144,106,241);
  border-radius: 5px;
  display: flex;
  align-items: center;
  font-size: 12px;
  padding: 16px 20px;
  color: rgb(144,106,241);
`;

export const WarningMessageContent = styled.div`
  color: #2D364F;
`;

export const WarningTextContainer = styled.div`
  padding-left: 16px;
  display: flex;
  flex-direction: column;
  gap: 5px;

  a {
    color: rgb(144,106,241);
    text-decoration: underline;
  }
`;

export const RequiredIcon = styled.i`
    transform: ${props => props.isEmailField ? "translateY(13px)" : "translateY(0)"};
`;

export const EmptyStep = styled.div`
  ${nunitoExtraBold};
  font-size: 40px;
  color: ${spaceCadet};
  line-height: 56px;
  text-align: center;
  position: relative;
  top: 150px;
`

