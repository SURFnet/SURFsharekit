import {useTranslation} from "react-i18next";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {Navigate} from "react-router-dom";
import Page from "../components/page/Page";
import React from "react";
import {ReactTemplatesTable} from "./ReactTemplatesTable";
import useDocumentTitle from "../util/useDocumentTitle";

function Templates(props) {
    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);

    useDocumentTitle("Templates")

    if (user === null) {
        return <Navigate to={'login?redirect=templates'}/>
    }

    const content = <div>
        <div className={"title-row"}>
            <h1>{t("templates.title")}</h1>
        </div>

        <ReactTemplatesTable
            onClickEditPublication={() => {}}
            props={props}/>
    </div>;

    return <Page id="publications"
                 activeMenuItem={"templates"}
                 breadcrumbs={[
                     {
                         path: './dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         title: 'side_menu.templates'
                     }
                 ]}
                 showBackButton={true}
                 content={content}/>
}

export default Templates