import React from 'react';
import styled from "styled-components";
import {ThemedH3, ThemedH4, ThemedP} from "../../Elements";
import SURFButton from "../../styled-components/buttons/SURFButton";
import {greyLight, majorelle, majorelleLight, spaceCadet, spaceCadetLight} from "../../Mixins";
import {FormField, Tooltip} from "../../components/field/FormField";
import {Mod11Helper} from "../../util/Mod11Helper";
import {useForm} from "react-hook-form";
import AppStorage, {StorageKey, useAppStorageState} from "../../util/AppStorage";
import PersonMergeHelper from "../../util/PersonMergeHelper";
import {useTranslation} from "react-i18next";
import {GlobalPageMethods} from "../../components/page/Page";
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import SwalMergeProfilesPopup from "sweetalert2";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {CollapsePersonMergeFooterEvent} from "../../util/events/Events";

function MergeProfilePopupContent(props) {

    const {formState, register, handleSubmit, errors, setValue, reset, trigger} = useForm();
    const [personsToMerge, setPersonsToMerge] = useAppStorageState(StorageKey.PERSONS_TO_MERGE)

    const {t} = useTranslation()

    const atLeastOneProfileWithEmail = PersonMergeHelper.getAllUniqueValuesForField(personsToMerge, "email").length > 0

    const firstNameOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "firstName")
    const surnamePrefixOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "surnamePrefix")
    const surnameOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "surname")
    const titleOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "title")
    const academicTitleOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "academicTitle")
    const initialsOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "initials")
    const emailOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "email")
    const secondaryEmailOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "secondaryEmail")
    const cityOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "city")
    const phoneOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "phone")
    const persistentIdentifierOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "persistentIdentifier")
    const positionOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "position")
    const orcidOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "orcid")
    const isniOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "isni")
    const hogeschoolIdOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "hogeschoolId")
    const linkedInUrlOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "linkedInUrl")
    const twitterUrlOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "twitterUrl")
    const researchGateUrlOptions = PersonMergeHelper.getAllOptionsForField(personsToMerge, "researchGateUrl")


    return (
        <MergeProfilePopupContentRoot>
            <CloseButtonContainer onClick={() => SwalMergeProfilesPopup.close()}>
                <FontAwesomeIcon icon={faTimes}/>
            </CloseButtonContainer>

            <Header>
                <Title>{t("profile.merge_popup.title")}</Title>
                <Subtitle>{t("profile.merge_popup.subtitle")}</Subtitle>
            </Header>

            <FormTitle>{t("profile.merge_popup.form_title")}</FormTitle>

            {personsToMerge && <Form>
                <Grid id={"name-row"}>
                    <div className={"flex-column form-field-container"}>
                        <FormField key={"firstName"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={firstNameOptions}
                                   label={t("profile.profile_first_name")}
                                   isRequired={true}
                                   readonly={firstNameOptions.length === 0}
                                   error={errors["firstName"]}
                                   name={"firstName"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].firstName}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"surnamePrefix"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={surnamePrefixOptions}
                                   label={t("profile.profile_surname_prefix")}
                                   isRequired={false}
                                   readonly={surnamePrefixOptions.length === 0}
                                   error={errors["surnamePrefix"]}
                                   name={"surnamePrefix"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].surnamePrefix}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"surname"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={surnameOptions}
                                   label={t("profile.profile_surname")}
                                   isRequired={true}
                                   readonly={surnameOptions.length === 0}
                                   error={errors["surname"]}
                                   name={"surname"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].surname}
                        />
                    </div>


                    <div className={"flex-column form-field-container"}>
                        <FormField key={"title"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={titleOptions}
                                   label={t("profile.profile_titulatuur")}
                                   isRequired={false}
                                   readonly={titleOptions.length === 0}
                                   error={errors["title"]}
                                   name={"title"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].title}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"academicTitle"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={academicTitleOptions}
                                   label={t("profile.profile_academic_title")}
                                   isRequired={false}
                                   readonly={academicTitleOptions.length === 0}
                                   error={errors["academicTitle"]}
                                   name={"academicTitle"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].academicTitle}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"initials"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={initialsOptions}
                                   label={t("profile.profile_initials")}
                                   isRequired={false}
                                   readonly={initialsOptions.length === 0}
                                   error={errors["initials"]}
                                   name={"initials"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].initials}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"email"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={emailOptions}
                                   label={t("profile.profile_email")}
                                   isRequired={atLeastOneProfileWithEmail}
                                   readonly={emailOptions.length === 0}
                                   error={errors["email"]}
                                   name={"email"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].email}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"secondaryEmail"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={secondaryEmailOptions}
                                   label={t("profile.profile_email_alt")}
                                   isRequired={false}
                                   readonly={secondaryEmailOptions.length === 0}
                                   error={errors["secondaryEmail"]}
                                   name={"secondaryEmail"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].secondaryEmail}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"phone"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={phoneOptions}
                                   label={t("profile.profile_phone")}
                                   isRequired={false}
                                   readonly={phoneOptions.length === 0}
                                   error={errors["phone"]}
                                   name={"phone"}
                                   validationRegex={"^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\\s\\./0-9]*$"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].phone}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"persistentIdentifier"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={persistentIdentifierOptions}
                                   hardHint={"info:eu-repo/dai/nl/"}
                                   label={t("profile.profile_persistent_identifier")}
                                   isRequired={false}
                                   extraValidation={Mod11Helper.mod11Validator}
                                   validationRegex={"^[0-9]{8,9}[0-9X]$"}
                                   readonly={persistentIdentifierOptions.length === 0}
                                   error={errors["persistentIdentifier"]}
                                   name={"persistentIdentifier"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].persistentIdentifier}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"position"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={positionOptions}
                                   label={t("profile.profile_function")}
                                   isRequired={true}
                                   readonly={positionOptions.length === 0}
                                   error={errors["position"]}
                                   name={"position"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].position}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"orcid"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={orcidOptions}
                                   label={t("profile.profile_orcid")}
                                   isRequired={false}
                                   extraValidation={Mod11Helper.mod11_2Validator}
                                   readonly={orcidOptions.length === 0}
                                   error={errors["orcid"]}
                                   name={"orcid"}
                                   hardHint={"http://orcid.org/"}
                                   validationRegex={"^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].orcid}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"isni"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={isniOptions}
                                   hardHint={"https://isni.org/isni/"}
                                   label={t("profile.profile_isni")}
                                   isRequired={false}
                                   extraValidation={Mod11Helper.mod11_2Validator}
                                   validationRegex={"^[0]{4}[0-9]{4}[0-9]{4}[0-9]{3}[0-9X]$"}
                                   readonly={isniOptions.length === 0}
                                   error={errors["isni"]}
                                   name={"isni"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].isni}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"hogeschoolId"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={hogeschoolIdOptions}
                                   label={t("profile.profile_hogeschool_id")}
                                   isRequired={false}
                                   readonly={hogeschoolIdOptions.length === 0}
                                   error={errors["hogeschoolId"]}
                                   name={"hogeschoolId"}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].hogeschoolId}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"linkedInUrl"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={linkedInUrlOptions}
                                   label={"LinkedIn"}
                                   readonly={linkedInUrlOptions.length === 0}
                                   error={errors["linkedInUrl"]}
                                   name={"linkedInUrl"}
                                   placeholder={t("profile.profile_linkedin_placeholder")}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].linkedInUrl}
                        />
                    </div>

                    <div className={"flex-column form-field-container"}>
                        <FormField key={"twitterUrl"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={twitterUrlOptions}
                                   label={"Twitter"}
                                   readonly={twitterUrlOptions.length === 0}
                                   error={errors["twitterUrl"]}
                                   name={"twitterUrl"}
                                   placeholder={t("profile.profile_twitter_placeholder")}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].twitterUrl}
                        />
                    </div>
                    <div className={"flex-column form-field-container"}>
                        <FormField key={"researchGateUrl"}
                                   classAddition={''}
                                   type={"dropdown"}
                                   options={researchGateUrlOptions}
                                   label={"Research gate"}
                                   readonly={researchGateUrlOptions.length === 0}
                                   error={errors["researchGateUrl"]}
                                   name={"researchGateUrl"}
                                   placeholder={t("profile.profile_research_gate_placeholder")}
                                   register={register}
                                   setValue={setValue}
                                   defaultValue={personsToMerge[0].researchGateUrl}
                        />
                    </div>
                </Grid>
            </Form>}


            <Footer>
                <SURFButton
                    text={t("action.confirm")}
                    backgroundColor={majorelle}
                    highlightColor={majorelleLight}
                    width={"170px"}
                    onClick={() => {
                        handleSubmit((formData) => postPersonMergeAction(formData))()
                    }}
                />
            </Footer>
        </MergeProfilePopupContentRoot>
    )

    function postPersonMergeAction(formData) {
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

        formData['mergePersonIds'] = PersonMergeHelper.getAllUniqueValuesForField(personsToMerge, "id")
        formData['skipEmail'] = !atLeastOneProfileWithEmail

        const postData = {
            "data": {
                "type": "personmerge",
                "attributes": formData
            }
        };

        Api.post('actions/personmerge/', () => {}, onSuccess, onLocalFailure, onServerFailure, config, postData);

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            SwalMergeProfilesPopup.close()
            const resultingPersonId = personsToMerge[0].id
            if (resultingPersonId) {
                props.history.push("/profile/" + resultingPersonId)
            } else {
                props.history.replace("/dashboard");
            }
            AppStorage.remove(StorageKey.PERSONS_TO_MERGE)
            window.dispatchEvent(new CollapsePersonMergeFooterEvent(true))
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                props.history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
            console.log(error);
        }
    }
}

const MergeProfilePopupContentRoot = styled.div`
    width: 100%;
    height: 600px;
    display: flex;
    flex-direction: column;
    text-align: left;
`;

const Header = styled.div`
    width: 100%;
    margin-bottom: 36px;
`;

const Form = styled.form`
    flex-grow: 1;
    overflow-y: scroll;
`;

const FormTitle = styled(ThemedH4)`
    margin-bottom: 25px;
`;

const Title = styled(ThemedH3)``;

const Subtitle = styled(ThemedP)``;

const Footer = styled.div`
    width: 100%;
    display: flex;
    flex-direction: row;
    justify-content: flex-end;
    align-items: center;
    padding-top: 26px;
    margin-top: 25px;
    border-top: 1px solid ${greyLight};
`;

const Grid = styled.div`
    width: 100%;
    display: grid;
    padding-right: 15px;
    grid-template-columns: 3fr 1fr 3fr;
    & div:nth-child(n+7) {
        grid-column-start: 1;
        grid-column-end: 4;
    }
`;

const CloseButtonContainer = styled.div`
    position: absolute;
    top: 0;
    right: 0;
    padding: 24px;
    cursor: pointer;
`;

export default MergeProfilePopupContent;