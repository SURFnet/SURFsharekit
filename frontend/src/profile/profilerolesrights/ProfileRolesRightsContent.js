import React from "react";
import './profilerolesrightscontent.scss'
import {useTranslation} from "react-i18next";
import {ReactTable} from "../../components/reacttable/reacttable/ReactTable";

function ProfileRolesRightsContent(props) {
    const {t} = useTranslation();
    const groups = props.groups

    return <div id={"tab-roles-rights"} className={"tab-content-container"}>
        <h2 className={"tab-title"}>{t("profile.tab_roles_rights")}</h2>
        <ReactProfileRolesRightsTable/>
    </div>

    function ReactProfileRolesRightsTable(props) {

        const tableRows = groups;

        const columns = React.useMemo(
            () => [
                {
                    Header: '',
                    accessor: 'icon',
                    style: {
                        width: "45px"
                    },
                    Cell: () => {
                        return (
                            <div className={"document-icon-cell"}>
                                <i className="fas fa-file-invoice document-icon"/>
                            </div>
                        )
                    }
                },
                {
                    Header: t('group.group'),
                    accessor: t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN',
                    className: 'bold-text',
                    style: {
                        width: "25%"
                    }
                },
                {
                    Header: t('group.organisation'),
                    accessor: 'partOf.title'
                },
                {
                    Header: t('group.organisation_level'),
                    accessor: 'partOf.level',
                    Cell: (tableInfo) => {
                        const levelKey = tableInfo.cell.value;
                        return levelKey ? t(`organisation.level.${levelKey}`) : "";
                    },
                    style: {
                        width: "30%"
                    }
                }
            ],
            []
        )

        return (
            <ReactTable tableName={'react-profile-rolls-and-rights-table'}
                        columns={columns}
                        tableRows={tableRows}
                        onReloadData={() => {}}
                        onRowClick={() => {}}
            />
        )
    }
}

export default ProfileRolesRightsContent;