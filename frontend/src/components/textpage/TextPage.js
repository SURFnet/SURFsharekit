import React from "react";
import Page from "../page/Page";
import CookieStatement from "../../cookiestatement/CookieStatement";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";

function TextPage(props){
    const languageLocale = useAppStorageState(StorageKey.LANGUAGE_LOCALE)
    const currentTheme = extractNameFromPathname()
    const isDutch = languageLocale === "nl"

    function extractNameFromPathname() {
        const path = window.location.pathname;
        const parts = path.split('/');

        // if you're creating new textpages, add them here aswell
        switch (parts[parts.length - 1]) {
            case 'cookies':
                return 'cookies';
            default:
                return '';
        }
    }

    // Decides which page is being used based on the currentTheme
    const content = [
        <div>
            { currentTheme === 'cookies' && <CookieStatement isDutch={isDutch}/>}
        </div>
    ]

    return <Page id={currentTheme}
                 showBackButton={true}
                 activeMenuItem={currentTheme}
                 content={content}/>;
}

export default TextPage;