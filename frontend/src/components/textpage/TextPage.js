import {useHistory, useLocation} from "react-router-dom";
import {useTranslation} from "react-i18next";
import React, {useEffect} from "react";
import {useGlobalState} from "../../util/GlobalState";
import Page from "../page/Page";
import {ThemedH1} from "../../Elements";
import PrivacyStatement from "../../privacystatement/PrivacyStatement";
import CookieStatement from "../../cookiestatement/CookieStatement";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";

function TextPage(props){
    const [languageLocale, setLanguageLocale] = useAppStorageState(StorageKey.LANGUAGE_LOCALE);
    const history = useHistory();
    const location = useLocation()
    const currentTheme = extractNameFromPathname();
    const isDutch = languageLocale === "nl"

    function extractNameFromPathname() {
        const path = window.location.pathname;
        const parts = path.split('/');

        // if you're creating new textpages, add them here aswell
        switch (parts[parts.length - 1]) {
            case 'privacy':
                return 'privacy';
            case 'cookies':
                return 'cookies';
            default:
                return '';
        }
    }

    // Decides which page is being used based on the currentTheme
    const content = [
        <div>
            { currentTheme === 'privacy' && <PrivacyStatement isDutch={isDutch} /> }
            { currentTheme === 'cookies' && <CookieStatement isDutch={isDutch}/>}
        </div>
    ]

    return <Page id={currentTheme}
                 history={props.history}
                 showBackButton={true}
                 activeMenuItem={currentTheme}
                 content={content}/>;
}

export default TextPage;