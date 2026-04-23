import React, {useEffect, useRef, useState} from "react";
import './profilecontent.scss'
import '../../components/field/formfield.scss'
import {useTranslation} from "react-i18next";
import AppStorage, {StorageKey, useAppStorageState} from "../../util/AppStorage";
import {useForm} from "react-hook-form";
import {FormField, Required, Tooltip} from "../../components/field/FormField";
import {FontAwesomeIcon} from '@fortawesome/react-fontawesome'
import {faLinkedin, faResearchgate, faXTwitter} from '@fortawesome/free-brands-svg-icons'
import {ReactComponent as SocialMediaIconSvg} from '../../resources/icons/ic-social-icon.svg'
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import {GlobalPageMethods} from "../../components/page/Page";
import MemberPositionOptionsHelper from "../../util/MemberPositionOptionsHelper";
import {Mod11Helper} from "../../util/Mod11Helper"
import {Accordion} from "../../components/Accordion";
import styled from "styled-components";
import {faAlignLeft, faIdCard, faShareAlt} from "@fortawesome/free-solid-svg-icons";
import {EmailRadioButtons} from "../../components/createpersonform/CreatePersonForm";
import {useNavigation} from "../../providers/NavigationProvider";

function ProfileContent(props) {
    const profileData = props.profileData ?? {}
    const makingNewProfile = props.profileData === undefined

    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);
    const {
        register,
        handleSubmit,
        setValue,
        reset,
        trigger,
        formState: { errors, dirtyFields }
    } = useForm();
    const [isEmailUnknown, setIsEmailUnknown] = useState(false);
    const [isInstituteKnown, setIsInstituteKnown] = useState(true);
    const [personConfig, setPersonConfig] = useState(!makingNewProfile ? props.profileData.config : {});
    const formSubmitButton = useRef();
    const navigate = useNavigation();
    const functionOptions = new MemberPositionOptionsHelper().getPositionOptions();
    const [emailState, setEmailState] = useState(getEmailState)
    const disableEmailChange = emailState === 'no-permission'
    const emailKnown = (emailState === 'known')

    const hasGeneratedOrcid = profileData.orcidRegisterDate !== null

    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            trigger();
        }
    }, [emailState])

    useEffect(() => {
        register('institute', {required: makingNewProfile && isInstituteKnown})

        if (!isInstituteKnown) {
            setValue('institute', null)
        }
    }, [isInstituteKnown]);

    // useDirtyNavigationCheck(dirtyFields)

    function getEmailState() {
        if (makingNewProfile || profileData.email) {
            return 'known';
        } else if (profileData.disableEmailChange === true) {
            return 'no-permission';
        } else {
            return 'unknown';
        }
    }

    useEffect(() => {
        register('email', {required: emailKnown})

        if (!emailKnown) {
            setValue('email', null)
        }
    }, [emailState]);

    function canEdit() {
        if (profileData) {
            return profileData.permissions.canEdit === true && profileData.isEmailEditable === true && profileData.disableEmailChange === false
        }
    }

    let emailElement;
    if (makingNewProfile || canEdit()) {
        emailElement = <FormField key={"email"}
                                  type={"email"}
                                  label={t("profile.profile_email")}
                                  isRequired={makingNewProfile && emailKnown}
                                  hideRequired={emailKnown}
                                  readonly={!emailKnown}
                                  error={errors["email"]}
                                  name={"email"}
                                  prefixElement={
                                      <EmailRadioButtons className={"flex-row radio"} onChange={({target: {value}}) => {
                                          setValue('email', null)
                                          setEmailState(value)
                                      }}>
                                          <input type="radio" id={'email-known'} name={'emailKnown'} checked={emailState === 'known'} value={'known'}/>
                                          <label htmlFor={"email-known"}>{t("email_field.known")}</label>

                                          <input type="radio" id={'email-unknown'} name={'emailUnknown'} checked={emailState === 'unknown'} value={'unknown'}/>
                                          <label htmlFor={"email-unknown"}>{t("email_field.unknown")}</label>

                                          <input type="radio" id={'email-no-permission'} name={'emailNoPermission'} checked={emailState === 'no-permission'} value={'no-permission'}/>
                                          <label htmlFor={"email-no-permission"}>{t("email_field.no_permission")}</label>
                                      </EmailRadioButtons>
                                  }
                                  register={register}
                                  setValue={setValue}
                                  defaultValue={profileData.email}
        />
    } else {
        emailElement = <FormField key={"email"}
                                  type={"email"}
                                  label={t("profile.profile_email")}
                                  isRequired={emailKnown && canEdit()}
                                  hideRequired={emailKnown}
                                  readonly={ profileData.disableEmailChange ? true : (!emailKnown || (profileData.hasFinishedOnboarding ? !canEdit() : false)) }
                                  error={errors && errors["email"]}
                                  name={"email"}
                                  inputHidden={!emailKnown}
                                  prefixElement={
                                      (!profileData.hasFinishedOnboarding && !profileData.disableEmailChange) &&
                                          <EmailRadioButtons className={"flex-row radio"} onChange={({target: {value}}) => {
                                              setEmailState(value)
                                          }}>
                                              <input type="radio" id={'email-known'} name={'emailKnown'} checked={emailState === 'known'} value={'known'}/>
                                              <label htmlFor={"email-known"}>{t("email_field.known")}</label>

                                              <input type="radio" id={'email-unknown'} name={'emailUnknown'} checked={emailState === 'unknown'} value={'unknown'}/>
                                              <label htmlFor={"email-unknown"}>{t("email_field.unknown")}</label>

                                              <input type="radio" id={'email-no-permission'} name={'emailNoPermission'} checked={emailState === 'no-permission'} value={'no-permission'}/>
                                              <label htmlFor={"email-no-permission"}>{t("email_field.no_permission")}</label>
                                          </EmailRadioButtons>
                                  }
                                  register={register}
                                  setValue={setValue}
                                  defaultValue={profileData.email}
        />
    }

    return <div id={"tab-profile"} className={"tab-content-container"}>
            <form id={"profile-form"} onSubmit={handleSubmit(makingNewProfile ? createProfile : saveForm)}>
                <button ref={props.profileDataFormRef} type="submit" style={{ display: 'none' }} />
                <AccordionGroup>
                    <Accordion faIcon={faAlignLeft} title={t("profile.personal_data")}>
                        <FormGrid>
                            <FormFieldContainerMedium className={"form-field-container"}>
                                <FormField key={"title"}
                                           type={"text"}
                                           label={t("profile.profile_titulatuur")}
                                           isRequired={false}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["title"]}
                                           name={"title"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.title}
                                />
                            </FormFieldContainerMedium>

                            <FormFieldContainerSmall className={"form-field-container"}>
                                <FormField key={"academicTitle"}
                                           type={"text"}
                                           label={t("profile.profile_academic_title")}
                                           isRequired={false}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["academicTitle"]}
                                           name={"academicTitle"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.academicTitle}
                                />
                            </FormFieldContainerSmall>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                <FormField key={"initials"}
                                           type={"text"}
                                           label={t("profile.profile_initials")}
                                           isRequired={false}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["initials"]}
                                           name={"initials"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.initials}
                                />
                            </FormFieldContainerLarge>

                            <FormFieldContainerMedium className={"form-field-container"}>
                                <FormField key={"firstName"}
                                           type={"text"}
                                           label={t("profile.profile_first_name")}
                                           isRequired={true}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["firstName"]}
                                           name={"firstName"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.firstName}
                                />
                            </FormFieldContainerMedium>

                            <FormFieldContainerSmall className={"form-field-container"}>
                                <FormField key={"surnamePrefix"}
                                           type={"text"}
                                           label={t("profile.profile_surname_prefix")}
                                           isRequired={false}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["surnamePrefix"]}
                                           name={"surnamePrefix"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.surnamePrefix}
                                />
                            </FormFieldContainerSmall>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                <FormField key={"surname"}
                                           type={"text"}
                                           label={t("profile.profile_surname")}
                                           isRequired={true}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["surname"]}
                                           name={"surname"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.surname}
                                />
                            </FormFieldContainerLarge>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                {emailKnown && <Required isEmailField={true}/>}
                                {emailElement}
                                {profileData.disableEmailChange ? <Tooltip text={t("profile.tooltips.email.no_permission")}/> : null}
                            </FormFieldContainerLarge>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                <FormField key={"secondaryEmail"}
                                           type={"email"}
                                           label={t("profile.profile_email_alt")}
                                           isRequired={false}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["secondaryEmail"]}
                                           name={"secondaryEmail"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.secondaryEmail}
                                />
                            </FormFieldContainerLarge>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                <FormField key={"position"}
                                           type={"dropdown"}
                                           options={functionOptions}
                                           label={t("profile.profile_function")}
                                           isRequired={true}
                                           readonly={!makingNewProfile && (profileData.permissions.canEdit !== true || profileData.position === 'student')}
                                           error={errors["position"]}
                                           name={"position"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.position}
                                />
                            </FormFieldContainerLarge>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                <FormField key={"phone"}
                                           type={"text"}
                                           label={t("profile.profile_phone")}
                                           isRequired={false}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["phone"]}
                                           name={"phone"}
                                           validationRegex={"^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\\s\\./0-9]*$"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.phone}
                                />
                            </FormFieldContainerLarge>
                            
                            { makingNewProfile && (
                                <FormFieldContainerLarge className={"form-field-container"}>
                                    <FormField key={"institute"}
                                               type={"institute"}
                                               label={t('profile.organisation')}
                                               isRequired={isInstituteKnown}
                                               inputHidden={!isInstituteKnown}
                                               readonly={false}
                                               error={errors["institute"]}
                                               name={"institute"}
                                               register={register}
                                               setValue={setValue}
                                               prefixElement={
                                                   <PrefixRadioButtons className={'flex-row radio'} style={{paddingBottom: '6px'}} onChange={({target: {value}}) => {
                                                       setIsInstituteKnown(!!parseInt(value))
                                                   }}>
                                                       <input type='radio' id={'instituteKnown-known'} name={'instituteKnown'} checked={isInstituteKnown} value={1}/>
                                                       <label htmlFor={'instituteKnown-known'} style={{marginRight: '8px'}}>{t('person.instituteKnownLabel')}</label>
                                                       <input type='radio' id={'instituteKnown-unknown'} name={'instituteKnown'} checked={!isInstituteKnown} value={0}/>
                                                       <label htmlFor={'instituteKnown-unknown'} style={{marginRight: '8px'}}>{t('person.instituteUnknownLabel')}</label>
                                                   </PrefixRadioButtons>
                                               }
                                    />
                                </FormFieldContainerLarge>
                            )}

                            {props.userCanSeeOwnInstitutes && (
                                <FormFieldContainerFullWidth className={"form-field-container"}>
                                    <FormField
                                        key={"rootInstitutes"}
                                        type={"tag"}
                                        label={t("profile.profile_root_institutes")}
                                        isRequired={false}
                                        readonly={true}
                                        name={"rootInstitutes"}
                                        options={[]}
                                        defaultValue={getRootInstituteFieldValues()}
                                    />
                                </FormFieldContainerFullWidth>
                            )}
                        </FormGrid>
                    </Accordion>

                    <Accordion faIcon={faIdCard} title={t("profile.identifiers")}>
                        <FormGrid>
                            {/*TODO: ORCIC generated on checken hier*/}
                            <FormFieldContainerLarge className={"form-field-container"}>
                                <FormField key={"orcid"}
                                           type={"orcid"}
                                           label={t("profile.profile_orcid")}
                                           isRequired={false}
                                           extraValidation={Mod11Helper.mod11_2Validator}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["orcid"]}
                                           name={"orcid"}
                                           hardHint={"http://orcid.org/"}
                                           validationRegex={"^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.orcid}
                                           hasGeneratedOrcid={hasGeneratedOrcid}
                                />
                                {!hasGeneratedOrcid && <Tooltip text={t("profile.tooltips.orcid")}/>}
                            </FormFieldContainerLarge>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                <FormField key={"persistentIdentifier"}
                                           type={"text"}
                                           hardHint={"info:eu-repo/dai/nl/"}
                                           label={t("profile.profile_persistent_identifier")}
                                           isRequired={false}
                                           extraValidation={Mod11Helper.mod11Validator}
                                           validationRegex={"^[0-9]{8,9}[0-9X]$"}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["persistentIdentifier"]}
                                           name={"persistentIdentifier"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.persistentIdentifier}
                                />
                                <Tooltip text={t("profile.tooltips.dai")}/>
                            </FormFieldContainerLarge>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                <FormField key={"isni"}
                                           type={"text"}
                                           hardHint={"https://isni.org/isni/"}
                                           label={t("profile.profile_isni")}
                                           isRequired={false}
                                           extraValidation={Mod11Helper.mod11_2Validator}
                                           validationRegex={"^[0]{4}[0-9]{4}[0-9]{4}[0-9]{3}[0-9X]$"}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["isni"]}
                                           name={"isni"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.isni}
                                />
                                <Tooltip text={t("profile.tooltips.isni")}/>
                            </FormFieldContainerLarge>



                            <FormFieldContainerLarge className={"form-field-container"}>
                                <FormField key={"hogeschoolId"}
                                           type={"text"}
                                           label={t("profile.profile_hogeschool_id")}
                                           isRequired={false}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["hogeschoolId"]}
                                           name={"hogeschoolId"}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.hogeschoolId}
                                />
                                <Tooltip text={t("profile.tooltips.hogeschool_id")}/>
                            </FormFieldContainerLarge>
                        </FormGrid>
                    </Accordion>

                    <Accordion faIcon={faShareAlt} title={t("profile.social_media")}>
                        <FormGrid>
                            <FormFieldContainerLarge className={"form-field-container"}>
                                <SocialMediaSvgIcon />
                                <FormField key={"socialMediaUrl"}
                                           type={"text"}
                                           label={"\u00a0"}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["socialMediaUrl"]}
                                           name={"socialMediaUrl"}
                                           placeholder={t("profile.profile_social_media_placeholder")}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.socialMediaUrl}
                                />
                            </FormFieldContainerLarge>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                <SocialMediaIcon icon={faLinkedin}/>
                                <FormField key={"linkedInUrl"}
                                           type={"text"}
                                           label={"\u00a0"}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["linkedInUrl"]}
                                           name={"linkedInUrl"}
                                           placeholder={t("profile.profile_linkedin_placeholder")}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.linkedInUrl}
                                />
                            </FormFieldContainerLarge>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                <SocialMediaIcon icon={faXTwitter}/>
                                <FormField key={"twitterUrl"}
                                           type={"text"}
                                           label={"\u00a0"}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["twitterUrl"]}
                                           name={"twitterUrl"}
                                           placeholder={t("profile.profile_twitter_placeholder")}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.twitterUrl}
                                />
                            </FormFieldContainerLarge>

                            <FormFieldContainerLarge className={"form-field-container"}>
                                <SocialMediaIcon icon={faResearchgate}/>
                                <FormField key={"researchGateUrl"}
                                           type={"text"}
                                           label={"\u00a0"}
                                           readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                           error={errors["researchGateUrl"]}
                                           name={"researchGateUrl"}
                                           placeholder={t("profile.profile_research_gate_placeholder")}
                                           register={register}
                                           setValue={setValue}
                                           defaultValue={profileData.researchGateUrl}
                                />
                            </FormFieldContainerLarge>
                        </FormGrid>
                    </Accordion>

                </AccordionGroup>
            </form>
    </div>

    function createProfile(formData) {
        if (!isInstituteKnown) {
            delete formData.institute
        }

        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        formData['skipEmail'] = !emailKnown
        formData['disableEmailChange'] = disableEmailChange

        const patchData = {
            "data": {
                "type": "person",
                "attributes": formData
            }
        };

        Api.post('persons', () => {
        }, onSuccess, onLocalFailure, onServerFailure, config, patchData);

        function onSuccess(response) {
            reset(formData)
            const responseData = Api.dataFormatter.deserialize(response.data);
            GlobalPageMethods.setFullScreenLoading(false)

            if (responseData.permissions.canView) {
                navigate('../profile/' + responseData.id, {replace: true})
            } else {
                navigate(-1)
            }
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        }
    }

    function saveForm(formData) {
        saveProfile(formData, (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            console.log(error);
        })
        savePersonConfig()
    }

    function saveProfile(formData, errorCallback = () => {}) {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            },
            params: {
                "include": "groups.partOf,config",
                'fields[groups]': 'partOf,title,userPermissions,labelNL,labelEN',
                'fields[institutes]': 'title,level,type'
            }
        }

        formData['skipEmail'] = profileData.isEmailEditable
        formData['disableEmailChange'] = profileData.disableEmailChange === true ? true : disableEmailChange

        const patchData = {
            "data": {
                "type": "person",
                "id": profileData.id,
                "attributes": formData
            }
        };

        Api.patch('persons/' + profileData.id, () => {
        }, onSuccess, onLocalFailure, onServerFailure, config, patchData);

        savePersonConfig(formData)

        function onSuccess(response) {
            const responseData = Api.dataFormatter.deserialize(response.data);
            props.setProfileData(responseData);

            GlobalPageMethods.setFullScreenLoading(false)

            //If user updated own profile
            if (user.id === profileData.id) {
                if (responseData.name !== user.name) {
                    user.name = responseData.name;
                    AppStorage.set(StorageKey.USER, user);
                }
            }

            reset(formData)
        }

        function onServerFailure(error) {
            errorCallback(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            errorCallback(error)
        }
    }

    function savePersonConfig() {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            },
            params: {
                "include": "groups.partOf,config",
                'fields[groups]': 'partOf,title,userPermissions,labelNL,labelEN',
                'fields[institutes]': 'title,level,type'
            }
        }

        const patchData = {
            "data": {
                "type": "personConfig",
                "id": personConfig.id,
                "attributes": {
                    "emailNotificationsEnabled": personConfig.emailNotificationsEnabled
                }
            }
        };

        Api.patch('personConfigs/' + personConfig.id, () => {
        }, onSuccess, onLocalFailure, onServerFailure, config, patchData);

        const errorCallback = (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            console.log(error);
        }

        function onSuccess(response) {
            const responseData = Api.dataFormatter.deserialize(response.data);
            setPersonConfig(responseData);
            GlobalPageMethods.setFullScreenLoading(false)
        }

        function onServerFailure(error) {
            errorCallback(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            errorCallback(error)
        }
    }

    function getRootInstituteFieldValues() {
        const rootInstitutes = profileData.rootInstitutesSummary;

        if (rootInstitutes == null || rootInstitutes.length === 0) {
            return [{
                labelNL: 'Extern',
                labelEN: 'External',
            }]
        }


        return rootInstitutes.map((institute) => {
            return {labelNL: institute.title, labelEN: institute.title}
        })
    }
}

export default ProfileContent;

export const PrefixRadioButtons = styled.fieldset`
  padding-bottom: 6px;

  label {
    font-size: 12px;
    margin-right: 8px;
  }
  
  input  {
    height: 10px;
    width: 10px;
  }
`

const AccordionGroup = styled.div`
    display: flex;
    flex-direction: column;
    gap: 15px;
`;

const FormGrid = styled.div`
    display: grid;
    grid-column-gap: 16px;
    grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr;
    padding-right: 110px;
    margin-top: 24px;
`;

const FormFieldContainerSmall = styled.div`
    width: 100%;
    grid-column: span 1;
`;

const FormFieldContainerMedium = styled.div`
    width: 100%;
    grid-column: span 2;
`;

const FormFieldContainerLarge = styled.div`
    width: 100%;
    grid-column: span 3;
`;

const FormFieldContainerFullWidth = styled.div`
    width: 100%;
    grid-column: 1 / -1;
`;

const SocialMediaIcon = styled(FontAwesomeIcon)`
    align-self: center;
`;

const SocialMediaSvgIcon = styled(SocialMediaIconSvg)`
        align-self: center;
        width: 16px;
        height: 16px;
    `;