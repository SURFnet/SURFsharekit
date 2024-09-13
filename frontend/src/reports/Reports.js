import React from "react";
import './reports.scss'
import Page from "../components/page/Page";
import {useTranslation} from "react-i18next";
import IconButton from "../components/buttons/iconbutton/IconButton";
import {faDownload} from "@fortawesome/free-solid-svg-icons";
import ExportPopup from "./ExportPopup";
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {Redirect} from "react-router-dom";
import useDocumentTitle from "../util/useDocumentTitle";

export default function Reports(props) {

    const {t} = useTranslation()
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const userHasExtendedAccess = userRoles ? userRoles.find(c => c !== 'Student' && c !== 'Default Member') : false;
    const canViewReports = userRoles ? userRoles.find(c => c !== 'Student' && c !== 'Default Member' && c !== 'Staff') : false;

    useDocumentTitle("Reports")

    const content = (
        <div className={"reports-content"}>
            <div className={"title-row"}>
                <h1>{t("report.title")}</h1>
            </div>
            <div className={"explanation-text"}>
                {t('report.explanation')}
            </div>
            <div className={"actions-row"}>
                <IconButton className={"report-export-button"}
                            icon={faDownload}
                            text={t("report.export")}
                            onClick={openExportPopup}
                />
            </div>
            {/*<div className={"report-statistics"}>*/}
            {/*    <div className={"report-count-blocks-row"}>*/}
            {/*        <ReportCountBlock title={"34.564"} subtitle={"Ingevoerde publicaties"}/>*/}
            {/*        <ReportCountBlock title={"24.732"} subtitle={"Open access publicaties"}/>*/}
            {/*        <ReportCountBlock title={"3.578"} subtitle={"Actieve gebruikers"}/>*/}
            {/*    </div>*/}
            {/*</div>*/}
        </div>
    )

    return (
        canViewReports && userHasExtendedAccess ? <Page id="reports"
                                                      history={props.history}
                                                      activeMenuItem={"reports"}
                                                      showBackButton={true}
                                                      breadcrumbs={[
                                                          {
                                                              path: './dashboard',
                                                              title: 'side_menu.dashboard'
                                                          },
                                                          {
                                                              title: 'side_menu.reports'
                                                          }
                                                      ]}
                                                      content={content}/>
        : <Redirect to={"/forbidden"}/>
    )

    function openExportPopup() {
        ExportPopup.show()
    }
}