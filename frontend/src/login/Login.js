import React, {useEffect, useState} from "react";
import AppStorage, {StorageKey, useAppStorageState} from "../util/AppStorage";
import ApiRequests from "../util/api/ApiRequests";
import Toaster from "../util/toaster/Toaster";
import EmptyPage, {GlobalEmptyPageMethods} from "../components/emptypage/EmptyPage";
import Background from "../resources/images/surf-background.png";
import './login.scss'
import {faInfoCircle} from "@fortawesome/free-solid-svg-icons";
import IconButton from "../components/buttons/iconbutton/IconButton";
import {useTranslation} from "react-i18next";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import {useHistory} from "react-router-dom";
import Api from "../util/api/Api";
import {useGlobalState} from "../util/GlobalState";
import styled from "styled-components";

function Login(props) {
    const [, setUser] = useAppStorageState(StorageKey.USER);
    const [, setUserInstitute] = useAppStorageState(StorageKey.USER_INSTITUTE);
    const [isTopMenuVisible, setTopMenuVisible] = useGlobalState('isTopMenuVisible', true);
    const [method, setMethod] = useGlobalState('method', null);
    const history = useHistory()
    const {t} = useTranslation();
    const loginDisabled = (process.env.REACT_APP_DISABLE_LOGIN === "true");

    useEffect(() => {
        setTopMenuVisible(false)
    },[])

    const openIdCallbackUrl = process.env.REACT_APP_LOGIN_REDIRECT_URL;
    const openIdLoginUrl = process.env.REACT_APP_LOGIN_URL;
    const sramLoginUrl = process.env.REACT_APP_LOGIN_URL_SRAM;
    const enableSram = process.env.REACT_APP_ENABLE_SRAM
    const enableConext = process.env.REACT_APP_ENABLE_CONEXT
    const currentUrl = new URL(window.location);
    const state = currentUrl.searchParams.get("state");
    let redirect = currentUrl.searchParams.get("redirect");
    let isRedirectPrivate = currentUrl.searchParams.get("redirectPrivate");
    const openIdCode = currentUrl.searchParams.get("code");

    const methodParam = new URLSearchParams(window.location.search).get('method');

    const shouldShowSram = (methodParam === 'sram' || methodParam === 'all') || (!methodParam && enableSram === "true");
    const shouldShowConext = (methodParam === 'conext' || methodParam === 'all') || (!methodParam && enableConext === "true");

    function saveAsState(link, isPrivate) {
        if (!link) {
            AppStorage.remove(StorageKey.STATE_REDIRECT)
        } else {
            AppStorage.set(StorageKey.STATE_REDIRECT, link)
        }
        if (!isPrivate) {
            AppStorage.remove(StorageKey.STATE_NEEDS_ACCESSTOKEN)
        } else {
            AppStorage.set(StorageKey.STATE_NEEDS_ACCESSTOKEN, isPrivate)
        }
    }

    function loadState() {
        redirect = AppStorage.get(StorageKey.STATE_REDIRECT)
        isRedirectPrivate = AppStorage.get(StorageKey.STATE_NEEDS_ACCESSTOKEN)
    }

    useEffect(() => {
        if (state) {
            loadState()
        } else {
            saveAsState(redirect, isRedirectPrivate)
        }
        if (openIdCode !== undefined && openIdCode !== null && openIdCode !== '') {
            loginUser()
        }
    }, []);

    const content = (
        <div className={"login-page"}>
            <div className={"login-wrapper"}>
                <div className={"login-container"}>
                    <div className={"logo-wrapper"}>
                        <img alt="Surf" id="login-logo" src={require('../resources/images/surf-sharekit-logo.png')}/>
                    </div>
                    <h3>
                        { loginDisabled ?
                            <>Under construction</>
                            :
                            <>{t('onboarding.login.welcome')}</>
                        }
                    </h3>
                    <div className={"welcome-text"}>
                        { loginDisabled ?
                            <>SURFsharekit is currently undergoing scheduled maintenance. We will be back online tomorrow. Thank you for your patience.</>
                                :
                            <>{t('onboarding.login.subtitle')}</>
                        }
                    </div>
                    <LoginButtonContainer className={"button-container"}>
                        <LoginButtons>
                            {shouldShowSram && <ButtonText className={"login-button"}
                                                           text={t('onboarding.login.login_sram')}
                                                           buttonType={"callToAction"}
                                                           onClick={() => {
                                                               getOpenIdCode("sram")
                                                           }}/>}
                            {shouldShowConext && <ButtonText className={"login-button"}
                                                               text={t('onboarding.login.login_conext')}
                                                               buttonType={"callToAction"}
                                                               onClick={() => {
                                                                   getOpenIdCode("conext")
                                                               }}/>}

                        </LoginButtons>
                        <IconButton className={"icon-button-login-info"}
                                    text={t('onboarding.login.help')}
                                    icon={faInfoCircle}
                                    onClick={() => {
                                        openInfoPage()
                                    }}/>
                    </LoginButtonContainer>
                </div>
            </div>
        </div>
    )

    return (
        <EmptyPage id="login"
                   history={props.history}
                   content={content}
                   style={{backgroundImage: `url('` + Background + `')`}}
        />
    )

    function openInfoPage() {
        window.open("https://servicedesk.surf.nl/wiki/display/WIKI/SURFsharekit", '_blank', 'noopener,noreferrer')
    }

    function getOpenIdCode(type) {

        localStorage.setItem('loginType', type);

        // When user presses on "Login met SRAM"
        if (type === "sram") {
            const scopes = ["openid", "profile", "email", "eduperson_entitlement", "eduperson_scoped_affiliation", "schac_home_organization", "voperson_external_affiliation"];
            //Get OpenIdCode from Surf Conext, we need this code to log in the user into our system
            window.location.href = sramLoginUrl + "&redirect_uri=" + openIdCallbackUrl + "&scope=" + scopes.join("%20") + "&state=1";

        } else { // When user press on "Login met Conext"
            // Get OpenIdCode from Surf Conext, we need this code to log in the user into our system
            window.location.href = openIdLoginUrl + "&redirect_uri=" + openIdCallbackUrl + "&scope=openid&state=1";
            sessionStorage.setItem('loginType', type);
        }

    }

    function loginUser() {
        GlobalEmptyPageMethods.setFullScreenLoading(true)
        setUser(null);
        setUserInstitute(null);

        const loginType = localStorage.getItem('loginType') || '';

        //Code is available, trying to authenticate with api
        fetch(process.env.REACT_APP_API_URL + `login/${loginType}?code=` + openIdCode + '&redirect_uri=' + openIdCallbackUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            mode: 'cors'
        }).then(response => {
            if (!response.ok) {
                GlobalEmptyPageMethods.setFullScreenLoading(false)
                throw response
            }
            return response.json()  //we only get here if there is no error
        }).then(json => {
            const newUser = {
                id: json.id,
                accessToken: json.token,
                name: json.name
            };

            AppStorage.set(StorageKey.USER, newUser);

            ApiRequests.getExtendedPersonInformation(newUser, history,
                (data) => {
                    GlobalEmptyPageMethods.setFullScreenLoading(false)
                    AppStorage.set(StorageKey.USER_ROLES, data.groups.map(group => group.roleCode).filter(r => !!r && r !== 'null'));

                    if (data.hasFinishedOnboarding && data.hasFinishedOnboarding === 1) {
                        if (redirect) {
                            if (isRedirectPrivate) {
                                Api.downloadFileWithAccessTokenAndPopup(redirect, null)
                                saveAsState(undefined, false)
                                props.history.push('dashboard');
                            } else {
                                props.history.push(redirect);
                            }
                        } else {
                            props.history.push('dashboard');
                        }
                    } else {
                        props.history.push({
                            pathname: '/onboarding',
                            state: {memberData: data}
                        });
                    }
                }, () => {
                    GlobalEmptyPageMethods.setFullScreenLoading(false)
                    Toaster.showDefaultRequestError()
                }
            );
        }).catch(error => {
            GlobalEmptyPageMethods.setFullScreenLoading(false)
        });
    }
}

const LoginButtonContainer = styled.div`
    width: 100%; 
    display: flex;
    flex-direction: column;
    align-items: center;
    vertical-align: center;
    gap: 15px;
`
const LoginButtons = styled.div`
    display: flex;
    flex-direction: row;
    gap: 15px;
`

export default Login;