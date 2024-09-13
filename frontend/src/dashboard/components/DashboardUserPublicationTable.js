import React, {useState} from "react";
import HorizontalTabList from "../../components/horizontaltablist/HorizontalTabList";
import DashboardPublicationTable from "./DashboardPublicationTable";
import {useTranslation} from "react-i18next";
import styled from "styled-components";
import {goToEditPublication} from "../../publications/Publications";

function DashboardUserPublicationTable(props) {
    const {t} = useTranslation();
    const [selectedIndex, setSelectedIndex] = useState(0);

    function onClickEditPublication(itemProps) {
        goToEditPublication(props, itemProps)
    }

    const getStatusFilter = () => {
        if (selectedIndex === 0) {
            return ""
        } else if (selectedIndex === 1) {
            return "Submitted"
        } else {
            return "Published"
        }
    }

    return <DashboardFilter>
        <HorizontalTabList
            tabsTitles={[t('dashboard.tab_everything'), t('dashboard.tab_submitted'), t('dashboard.tab_published')]}
            selectedIndex={selectedIndex} onTabClick={setSelectedIndex}
        />

        <DashboardPublicationTable
            repoStatusFilter={getStatusFilter()}
            filterOnUserId={props.userId}
            onClickEditPublication={onClickEditPublication}
            history={props.history}
            enablePagination={true}
        />

    </DashboardFilter>
}

const DashboardFilter = styled.div
`
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 36px;
`;

export default DashboardUserPublicationTable;