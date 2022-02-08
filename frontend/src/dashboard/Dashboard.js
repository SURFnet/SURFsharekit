import React, {useEffect, useState} from "react";
import './dashboard.scss'
import {StorageKey, useAppStorageState} from "../util/AppStorage";
import {Redirect, useHistory} from "react-router-dom";
import Card from "../components/card/Card";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import Constants from '../sass/theme/_constants.scss'
import {useTranslation} from 'react-i18next';
import DashboardBackground from '../resources/images/surf-background.jpeg';
import {ReactComponent as DashboardBlockArrows} from "../resources/images/dashboard_logo.svg";
import Page, {GlobalPageMethods} from "../components/page/Page";
import {createAndNavigateToRepoItem, goToEditPublication} from "../publications/Publications";
import ReactPublicationTable from "../components/reacttable/tables/ReactPublicationTable";
import ApiRequests from "../util/api/ApiRequests";
import {IconTitleHeader} from "../components/icontitleheader/IconTitleHeader";
import {faFileInvoice, faHome} from "@fortawesome/free-solid-svg-icons";
import IconButton from "../components/buttons/iconbutton/IconButton";
import {ReportPieChart} from "../components/piechart/ReportPieChart";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import {HelperFunctions} from "../util/HelperFunctions";
import {UserPermissions} from "../util/UserPermissions";

