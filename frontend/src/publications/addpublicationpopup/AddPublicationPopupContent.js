import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import React, {useRef, useState} from "react";
import "./addpublicationpopup.scss"
import '../../components/field/formfield.scss'
import {useTranslation} from "react-i18next";
import {RadioGroup} from "../../components/field/radiogroup/RadioGroup";
import {
    instituteCreateRepoItemPermissions,
    instituteCreateRepoItemPermissionToRealType,
    instituteCreateRepoItemPermissionToString
} from "../Publications";
import ButtonText from "../../components/buttons/buttontext/ButtonText";
import {FormStep} from "../../components/field/relatedrepoitempopup/RelatedRepoItemContent";
import SearchRepoItemTable from "../../components/searchrepoitemtable/SearchRepoItemTable";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";

export function AddPublicationPopupContent(popupProps) {
    const institutes = popupProps.institutes
    const skipStep1 = institutes.length === 1

    const {t} = useTranslation()
    const [currentStepIndex, setCurrentStepIndex] = useState((skipStep1) ? 1 : 0);
    const [selectedPublicationType, setSelectedPublicationType] = useState(null)
    const selectedInstitute = useRef((skipStep1) ? institutes[0] : null);
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const userHasExtendedAccess = userRoles ? userRoles.find(c => c !== 'Student' && c !== 'Default Member') : false;

    return (
        <div className={"add-publication-popup-content-wrapper"}>
            <div className={"add-publication-popup-content"}>
                <div className={"close-button-container"}
                     onClick={popupProps.onCancel}>
                    <FontAwesomeIcon icon={faTimes}/>
                </div>
                <PopupContent/>
            </div>
        </div>
    )

    function PopupContent(props) {
        let indexOffset = skipStep1 ? -1 : 0;

        return <div>
            <h3 className={"popup-title"}>{t('add_publication.popup.title')}</h3>
            <div className='flex-row form-step-list'>
                {!skipStep1 && <FormStep
                    active={currentStepIndex === 0}
                    number={1 + indexOffset}
                    title={t('add_publication.popup.step1_title')}/>}
                {!skipStep1 && <div className='form-step-divider'/>}
                <FormStep
                    active={currentStepIndex === 1}
                    number={2 + indexOffset}
                    title={t('add_publication.popup.step2_title')}/>
                {userHasExtendedAccess && <div className='form-step-divider'/>}
                {userHasExtendedAccess && <FormStep
                    active={currentStepIndex === 2}
                    number={3 + indexOffset}
                    title={t('add_publication.popup.step3_title')}/>}
            </div>

            {currentStepIndex === 0 && <Step1Content/>}
            {currentStepIndex === 1 && <Step2Content/>}
            {currentStepIndex === 2 && <Step3Content/>}
        </div>

        function Step1Content(props) {
            function instituteRowClicked(institute) {
                selectedInstitute.current = institute
                setCurrentStepIndex(1)
            }

            let institutesHtml = []
            for (let i = 0; i < institutes.length; i++) {
                const instituteWrapper = institutes[i]
                institutesHtml.push(<div className={"institute-option"} onClick={() => {
                    instituteRowClicked(instituteWrapper)
                }}>
                    <div className={"status-color-indicator"}/>
                    <div className={"icon-container"}>
                        <div className="fas fa-building icon"/>
                    </div>
                    <div className={"title"}>{instituteWrapper.title}</div>
                </div>)
            }

            return <div>
                <h4 className={"type-title"}>{t('add_publication.popup.organisation_title')}</h4>

                <div className={"institute-options"}>
                    {institutesHtml}
                </div>
            </div>
        }

        function Step2Content(props) {
            const repoItemTypesToCreate = instituteCreateRepoItemPermissions(selectedInstitute.current).map((p) => {
                return {
                    "value": p,
                    "label": instituteCreateRepoItemPermissionToString(p, t)
                }
            })

            return <div>
                <h4 className={"type-title"}>{t('add_publication.popup.type_title')}</h4>

                <RadioGroup
                    name={"radio-test"}
                    readonly={false}
                    options={repoItemTypesToCreate}
                    defaultValue={selectedPublicationType}
                    onChange={(change) => {
                        setSelectedPublicationType(change)
                    }}
                />

                <div className={"button-container"}>
                    {!skipStep1 && <ButtonText text={props.buttonText ?? t('action.previous')}
                                               className={"previous-button"}
                                               onClick={() => {
                                                   setCurrentStepIndex(0)
                                               }}/>}

                    {userHasExtendedAccess && <ButtonText text={props.buttonText ?? t('add_publication.popup.use_template')}
                                buttonType={"primary"}
                                disabled={!selectedPublicationType}
                                className={"save-button"}
                                onClick={() => {
                                    setCurrentStepIndex(2)
                                }}/>}

                    <ButtonText text={props.buttonText ?? t('add_publication.popup.confirm')}
                                buttonType={"callToAction"}
                                disabled={!selectedPublicationType}
                                className={"save-button"}
                                onClick={() => {
                                    popupProps.instituteAndTypeSelected({
                                        institute: selectedInstitute.current,
                                        selectedPublicationType: instituteCreateRepoItemPermissionToRealType(selectedPublicationType)
                                    })
                                }}/>
                </div>
            </div>
        }

        function Step3Content(props) {
            const [repoItemToCopy, setRepoItemToCopy] = useState(null)

            return <div>
                <h4 className={"type-title"}>{t('add_publication.popup.template_title')}</h4>

                <SearchRepoItemTable setSelectedRepoItem={setRepoItemToCopy}
                                     hideNextButton={true}
                                     filters={
                                         {
                                             'filter[permissions.canCopy]': true,
                                             'filter[scope]': selectedInstitute.current.id,
                                             'filter[repoType]': instituteCreateRepoItemPermissionToRealType(selectedPublicationType)
                                         }
                                     }
                />

                <div className={"button-container"}>
                    <ButtonText text={props.buttonText ?? t('action.previous')}
                                className={"previous-button"}
                                onClick={() => {
                                    setCurrentStepIndex(1)
                                }}/>

                    <ButtonText text={props.buttonText ?? t('add_publication.popup.no_template')}
                                buttonType={"primary"}
                                className={"save-button"}
                                onClick={() => {
                                    popupProps.instituteAndTypeSelected({
                                        institute: selectedInstitute.current,
                                        selectedPublicationType: instituteCreateRepoItemPermissionToRealType(selectedPublicationType)
                                    })
                                }}/>

                    <ButtonText text={props.buttonText ?? t('add_publication.popup.confirm')}
                                buttonType={"callToAction"}
                                disabled={repoItemToCopy == null}
                                className={"save-button"}
                                onClick={() => {
                                    popupProps.repoItemToCopySelected(repoItemToCopy)
                                }}/>
                </div>
            </div>
        }
    }
}