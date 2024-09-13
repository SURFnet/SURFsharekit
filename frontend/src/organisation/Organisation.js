import React, {useEffect, useState} from "react"
import './organisation.scss'
import Page from "../components/page/Page";
import {useTranslation} from "react-i18next";
import {OrganisationExpandableList} from "./OrganisationExpandableList";
import HorizontalTabList from "../components/horizontaltablist/HorizontalTabList";
import ReactRollsAndRightsTable from "./ReactRollsAndRightsTable";
import PersonTable from "../components/reacttable/tables/person/PersonTable";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {Redirect, useHistory, useLocation} from "react-router-dom";
import {SwitchRowField} from "../components/field/switchrow/SwitchRowField";

function Organisation(props) {
    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);
    const [selectedIndex, setSelectedIndex] = useState(null);
    const location = useLocation()

    useEffect(() => {
        if (location.hash === "#groups"){
            setSelectedIndex(1)
        } else if (location.hash === "#users"){
            setSelectedIndex(2)
        } else {
            setSelectedIndex(0)
        }
    }, [location])

    useEffect(() => {
        if (selectedIndex === 0){
            window.location.hash = ''
            document.title = 'Organisation'
        } else if (selectedIndex === 1){
            window.location.hash = '#groups';
            document.title = 'Groups'
        } else if (selectedIndex === 2){
            window.location.hash = '#users';
            document.title = 'Users'
        }
    }, [selectedIndex])


    if (user === null) {
        return <Redirect to={'login?redirect=organisation'}/>
    }

    const content = <div>
        <div className={"title-row"}>
            <h1>{t("organisation.title")}</h1>
        </div>
        <TabContent/>
    </div>;



    function TabContent(props) {

        return <div>
            <HorizontalTabList
                defaultActiveKey={0}
                tabsTitles={[t("organisation.tab_organizational_chart"), t('organisation.tab_roles_rights'), t('organisation.tab_users')]}
                selectedIndex={selectedIndex}
                onTabClick={setSelectedIndex}/>
            {(selectedIndex === 0) && <OrganizationalChart />}
            {(selectedIndex === 1) && <RolesRightsContent />}
            {(selectedIndex === 2) && <Users />}
        </div>
    }

    function OrganizationalChart() {
        const [showInactive, setShowInactive] = useState(false);
        return <div id={"tab-notifications"} className={"tab-content-container"}>
            <h2 className={"tab-title"}>{t("organisation.tab_organizational_chart")}</h2>
            <SwitchRowField
                label={t("organisation.tab_organizational_inactive")}
                onValueChanged={setShowInactive}
            />
            <OrganisationExpandableList showInactive={showInactive}/>
        </div>
    }

    function RolesRightsContent() {
        return <div id={"tab-roles-rights"} className={"tab-content-container"}>
            <h2 className={"tab-title"}>{t("organisation.tab_roles_rights")}</h2>
            <ReactRollsAndRightsTable props={props}/>
        </div>
    }

    function Users() {
        return <div id={"tab-notifications"} className={"tab-content-container"}>
            <h2 className={"tab-title"}>{t("organisation.tab_users")}</h2>
            <PersonTable props={props} history={props.history}/>
        </div>
    }

    return <Page id="organisation"
                 history={props.history}
                 activeMenuItem={"organisation"}
                 breadcrumbs={[{
                     path: './dashboard', title: 'side_menu.dashboard'
                 }, {
                     title: 'side_menu.organisation'
                 }]}
                 showBackButton={true}
                 content={content}/>;
}

export default Organisation;