import React, {useEffect, useRef, useState} from "react";
import './profilecontent.scss'
import '../../components/field/formfield.scss'
import {useTranslation} from "react-i18next";
import AppStorage, {StorageKey, useAppStorageState} from "../../util/AppStorage";
import {useForm} from "react-hook-form";
import {FormField, Tooltip} from "../../components/field/FormField";
import ButtonText from "../../components/buttons/buttontext/ButtonText";
import {FontAwesomeIcon} from '@fortawesome/react-fontawesome'
import {faLinkedin, faResearchgate, faTwitterSquare} from '@fortawesome/free-brands-svg-icons'
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import {GlobalPageMethods} from "../../components/page/Page";
import MemberPositionOptionsHelper from "../../util/MemberPositionOptionsHelper";
import {useHistory} from "react-router-dom";
import {useDirtyNavigationCheck} from "../../util/hooks/useDirtyNavigationCheck";
import VerificationPopup from "../../verification/VerificationPopup";
import {Mod11Helper} from "../../util/Mod11Helper"

function ProfileContent(props) {
    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);
    const {formState, register, handleSubmit, errors, setValue, reset, trigger} = useForm();
    const [isEmailUnknown, setIsEmailUnknown] = useState(false);
    const {dirtyFields} = formState
    const formSubmitButton = useRef();
    const history = useHistory()

    const functionOptions = new MemberPositionOptionsHelper().getPositionOptions();

    const profileData = props.profileData ?? {}
    const makingNewProfile = props.profileData === undefined

    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            trigger();
        }
    }, [isEmailUnknown])

    useDirtyNavigationCheck(history, dirtyFields)

    let emailElement;
    if (makingNewProfile || (profileData.permissions.canEdit === true && profileData.isEmailEditable === true)) {
        emailElement = <FormField key={"email"}
                                  classAddition={''}
                                  type={"email"}
                                  label={t("profile.profile_email")}
                                  isRequired={makingNewProfile && !isEmailUnknown}
                                  readonly={false}
                                  error={errors["email"]}
                                  name={"email"}
                                  register={register}
                                  setValue={setValue}
                                  defaultValue={profileData.email}
        />
    } else {
        emailElement = <FormField key={"email"}
                                  classAddition={''}
                                  type={"email"}
                                  label={t("profile.profile_email")}
                                  isRequired={true}
                                  readonly={true}
                                  error={errors["email"]}
                                  name={"email"}
                                  defaultValue={profileData.email}
        />
    }

    return <div id={"tab-profile"} className={"tab-content-container"}>
        <h2 className={"tab-title"}>{t("profile.tab_profile")}</h2>
        <div className={"tab-content"}>
            <form id={"profile-form"} onSubmit={handleSubmit(makingNewProfile ? createProfile : saveProfile)}>
                <div className={"flex-column"}>
                    <div className={"form-columns-container flex-row"}>
                        <div className={"personal-data form-column flex-column"}>
                            <h4 className={"column-title"}>Persoonsgegevens</h4>
                            <div className={"form-row flex-row"}>
                                <div className={"form-field-container"}>
                                    <div className={"flex-column form-field-container"}>
                                        <FormField key={"title"}
                                                   classAddition={''}
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
                                    </div>
                                    <div className={"flex-column form-field-container"}>
                                        <FormField key={"academicTitle"}
                                                   classAddition={''}
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
                                    </div>
                                </div>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"initials"}
                                               classAddition={''}
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
                                </div>
                            </div>
                            <div className={"form-row flex-row"}>
                                <div className={"flex-row name-first-column"}>
                                    <div className={"flex-column form-field-container"}>
                                        <FormField key={"firstName"}
                                                   classAddition={''}
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
                                    </div>
                                    <div className={"flex-column form-field-container"}>
                                        <FormField key={"surnamePrefix"}
                                                   classAddition={''}
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
                                    </div>
                                </div>
                                <div className={"flex-column form-field-container name-second-column"}>
                                    <FormField key={"surname"}
                                               classAddition={''}
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
                                </div>
                            </div>
                            <div className={"form-row flex-row"}>
                                <div className={"flex-column form-field-container"}>
                                    {emailElement}
                                </div>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"secondaryEmail"}
                                               classAddition={''}
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
                                </div>
                            </div>
                            {makingNewProfile && <div className={"form-row flex-row"}>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"skipEmail"}
                                               type={"checkbox"}
                                               options={[{
                                                   value: 1,
                                                   labelNL: t('person.skipEmailLabel'),
                                                   labelEN: t('person.skipEmailLabel')
                                               }]}
                                               label={"\u00a0"}
                                               isRequired={false}
                                               onValueChanged={v => {
                                                   if (v.length > 0) {
                                                       setValue('email', undefined)
                                                       setIsEmailUnknown(true)
                                                   } else {
                                                       setIsEmailUnknown(false)
                                                   }
                                               }}
                                               error={errors["skipEmail"]}
                                               name={"skipEmail"}
                                               register={register}
                                               setValue={setValue}/>
                                </div>
                            </div>}
                            <div className={"form-row flex-row"}>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"city"}
                                               classAddition={''}
                                               type={"text"}
                                               label={t("profile.profile_city")}
                                               isRequired={false}
                                               readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                               error={errors["city"]}
                                               name={"city"}
                                               register={register}
                                               setValue={setValue}
                                               defaultValue={profileData.city}
                                    />
                                </div>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"phone"}
                                               classAddition={''}
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
                                </div>
                            </div>
                            <div className={"form-row flex-row"}>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"persistentIdentifier"}
                                               classAddition={''}
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
                                </div>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"position"}
                                               classAddition={''}
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
                                </div>
                            </div>
                            <div className={"form-row flex-row"}>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"orcid"}
                                               classAddition={''}
                                               type={"text"}
                                               label={t("profile.profile_orcid")}
                                               isRequired={false}
                                               extraValidation={Mod11Helper.mod11_2Validator}
                                               readonly={!makingNewProfile && profileData.permissions.canEdit !== true}
                                               error={errors["orcid"]}
                                               name={"orcid"}
                                               hardHint={"http://orcid.org/"}
                                               validationRegex={"^[0]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$"}
                                               register={register}
                                               setValue={setValue}
                                               defaultValue={profileData.orcid}
                                    />
                                    <Tooltip text={t("profile.tooltips.orcid")}/>
                                </div>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"isni"}
                                               classAddition={''}
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
                                </div>
                            </div>
                            <div className={"form-row flex-row"}>
                                <div className={"flex-column form-field-container"}>
                                    <FormField key={"hogeschoolId"}
                                               classAddition={''}
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
                                </div>
                                <div className={"flex-column form-field-container"}>
                                </div>
                            </div>
                        </div>
                        <div className={"social-media form-column flex-column"}>
                            <h4 className={"column-title flex-row"}>
                                <div>Sociale media</div>
                            </h4>
                            <div className={"form-row flex-row form-field-container"}>
                                <FontAwesomeIcon icon={faLinkedin}/>
                                <FormField key={"linkedInUrl"}
                                           classAddition={''}
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
                            </div>
                            <div className={"form-row flex-row form-field-container"}>
                                <FontAwesomeIcon icon={faTwitterSquare}/>
                                <FormField key={"twitterUrl"}
                                           classAddition={''}
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
                            </div>
                            <div className={"form-row flex-row form-field-container"}>
                                <FontAwesomeIcon icon={faResearchgate}/>
                                <FormField key={"researchGateUrl"}
                                           classAddition={''}
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
                            </div>
                            {makingNewProfile && <div className={"organisation form-column flex-column"}>
                                <br/>
                                <h4 className={"column-title"}>{t('profile.organisation')}</h4>
                                <div className={"form-row flex-row form-field-container"}>
                                    <FormField key={"institute"}
                                               classAddition={''}
                                               type={"institute"}
                                               label={t('profile.organisation')}
                                               isRequired={true}
                                               readonly={false}
                                               error={errors["institute"]}
                                               name={"institute"}
                                               register={register}
                                               setValue={setValue}
                                    />
                                </div>
                            </div>
                            }
                        </div>
                    </div>

                    <div id="form-buttons" className={"flex-row"}>
                        {!makingNewProfile && profileData.id !== user.id && profileData.permissions.canDelete && <button
                            id="delete-button"
                            disabled={true}>
                            <ButtonText
                                onClick={deleteProfile}
                                buttonType={"primary"}
                                text={t('action.delete')}/>
                        </button>}

                        {(!makingNewProfile && profileData.permissions.canEdit !== true) || <button
                            id="save-button"
                            type="submit"
                            form="profile-form"
                            ref={formSubmitButton}>

                            <ButtonText
                                buttonType={"callToAction"}
                                text={t('action.save')}/>
                        </button>}
                    </div>
                </div>
            </form>
        </div>
    </div>

    function createProfile(formData) {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        formData['skipEmail'] = true

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
                props.history.push(props.history.replace('../profile/' + responseData.id))
            } else {
                props.history.goBack()
            }
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
        }
    }

    function deleteProfileWithCallback(profileId, successCallback, errorCallback) {
        function onValidate(response) {
        }

        function onSuccess(response) {
            successCallback(response.data);
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
            errorCallback()
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
            errorCallback()
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const patchData = {
            "data": {
                "type": "person",
                "id": profileId,
                "attributes": {
                    "isRemoved": true
                }
            }
        };

        Api.patch(`persons/${profileId}`, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData);
    }

    function deleteProfile() {
        return VerificationPopup.show(t("verification.profile.delete.title"), t("verification.profile.delete.subtitle"), () => {
            deleteProfileWithCallback(profileData.id, (responseData) => {
                reset();
                GlobalPageMethods.setFullScreenLoading(false)
                props.history.goBack();
            }, () => {
                GlobalPageMethods.setFullScreenLoading(false)
                Toaster.showDefaultRequestError();
            })
        })
    }

    function saveProfile(formData) {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            },
            params: {
                "include": "groups.partOf",
                'fields[groups]': 'partOf,title,userPermissions',
                'fields[institutes]': 'title,level,type'
            }
        }

        formData['skipEmail'] = profileData.isEmailEditable

        const patchData = {
            "data": {
                "type": "person",
                "id": profileData.id,
                "attributes": formData
            }
        };

        Api.patch('persons/' + profileData.id, () => {
        }, onSuccess, onLocalFailure, onServerFailure, config, patchData);

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
            GlobalPageMethods.setFullScreenLoading(false)
            console.log(error);
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
            console.log(error);
        }
    }
}

export default ProfileContent;