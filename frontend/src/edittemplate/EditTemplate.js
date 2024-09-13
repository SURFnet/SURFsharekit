import {useTranslation} from "react-i18next";
import './edittemplate.scss'
import Page, {GlobalPageMethods} from "../components/page/Page";
import React, {useEffect, useRef, useState} from "react";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import {TemplateMetaFieldExpandableRow} from "./TemplateMetaFieldExpandableRow";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import {FormField, FormSection, FormSectionsContainer} from "../components/field/FormField";
import {useForm} from "react-hook-form";
import RepoItemHelper from "../util/RepoItemHelper";

function EditTemplate(props) {
    const {t} = useTranslation();
    const contentRef = useRef();
    const formSubmitButton = useRef();
    const [template, setTemplate] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const {register, handleSubmit, errors, setValue} = useForm();
    let templatePatchData = null;
    // let setActiveSection = null
    let scrollSectionsContainer = null
    let scrollSections = null

    let sections = template ? getSectionsFromSteps(template) : []

    useEffect(() => {
        getTemplate()
    }, [])

/*    useEffect(() => {
        if(template) {
            window.addEventListener("scroll", handleScroll)
        }
        return () => {
            window.removeEventListener("scroll", handleScroll)
        }
    }, [template])*/

    const TemplateMetaFieldSections = (templateSectionProps) => {
        return (
            <div className={"template-meta-field-section-list"}>
                {
                    templateSectionProps.sections.map((templateSection) => {
                        return (
                            <div key={templateSection.id}
                                 className={"template-meta-field-section"}>
                                <h3>{t('language.current_code') === 'nl' ? templateSection.titleNL : templateSection.titleEN}</h3>
                                <TemplateMetaFieldList fieldList={templateSection.fields}/>
                            </div>
                        )
                    })
                }
            </div>
        )
    }

    const TemplateMetaFieldList = (fieldListProps) => {
        const fieldList = fieldListProps.fieldList;
        return (
            <div className={"template-meta-field-list"}>
                {
                    fieldList.map((metaField) => {
                        return <TemplateMetaFieldExpandableRow key={metaField.key}
                                                               template={template}
                                                               metaField={metaField}
                                                               setValue={setValue}
                                                               register={register}
                        />
                    })
                }
            </div>
        )
    }

    let content;
    if (isLoading || template === null) {
        content = <LoadingIndicator centerInPage={true}/>
    } else {
        content = <div>
            <div className={"title-row"}>
                <h1>{template.title}</h1>
                <div className={"actions-container"}>
                    {template.permissions.canEdit && <ButtonText className={"save-button"}
                                                                 text={t("action.save")}
                                                                 buttonType={"callToAction"}
                                                                 onClick={() =>
                                                                     formSubmitButton.current.click()
                                                                 }
                    />}
                </div>
            </div>
            <div className={"form-elements-container"}>
                {/*<SectionQuickLinks/>*/}
                <div className={"form"}>
                    <form key={"edit-template-form"}
                          id={"edit-template-form"}
                          onSubmit={handleSubmit(handleOnSubmitForm)}>
                        <div className={"flex-column"}>
                            <div className={"form-columns-container flex-row"}>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"templateTitle"}
                                               type={"text"}
                                               readonly={!template.permissions.canEdit}
                                               label={t("templates.template_name")}
                                               isRequired={true}
                                               error={errors["templateTitle"]}
                                               name={"templateTitle"}
                                               register={register}
                                               setValue={setValue}
                                               defaultValue={template.title}
                                    />
                                </div>
                            </div>
                            <div className={"form-columns-container flex-row"}>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"organisationLevel"}
                                               readonly={true}
                                               type={"text"}
                                               label={t("templates.template_organisation_level")}
                                               error={errors["organisationLevel"]}
                                               name={"organisationLevel"}
                                               register={register}
                                               setValue={setValue}
                                               defaultValue={template.instituteTitle}
                                    />
                                </div>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"templateType"}
                                               readonly={true}
                                               type={"text"}
                                               label={t("templates.template_type")}
                                               error={errors["templateType"]}
                                               name={"templateType"}
                                               register={register}
                                               setValue={setValue}
                                               defaultValue={RepoItemHelper.getTranslatedRepoType(template.repoType)}
                                    />
                                </div>
                            </div>
                        </div>
                        <TemplateMetaFieldSections sections={sections}/>
                        <button type="submit"
                                form="edit-template-form"
                                ref={formSubmitButton}
                                style={{display: "none"}}/>
                    </form>
                </div>
            </div>
        </div>
    }

    return <Page id="edit-template"
                 history={props.history}
                 activeMenuItem={"templates"}
                 contentRef={contentRef}
                 content={content}
                 breadcrumbs={[
                     {
                         path: '../dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         path: '../templates',
                         title: 'side_menu.templates'
                     },
                     {
                         title: template === null ? '' : template.title
                     }
                 ]}
                 showBackButton={true}/>;

    function handleOnSubmitForm(formData) {
        GlobalPageMethods.setFullScreenLoading(true)
        templatePatchData = configureTemplatePatchData(null, formData)
        patchTemplate(templatePatchData)
    }

    function getSectionsFromSteps(template) {
        let sections = [];
        if(template) {
            template.steps.forEach(step => {
                sections = step.templateSections.map(section => {return section})
            })
        }
        return sections
    }

   /* function handleScroll(event) {
        const body = document.getElementById("root")
        if (!scrollSections) {
            scrollSectionsContainer = body.querySelector("#edit-template-form .template-meta-field-section-list")
            scrollSections = body.querySelectorAll("#edit-template-form .template-meta-field-section-list > .template-meta-field-section")
        }
        const scrollBottom = event.currentTarget.pageYOffset + body.clientHeight
        const targetOffset = body.clientHeight * 0.5
        let activeSectionIndex = 0;
        for (const sectionRef of scrollSections) {
            if (scrollBottom < (sectionRef.offsetTop + targetOffset)) {
                activeSectionIndex = activeSectionIndex - 1
                break;
            }
            activeSectionIndex++;
        }
        activeSectionIndex = Math.min(activeSectionIndex, (scrollSections.length - 1))
        if(scrollBottom >= (scrollSectionsContainer.offsetTop + scrollSectionsContainer.clientHeight)) {
            activeSectionIndex = (scrollSections.length - 1)
        }
        // setActiveSection(activeSectionIndex)
    }*/

    /*function SectionQuickLinks() {
        // const [activeSectionIndex, setActiveSectionIndex] = useState(0);
        // setActiveSection = setActiveSectionIndex

        let sectionsHtml = []
        for (let i = 0; i < sections.length; i++) {
            const templateSection = sections[i]
            const activeClass = (i === activeSectionIndex) ? "active" : ""
            sectionsHtml.push(<div key={i} className={"section-quick-link"}>
                <div className={"active-indicator " + activeClass}/>
                <div className={"section-quick-link-title"}>
                    <h5 className={activeClass}>{t('language.current_code') === 'nl' ? templateSection.titleNL : templateSection.titleEN}</h5>
                </div>
            </div>)
        }

        return <div className={"section-quick-links-wrapper"}>
            <div className={"section-quick-links-container"}>
                {sectionsHtml}
            </div>
        </div>
    }*/

    function configureTemplatePatchData(metaFieldValues = null, formData = null) {
        if (!templatePatchData) {
            let fieldList = []
            sections.forEach((section) => {
                section.fields.forEach((templateMetaField) => {

                    const formSwitchValue = formData[templateMetaField.key];
                    let switchEnabled = 0;
                    if (formSwitchValue) {
                        if (Array.isArray(formSwitchValue) && formSwitchValue.length === 0) {
                            switchEnabled = 0
                        } else {
                            switchEnabled = formSwitchValue ? 1 : 0;
                        }
                    }

                    templateMetaField.titleNL = formData[`${templateMetaField.key}_titleNL`] ?? "";
                    templateMetaField.titleEN = formData[`${templateMetaField.key}_titleEN`] ?? "";
                    templateMetaField.infoTextNL = formData[`${templateMetaField.key}_infoTextNL`] ?? "";
                    templateMetaField.infoTextEN = formData[`${templateMetaField.key}_infoTextEN`] ?? "";

                    const fieldAnswerValue = {
                        "key": templateMetaField.key,
                        "titleEN": templateMetaField.titleEN,
                        "titleNL": templateMetaField.titleNL,
                        "infoTextEN": templateMetaField.infoTextEN,
                        "infoTextNL": templateMetaField.infoTextNL,
                        "descriptionEN": templateMetaField.descriptionEN,
                        "descriptionNL": templateMetaField.descriptionNL,
                        "enabled": switchEnabled,
                        "required": templateMetaField.required
                    }

                    if (!templateMetaField.locked) {
                        fieldList.push(fieldAnswerValue)
                    }
                })
            })

            templatePatchData = {
                "data": {
                    "type": "template",
                    "id": template.id,
                    "attributes": {
                        "title": formData.templateTitle,
                        "fields": fieldList
                    }
                }
            }
        }

        return templatePatchData;
    }

    function getTemplate() {
        setIsLoading(true);

        const config = {
            params: {
                'include': 'partOf'
            }
        }

        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsLoading(false);
            setTemplate(response.data)
        }

        function onLocalFailure(error) {
            setIsLoading(false);
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            setIsLoading(false);
            Toaster.showServerError(error)
        }

        Api.jsonApiGet('templates/' + props.match.params.id, onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }

    function patchTemplate(templatePatchDataTemp) {

        if (templatePatchDataTemp !== null) {


            function onValidate(response) {
            }

            function onSuccess(response) {
                GlobalPageMethods.setFullScreenLoading(false)
                Toaster.showDefaultRequestSuccess();
                const parsedTemplate = Api.dataFormatter.deserialize(response.data);
                setTemplate(parsedTemplate)
            }

            function onLocalFailure(error) {
                GlobalPageMethods.setFullScreenLoading(false)
                Toaster.showDefaultRequestSuccess()
            }

            function onServerFailure(error) {
                GlobalPageMethods.setFullScreenLoading(false)
                Toaster.showServerError(error)
            }

            const config = {
                headers: {
                    "Content-Type": "application/vnd.api+json",
                }
            }

            Api.patch('templates/' + props.match.params.id, onValidate, onSuccess, onLocalFailure, onServerFailure, config, templatePatchDataTemp);
        }
    }
}

export default EditTemplate;