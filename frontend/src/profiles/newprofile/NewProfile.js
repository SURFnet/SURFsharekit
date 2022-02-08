import React from "react";
import './newprofile.scss'
import {Redirect} from "react-router-dom";
import ProfileContent from "../../profile/profilecontent/ProfileContent";
import Page from "../../components/page/Page";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";
import {useTranslation} from "react-i18next";

function NewProfile(pageProps) {
    const [user] = useAppStorageState(StorageKey.USER);
    const {t} = useTranslation();

    if (user === null) {
        return <Redirect to={'unauthorized?redirect=profiles/newprofile'}/>
    }

    let content = <div>
        <div className={"user-header"}>
            <h1>{t('new_profile.title')}</h1>
        </div>
        <ProfileContent {...pageProps}/>
    </div>;

    return <Page id="new-profile"
                 history={pageProps.history}
                 showBackButton={true}
                 breadcrumbs={[
                     {
                         path: './dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         path: './profiles',
                         title: 'profile.tab_profile'
                     },
                     {
                         path: './profiles/newprofile',
                         title: 'new_profile.title'
                     }
                 ]}
                 content={content}/>
}

export default NewProfile;