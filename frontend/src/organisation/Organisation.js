import React, {useState} from "react"
import './organisation.scss'
import Page from "../components/page/Page";
import {useTranslation} from "react-i18next";
import {OrganisationExpandableList} from "./OrganisationExpandableList";
import HorizontalTabList from "../components/horizontaltablist/HorizontalTabList";
import ReactRollsAndRightsTable from "./ReactRollsAndRightsTable";
import PersonTable from "../components/reacttable/tables/PersonTable";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {Redirect} from "react-router-dom";

function Organisation(props) {
    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);

    if (user === null) {
        return <Redirect to={'unauthorized?redirect=organisation'}/>
    }
    const content = <div>
        <div className={"title-row"}>
            <h1>{t("organisation.title")}</h1>
        </div>
        <TabContent/>
    </div>;

    function TabContent(props) {
        const [selectedIndex, setSelectedIndex] = useState(0);

        return <div>
            <HorizontalTabList
                tabsTitles={[t("organisation.tab_organizational_chart"), t('organisation.tab_roles_rights'), t('organisation.tab_users')]}
                selectedIndex={selectedIndex} onTabClick={setSelectedIndex}/>
            {(selectedIndex === 0) && <OrganizationalChart/>}
            {(selectedIndex === 1) && <RolesRightsContent/> }
            {(selectedIndex === 2) && <Users/>}
        </div>
    }

    function OrganizationalChart() {
        return <div id={"tab-notifications"} className={"tab-content-container"}>
            <h2 className={"tab-title"}>{t("organisation.tab_organizational_chart")}</h2>
            <OrganisationExpandableList/>
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
                 breadcrumbs={[
                     {
                         path: './dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         title: 'side_menu.organisation'
                     }
                 ]}
                 showBackButton={true}
                 content={content}/>;
}

export default Organisation;