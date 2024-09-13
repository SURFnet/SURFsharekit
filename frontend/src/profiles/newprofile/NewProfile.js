import React, {useEffect, useRef, useState} from "react";
import './newprofile.scss'
import {Redirect} from "react-router-dom";
import ProfileContent from "../../profile/profilecontent/ProfileContent";
import Page from "../../components/page/Page";
import {StorageKey, useAppStorageState} from "../../util/AppStorage";
import {useTranslation} from "react-i18next";
import PageHeader from "../../styled-components/PageHeader";
import MarginWrapper from "../../styled-components/MarginWrapper";
import SURFButton from "../../styled-components/buttons/SURFButton";
import {spaceCadetLight} from "../../Mixins";

function NewProfile(pageProps) {
    const [user] = useAppStorageState(StorageKey.USER);
    const {t} = useTranslation();
    const profileDataFormRef = useRef()

    useEffect(() => {
        console.log(profileDataFormRef)
    }, [profileDataFormRef.current])

    if (user === null) {
        return <Redirect to={'login?redirect=profiles/newprofile'}/>
    }

    let content = <div>
        <MarginWrapper bottom={'50px'}>
            <PageHeader
                title={t('new_profile.title')}
                button={<SURFButton
                    highlightColor={spaceCadetLight}
                    width={"130px"}
                    text={t("action.save")}
                    onClick={() => profileDataFormRef.current.click()}
                />}
            />
        </MarginWrapper>
        <ProfileContent {...pageProps} isMakingNew={true} profileDataFormRef={profileDataFormRef}/>
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