function Dashboard(props) {
    const {t} = useTranslation();
    const [user] = useAppStorageState(StorageKey.USER);
    const [userRoles, setUserRoles] = useAppStorageState(StorageKey.USER_ROLES);
    const [userPermissions] = useAppStorageState(StorageKey.USER_PERMISSIONS);
    const [userRootInstitutes, setUserRootInstitutes] = useState(null);
    const [repoItemReportData, setRepoItemReportData] = useState(null);
    const history = useHistory();
    const userHasExtendedAccess = userRoles ? userRoles.find(c => c !== 'Student' && c !== 'Default Member') : false;

    useEffect(() => {
        if (typeof (user) !== 'undefined' && user != null) {
            ApiRequests.getExtendedPersonInformation(user, history,
                (data) => {
                    setUserRoles(data.groups.map(g => g.roleCode).filter(r => !!r && r !== 'null'))
                    setUserRootInstitutes(HelperFunctions.getMemberRootInstitutes(data))
                }, () => {
                }
            );
        }
    }, [])

    useEffect(() => {
        getDashboardReportData();
    }, [userRootInstitutes])

    function handleRepoItemFileDownload(id) {
        GlobalPageMethods.setFullScreenLoading(true)

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            Api.downloadFileWithAccessTokenAndPopup(response.data.data.attributes.url, response.data.data.attributes.title)
        }

        function onLocalFailure(error) {
            console.log(error);
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            console.log(error);
            GlobalPageMethods.setFullScreenLoading(false)
            if (error.response.status === 403) {
                history.replace('/forbidden');
            } else {
                Toaster.showServerError(error)
            }
        }

        Api.get('repoItemFiles/' + id, onValidate, onSuccess, onLocalFailure, onServerFailure);
    }

    useEffect(() => {
        if (typeof (user) !== 'undefined' && user != null) {
            if (props.match.params.type === 'publicationfiles' && props.match.params.id !== undefined && props.match.params.id !== '') {
                handleRepoItemFileDownload(props.match.params.id)
            }
        }
    }, [])

    if (user === null) {
        if (props.match.params.type && props.match.params.id) {
            return <Redirect to={'../login?redirect=' + window.location.pathname}/>
        } else {
            return <Redirect to={'login'}/>
        }
    }


    function getShareCardContent() {
        return (
            <div className={"share"}>
                <DashboardBlockArrows className={"icon-publications"}/>
                <h2>{t("dashboard.block_1_title")}</h2>
                {
                    UserPermissions.canCreateRepoItem(userPermissions) &&
                    <IconButton className={"icon-button-publications"}
                                icon={faFileInvoice}
                                text={t("dashboard.block_1_button")}
                                onClick={() => {
                                    createRepoItem()
                                }}
                    />
                }
            </div>
        )
    }

    function getExplanationCardContent() {
        return (
            <div className={"explanation"}>
                <div className={"text-container"}>
                    <h2>{t("dashboard.block_2_title")}</h2>
                    <div className={"explanation-text"}>{t("dashboard.block_2_subtitle")}</div>
                </div>
                {/*<ButtonText text={t("dashboard.block_2_button")}/>*/}
            </div>
        )
    }

    function getBenefitsCardContent() {

        let benefitTexts = [
            t("dashboard.block_3_bullet_1"),
            t("dashboard.block_3_bullet_2"),
            t("dashboard.block_3_bullet_3"),
        ];

        return (
            <div className={"benefits"}>
                <div className={"text-container"}>
                    <h2>{t("dashboard.block_3_title")}</h2>
                    <div className={"benefits-list"}>
                        {benefitTexts.map((text, i) =>
                            <div className={"benefits-list-item"} key={i}>
                                <div className={"fas fa-check"}/>
                                <div className={"text"}>{text}</div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        )
    }

    function getReportCardContent() {
        return (
            <div className={"report"}>
                <div className={"text-container"}>
                    <h2>{t("report.dashboard_title")}</h2>
                </div>
                <ReportPieChart reportData={repoItemReportData}/>
            </div>
        )
    }

    function createRepoItem() {
        GlobalPageMethods.setFullScreenLoading(true)
        createAndNavigateToRepoItem(props,
            () => {
                GlobalPageMethods.setFullScreenLoading(false)
            },
            () => {
                GlobalPageMethods.setFullScreenLoading(false)
            })
    }

    function onClickEditPublication(itemProps) {
        goToEditPublication(props, itemProps)
    }

    const content = <div>
        <div className={"title-row"}>
            <IconTitleHeader title={t("dashboard.title")}
                             icon={faHome}/>
        </div>
        <div className={"card-row"}>
            <Card content={getShareCardContent()} backgroundColor={Constants.majorelle}/>
            <Card content={userHasExtendedAccess ? getReportCardContent() : getExplanationCardContent()}/>
            <Card content={getBenefitsCardContent()}/>
        </div>

        <div className={"title-row secondary"}>
            <h2>{userHasExtendedAccess ? t("dashboard.my_publications.open_title") : t("dashboard.my_publications.title")}</h2>
        </div>
        <ReactPublicationTable repoStatusFilter={userHasExtendedAccess && 'Submitted'}
                               filterOnUserId={!userHasExtendedAccess && user.id}
                               onClickEditPublication={onClickEditPublication}
                               history={props.history}/>
        {
            userHasExtendedAccess &&
            <div className={'flex-reverse-row'}>
                <ButtonText text={t('dashboard.my_publications.see_all')}
                            onClick={() => props.history.push(`publications`)}/>
            </div>
        }
    </div>;

    return <Page id="dashboard"
                 history={props.history}
                 content={content}
                 menuButtonColor='white'
                 style={{backgroundImage: `url('` + DashboardBackground + `')`}}
                 showDarkGradientOverlay={true}
                 showHalfPageGradient={true}
                 activeMenuItem={"dashboard"}
                 showNavigationBarGradient={false}/>;


    function getDashboardReportData() {

        if (userRootInstitutes) {

            function onValidate(response) {
            }

            function onSuccess(response) {
                if (response.data) {
                    setRepoItemReportData(response.data);
                }
            }

            function onLocalFailure(error) {
                Toaster.showDefaultRequestError();
            }

            function onServerFailure(error) {
                Toaster.showServerError(error)
                if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                    props.history.push('/login?redirect=' + window.location.pathname);
                }
            }

            const instituteIds = userRootInstitutes.map((institute) => {
                return institute.id
            }).join(",")

            const config = {
                params: {
                    'filter[id]': instituteIds,
                }
            };

            Api.jsonApiGet('reports/instituteReports', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
        }
    }
}

export default Dashboard;