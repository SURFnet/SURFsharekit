import React, {useEffect, useState} from "react"
import './group.scss'
import Page, {GlobalPageMethods} from "../components/page/Page";
import {useTranslation} from "react-i18next";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import HorizontalTabList from "../components/horizontaltablist/HorizontalTabList";
import GroupPermissionTab from "./grouppermissiontable/GroupPermissionTab";
import GroupPersonTable from "./grouppersontable/GroupPersonTable";

function Group(props) {
    const {t} = useTranslation();
    const [group, setGroup] = useState(null);
    const [currentIndex, setCurrentIndex] = useState(0);

    useEffect(() => {
        getGroupObjectFromApi()
    }, [])

    let groupContent = undefined

    if (group) {
        groupContent = [
            <HorizontalTabList
                key={"tab-titles"}
                tabsTitles={[t('group.members') , t('group.permissions')]}
                selectedIndex={currentIndex} onTabClick={setCurrentIndex}/>,
            (currentIndex === 0 &&
                <GroupPersonTable
                    key={"group-person-table"}
                    group={group}
                    reloadGroup={() => getGroupObjectFromApi(false)}
                    history={props.history}/>),
            (currentIndex === 1 &&
                <GroupPermissionTab
                    key={"group-permission-table"}
                    group={group}
                />
            )
        ]
    }
    const content = <div>
        <div className={"header"}>
            <div className={"title-text"}>
                <h1>{group ? (t('language.current_code') === 'nl' ? group.labelNL : group.labelEN) : "\u00a0"}</h1>
                <h5>{group ? group.partOf.title : "\u00a0"}</h5>
            </div>
        </div>
        {groupContent}
    </div>;

    return <Page id="group"
                 history={props.history}
                 activeMenuItem={"group"}
                 breadcrumbs={[
                     {
                         path: '../../dashboard',
                         title: 'side_menu.dashboard'
                     },
                     {
                         path: '../organisation',
                         title: 'side_menu.organisation'
                     },
                     {
                         title: group ? (t('language.current_code') === 'nl' ? group.labelNL : group.labelEN) : 'group.group'
                     },
                 ]}
                 showBackButton={true}
                 content={content}/>;

    function getGroupObjectFromApi(showLoader = true) {
        showLoader && GlobalPageMethods.setFullScreenLoading(true)
        const config = {
            params: {
                'fields[groups]': 'partOf,title,amountOfPersons,codeMatrix,userPermissions,permissions,labelNL,labelEN',
                'include': 'partOf'
            }
        }
        Api.jsonApiGet('groups/' + props.match.params.id, onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            showLoader && GlobalPageMethods.setFullScreenLoading(false)
            setGroup(response.data);
        }

        function onServerFailure(error) {
            showLoader && GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                props.history.push('/login?redirect=' + window.location.pathname);
            } else if (error && error.response && (error.response.status === 404 || error.response.status === 400)) { //The object to access does not exist
                props.history.replace('/notfound');
            } else if (error && error.response && (error.response.status === 423)) { //The object is inaccesible
                props.history.replace('/removed');
            } else if (error && error.response && error.response.status === 403) { //The object to access is forbidden to view
                props.history.replace('/forbidden');
            } else { //The object to access does not exist
                props.history.replace('/unauthorized');
            }
        }

        function onLocalFailure(error) {
            showLoader && GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
        }
    }
}

export default Group;