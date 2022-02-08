import React, {useEffect} from "react";
import AppStorage, {StorageKey, useAppStorageState} from "../util/AppStorage";
import ApiRequests from "../util/api/ApiRequests";
import Toaster from "../util/toaster/Toaster";
import EmptyPage, {GlobalEmptyPageMethods} from "../components/emptypage/EmptyPage";
import Background from "../resources/images/surf-background.jpeg";
import './login.scss'
import {faInfoCircle} from "@fortawesome/free-solid-svg-icons";
import IconButton from "../components/buttons/iconbutton/IconButton";
import {useTranslation} from "react-i18next";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import {useHistory} from "react-router-dom";
import Api from "../util/api/Api";

function Login(props) {
    const [, setUser] = useAppStorageState(StorageKey.USER);
    const [, setUserInstitute] = useAppStorageState(StorageKey.USER_INSTITUTE);
    const history = useHistory()
    const {t} = useTranslation();

    const openIdCallbackUrl = process.env.REACT_APP_LOGIN_REDIRECT_URL;
    const openIdLoginUrl = process.env.REACT_APP_LOGIN_URL;
    const currentUrl = new URL(window.location);
    const state = currentUrl.searchParams.get("state");
    let redirect = currentUrl.searchParams.get("redirect");
    let isRedirectPrivate = currentUrl.searchParams.get("redirectPrivate");
    const openIdCode = currentUrl.searchParams.get("code");

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
                    <h3>{t('onboarding.login.welcome')}</h3>
                    <div className={"welcome-text"}>
                        {t('onboarding.login.subtitle')}
                    </div>
                    <div className={"button-container"}>
                        <ButtonText className={"login-button"}
                                    text={t('onboarding.login.login')}
                                    buttonType={"callToAction"}
                                    onClick={() => {
                                        getOpenIdCode()
                                    }}/>
                        <IconButton className={"icon-button-login-info"}
                                    text={t('onboarding.login.help')}
                                    icon={faInfoCircle}
                                    onClick={() => {
                                        openInfoPage()
                                    }}/>
                    </div>
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
        window.open("https://www.surf.nl/surfsharekit-de-online-opslagplaats-voor-het-hoger-onderwijs", '_blank', 'noopener,noreferrer')
    }

    function getOpenIdCode() {
        //Get OpenIdCode from Surf Conext, we need this code to log in the user into our system
        const loginUrl = openIdLoginUrl + "&redirect_uri=" + openIdCallbackUrl + "&scope=openid&state=1";
        window.location.href = loginUrl;
    }

    function loginUser() {
        GlobalEmptyPageMethods.setFullScreenLoading(true)
        setUser(null);
        setUserInstitute(null);

        //Code is available, trying to authenticate with api
        fetch(process.env.REACT_APP_API_URL + 'login?code=' + openIdCode + '&redirect_uri=' + openIdCallbackUrl, {
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

export default Login;