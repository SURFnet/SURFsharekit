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
import Api from "../util/api/Api";
import {useGlobalState} from "../util/GlobalState";
import styled from "styled-components";
import {useNavigation} from "../providers/NavigationProvider";
import { getCurrentConfig } from "../config/environment";
import {Link} from "react-router-dom";
import {majorelle, majorelleLight, openSans, openSansBold} from "../Mixins";

function Login(props) {
    const [, setUser] = useAppStorageState(StorageKey.USER);
    const [, setUserInstitute] = useAppStorageState(StorageKey.USER_INSTITUTE);
    const [, setMemberData] = useAppStorageState(StorageKey.MEMBER_DATA);
    const [isTopMenuVisible, setTopMenuVisible] = useGlobalState('isTopMenuVisible', true);
    const [method, setMethod] = useGlobalState('method', null);
    const navigate = useNavigation()
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
                        <Info>
                            <IconButton className={"icon-button-login-info"}
                                        text={t('onboarding.login.help')}
                                        icon={faInfoCircle}
                                        onClick={() => {
                                            openInfoPage()
                                        }}/>
                            <IconButton className={"icon-button-login-info"}
                                        text={t('onboarding.login.privacy')}
                                        icon={faInfoCircle}
                                        onClick={() => {
                                            window.open(
                                                "https://servicedesk.surf.nl/wiki/spaces/WIKI/pages/248316093/Privacyverklaring",
                                                '_blank',
                                                'noopener,noreferrer'
                                            );
                                        }}/>
                        </Info>
                    </LoginButtonContainer>
                </div>
            </div>
        </div>
    )

    return (
        <EmptyPage id="login"
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
            const scopes = ["openid", "profile", "email", "eduperson_entitlement", "eduperson_scoped_affiliation", "schac_home_organization", "voperson_external_affiliation", "voperson_external_id"];
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

        const envConfig = getCurrentConfig();
        const loginType = localStorage.getItem('loginType') || '';
        const loginUrl = envConfig.api.baseURL + `login/${loginType}?code=` + openIdCode + '&redirect_uri=' + openIdCallbackUrl;
        
        console.log('Attempting login to:', loginUrl); // Debug log

        fetch(loginUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'include',
            mode: 'cors'
        }).then(async response => {
            console.log('Login response status:', response.status); // Debug log
            if (!response.ok) {
                GlobalEmptyPageMethods.setFullScreenLoading(false)
                
                // Try to parse error response body
                let errorData;
                try {
                    const responseText = await response.text();
                    // Check if response is a stringified JSON object and parse it
                    if (responseText && responseText.trim().startsWith('{')) {
                        errorData = JSON.parse(responseText);
                    }
                } catch (parseError) {
                    // If parsing fails, create generic error
                    errorData = null;
                }
                
                // Create error object with response details
                const error = new Error(`HTTP error! status: ${response.status}`);
                error.status = response.status;
                error.response = {
                    status: response.status,
                    data: errorData
                };
                
                throw error;
            }
            return response.json()
        }).then(json => {
            console.log('Login successful, user data:', json); // Debug log
            const newUser = {
                id: json.id,
                name: json.name
            };

            AppStorage.set(StorageKey.USER, newUser);

            // All subsequent API calls will automatically include the cookie
            ApiRequests.getExtendedPersonInformation(newUser, navigate,
                (data) => {
                    GlobalEmptyPageMethods.setFullScreenLoading(false)
                    AppStorage.set(StorageKey.USER_ROLES, data.groups.map(group => group.roleCode).filter(r => !!r && r !== 'null'));

                    if (data.hasFinishedOnboarding && (data.hasFinishedOnboarding === true || data.hasFinishedOnboarding === 1)) {
                        if (redirect) {
                            if (isRedirectPrivate) {
                                Api.downloadFileWithAccessTokenAndPopup(redirect, null)
                                saveAsState(undefined, false)
                                navigate('/dashboard');
                            } else {
                                navigate(redirect);
                            }
                        } else {
                            navigate('/dashboard');
                        }
                    } else {
                        setMemberData(data);
                        navigate({pathname: '/onboarding', state: {memberData: data}});
                    }
                }, (error) => {
                    GlobalEmptyPageMethods.setFullScreenLoading(false)
                    Toaster.showServerError(error)
                }
            );
        }).catch(error => {
            console.error('Login error:', error); // Debug log
            GlobalEmptyPageMethods.setFullScreenLoading(false)
            
            // More specific error handling
            if (error.name === 'TypeError' && error.message === 'Failed to fetch') {
                console.error('Network error - Is the backend running?');
                Toaster.showToaster({
                    type: "error",
                    message: "Cannot connect to the server. Please check if the backend is running."
                });
            } else if (error.status === 401) {
                // Check if we have an error message from the response body
                const errorMessage = error.response?.data?.error || error.response?.data?.message;
                Toaster.showToaster({
                    type: "error",
                    message: errorMessage || t('login.error.unauthorized')
                });
            } else {
                // For errors with technical details (like error_description with authorization codes),
                // don't show the raw technical message - use standard server error instead
                if (error.response?.data?.error_description) {
                    // Create a modified error without the technical message/error_description
                    const sanitizedError = {
                        ...error,
                        response: {
                            ...error.response,
                            data: {
                                ...error.response.data,
                                message: undefined,
                                error_description: undefined
                            }
                        }
                    };
                    Toaster.showServerError(sanitizedError);
                } else {
                    // For other errors, use standard server error handling
                    Toaster.showServerError(error);
                }
            }
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

const Info = styled.div`
    display: flex;
`

export default Login;
