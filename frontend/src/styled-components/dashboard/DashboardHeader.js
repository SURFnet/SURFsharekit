import React from 'react';
import styled from "styled-components";
import {majorelle, nunitoBold, nunitoRegular} from "../../Mixins";
import {useTranslation} from "react-i18next";
import logo from "../../resources/images/dashboard_logo.svg"

function DashboardHeader(props) {

    const {t} = useTranslation()

    return (
        <>
            <PurpleHeader>
                <DashboardHeading isSideMenuCollapsed={props.isSideMenuCollapsed} className={"row with-margin"}>
                    <DashboardH1>{CurrentTime()} {props.firstName}</DashboardH1>
                    <DashboardH2>{t("dashboard.subtitle")}</DashboardH2>
                    <Content className="content-wrapper">
                        {props.content}
                    </Content>
                </DashboardHeading>
                <SharekitDashboardLogo src={logo} alt="test"/>
            </PurpleHeader>
        </>
    )
}

function CurrentTime() {

    const {t} = useTranslation();
    var today = new Date()
    var curHr = today.getHours()

    if (curHr < 12) {
       return t("dashboard.message_morning")
    } else if (curHr < 18) {
        return t("dashboard.message_afternoon")
    } else {
        return t("dashboard.message_evening")
    }
}

const PurpleHeader = styled.div `
    padding-bottom: 20px;
    width: 100%;
    margin-left: ${props => props.isSideMenuCollapsed ? 125 : 50}px;
    transform: translateX(-50px);
    background-color: ${majorelle};
`;

const DashboardHeading = styled.div`
    padding-top: 55px;
`;

const Content = styled.div`
    padding-top: 10px;
`;

const SharekitDashboardLogo = styled.img`
    position: absolute;
    right: 0;
    top: 45px;
    width: 208px;
`;

const DashboardH1 = styled.h1`
   ${nunitoRegular};
   font-size: 40px;
   color: white;
   max-width: 75%;
`;

const DashboardH2 = styled.h2`
   ${nunitoBold};
   font-size: 30px;
   color: white;
   padding-top: 35px;
  max-width: 75%;
`;

export default DashboardHeader;