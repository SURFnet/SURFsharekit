import React, {useState} from "react";
import './profiles.scss'
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import Page from "../components/page/Page";
import {Redirect} from "react-router-dom";
import {useTranslation} from "react-i18next";
import {faPlus} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import {UserPermissions} from "../util/UserPermissions";
import PersonTable from "../components/reacttable/tables/person/PersonTable";
import useDocumentTitle from "../util/useDocumentTitle";

function Profiles(props) {
    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);
    const [userPermissions] = useAppStorageState(StorageKey.USER_PERMISSIONS);
    const [searchCount, setSearchCount] = useState(0);

    useDocumentTitle('Persons')

    if (user === null) {
        return <Redirect to={'unauthorized?redirect=profiles'}/>
    }

    function onTableFiltered(itemProps) {
        setSearchCount(itemProps.count)
    }

    const content = <div>
        <div className={"title-row"}>
            <h1>{t("profiles.title")}</h1>
            <div className={"search-count"}>{searchCount}</div>
        </div>
        <div className={"actions-row"}>
            {
                UserPermissions.canCreateMember(userPermissions) &&
                <IconButtonText faIcon={faPlus}
                                buttonText={t("profiles.add_profile")}
                                onClick={() => {
                                    props.history.push("./profiles/newprofile")
                                }}/>
            }
        </div>
        <PersonTable
            props={props}
            history={props.history}
            onTableFiltered={onTableFiltered}
            hideDelete={true}
            claimIconEnabled={true}
        />
    </div>;

    return <Page id="profiles"
                 history={props.history}
                 breadcrumbs={[
                     {
                         path: './dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         path: './profiles',
                         title: 'side_menu.profiles'
                     }
                 ]}
                 showBackButton={true}
                 activeMenuItem={"profiles"}
                 content={content}/>;
}

export default Profiles;