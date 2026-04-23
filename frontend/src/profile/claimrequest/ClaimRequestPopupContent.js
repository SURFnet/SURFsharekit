import React, {useEffect, useRef, useState} from "react";
import SURFButton from "../../styled-components/buttons/SURFButton";
import {greyLight, majorelle, majorelleLight, spaceCadet, spaceCadetLight, SURFShapeRight} from "../../Mixins";
import {ThemedH3, ThemedH4, ThemedP} from "../../Elements";
import styled from "styled-components";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {useTranslation} from "react-i18next";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import {useForm} from "react-hook-form";
import '../../components/field/formfield.scss'
import {FormField, Tooltip} from "../../components/field/FormField";
import SwalClaimRequestPopup from "sweetalert2";
import AppStorage, {StorageKey, useAppStorageState} from "../../util/AppStorage";
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import LoadingIndicator from "../../components/loadingindicator/LoadingIndicator";
import {GlobalPageMethods} from "../../components/page/Page";
import {useNavigate} from "react-router-dom";
import i18n from "../../i18n";
import {useNavigation} from "../../providers/NavigationProvider";

function ClaimRequestPopupContent(props) {
    const [profileData, setProfileData] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const personInstitutes = props.personInstitutes
    const person = props.person
    const [canClaim, setCanClaim] = useState(false);
    const user = AppStorage.get(StorageKey.USER)
    const {register, handleSubmit, formState: { errors}, setValue} = useForm();
    const {t} = useTranslation()
    const navigate = useNavigation()

    useEffect(() => {
        getProfile()
    }, [])

    useEffect(() => {
        if (profileData) {
            setCanClaim(RealList().length > 0)
        }
    }, [profileData])

    return (
        <ClaimRequestPopupContentRoot>
            <CloseButtonContainer onClick={() => SwalClaimRequestPopup.close()}>
                <FontAwesomeIcon icon={faTimes}/>
            </CloseButtonContainer>

            {isLoading ? (
                <LoadingIndicator/>
            ) : (
                <>
                    <Header>
                        <Title>{t("profile.claim_popup.title")}</Title>
                    </Header>

                    <FormTitle>{t("profile.claim_popup.subtitle")}</FormTitle>
                    {!canClaim && <ClaimDisclaimerText>{t("profile.claim_popup.cant_merge")}</ClaimDisclaimerText>}
                    <Form>
                        <FormFieldContainer className={"form-row flex-row form-field-container"}>
                            <FormField key={"rootInstitutes"}
                                       classAddition={''}
                                       type={"dropdown"}
                                       options={profileData ? RealList() : []}
                                       label={t('profile.organisation')}
                                       isRequired={true}
                                       readonly={profileData && RealList().length === 0}
                                       error={errors["institute"]}
                                       name={"rootInstitutes"}
                                       register={register}
                                       setValue={setValue}
                                       defaultValue={profileData && profileData.length === 1 ? RealList() : []}
                                       tooltip={t("profile.claim_popup_tooltip")}
                            />
                        </FormFieldContainer>
                    </Form>
                </>
            )}


            <Footer>
                <SURFButton
                    text={t("action.cancel")}
                    backgroundColor={spaceCadet}
                    highlightColor={spaceCadetLight}
                    width={"170px"}
                    onClick={() => {
                        SwalClaimRequestPopup.close()
                    }}
                />

                <SURFButton
                    text={t("action.confirm")}
                    backgroundColor={majorelle}
                    highlightColor={majorelleLight}
                    disabled={!canClaim}
                    width={"170px"}
                    onClick={() => {
                        handleSubmit((formData) => postClaimPersonAction(formData))()
                    }}
                />
            </Footer>
        </ClaimRequestPopupContentRoot>
    )

    function postClaimPersonAction(formData) {
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const postData = {
            "data": {
                "type": "claim",
                "attributes": {
                    "instituteId": formData["rootInstitutes"],
                    "personId": person
                }
            }
        };

        Api.post('claims', () => {}, onSuccess, onLocalFailure, onServerFailure, config, postData);

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showToaster({type: "info", message: i18n.t("profile.claim_popup.success")})
            SwalClaimRequestPopup.close()
        }

        const errorCallback = (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            console.log(error);
        };

        function onServerFailure(error) {
            errorCallback(error);
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            errorCallback(error);
        }
    }

    function getProfile() {
        setIsLoading(true)
        const config = {
            params: {
                'include': "groups.partOf,config",
                'fields[groups]': 'partOf,title,labelNL,labelEN',
                'fields[institutes]': 'title,level,type',
            }
        };

        Api.jsonApiGet('persons/' + user.id, onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            if (response.data.isRemoved) {
                navigate('/removed', {replace: true});
            } else {
                setProfileData(response.data);
                console.log(response.data)
                setIsLoading(false);
            }
        }

        const errorCallback = (error) => {
            console.log(error);
            Toaster.showServerError(error)
        };

        function onServerFailure(error) {
            errorCallback(error);
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            } else if (error && error.response && (error.response.status === 404 || error.response.status === 400)) { //The object to access does not exist
                navigate('/notfound', {replace: true});
            } else if (error && error.response && (error.response.status === 423)) { //The object is inaccesible
                navigate('/removed', {replace:true});
            } else if (error && error.response && error.response.status === 403) { //The object to access is forbidden to view
                navigate('/forbidden', {replace: true});
            } else { //The object to access does not exist
                navigate('/unauthorized', {replace: true});
            }
        }

        function onLocalFailure(error) {
            errorCallback(error);
        }
    }

    function RootInstituteFieldValues() {
        return profileData.rootInstitutesSummary.map((option) => {
                return {
                    value: option.id,
                    labelEN: option.title,
                    labelNL: option.title
                }
            }
        );
    }

    function UserRootInstitueValues() {
        return personInstitutes.map((option) => {
                return {
                    value: option.id,
                    labelEN: option.title,
                    labelNL: option.title
                }
            }
        );
    }

    function RealList() {
        return RootInstituteFieldValues().filter(({ value: id1 }) => !UserRootInstitueValues().some(({ value: id2 }) => id2 === id1));
    }
}

const ClaimRequestPopupContentRoot = styled.div`    
    padding: 15px;
    position: relative;
`;

const Header = styled.div`
    padding: 0 0 10px 0;
    margin-top: 10px;
    display: flex;
    align-items: center;
`;

const Form = styled.form`
    flex-grow: 1;
`;

const FormTitle = styled(ThemedH4)`
`;

const Title = styled(ThemedH3)`
    margin: 0;
    flex-grow: 1;
`;

const Footer = styled.div`
    width: 100%;
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    padding-top: 26px;
    margin-top: 25px;
`;

const CloseButtonContainer = styled.div`
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    
    &:hover {
        opacity: 0.7;
    }
`;

const ClaimDisclaimerText = styled.p `
    color: red;
    font-size: 12px;
`;

const FormFieldContainer = styled.div `
`;

export default ClaimRequestPopupContent;
