import React, {useEffect, useMemo, useState} from "react";
import styled from "styled-components";
import {Accordion} from "../../components/Accordion";
import {useForm} from "react-hook-form";
import {useTranslation} from "react-i18next";
import {greyLight, openSans} from "../../Mixins";
import {Checkbox} from "../../components/Checkbox";
import {ThemedH6} from "../../Elements";
import {faEnvelope} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {GlobalPageMethods} from "../../components/page/Page";
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import {useHistory} from "react-router-dom";
import LoadingIndicator from "../../components/loadingindicator/LoadingIndicator";

export function ProfileNotificationsContent(props) {

    const {
        notifications
    } = props;

    const makingNewProfile = props.profileData === undefined
    const [personConfig, setPersonConfig] = useState(!makingNewProfile ? props.profileData.config : {});
    const history = useHistory();
    const {t} = useTranslation();
    const { register, handleSubmit, errors, watch, formState } = useForm();

    const notificationCategories = useMemo(() => {
        if (notifications) {
            return Array.from(new Set(notifications.map((notification) => {
                return notification.notificationCategory;
            }))).sort((a, b) => { return a.sortOrder - b.sortOrder})
        } else return [];
    }, [notifications])

    return (
        <>
            { notifications ? formComponent() : loader()}
        </>
    )

    function loader() {
        return (
            <LoaderContainer>
                <LoadingIndicator centerInPage={true}/>
            </LoaderContainer>
        )
    }

    function formComponent() {
        return (
            <NotificationForm onSubmit={handleSubmit(saveNotificationForm)}>
                <button ref={props.notificationFormRef} type="submit" style={{ display: 'none' }} />
                {
                    notificationCategories.map((notificationCategory) => {

                        return (
                            <Accordion key={notificationCategory.id}
                                       title={t('language.current_code') === 'nl' ? notificationCategory.labelNL : notificationCategory.labelEN}
                            >
                                <NotificationListContainer>
                                    <NotificationListRow>
                                        <ListHeaderTitle>{t("profile.notification_type")}</ListHeaderTitle>
                                        <ListHeaderIconContainer>
                                            <EmailIcon icon={faEnvelope}/>
                                        </ListHeaderIconContainer>
                                    </NotificationListRow>
                                    <NotificationList>
                                        {props.notifications
                                            .filter((notification) => { return notification.notificationCategory.id === notificationCategory.id})
                                            .sort((a, b) => {return a.sortOrder - b.sortOrder})
                                            .map((notification) => {
                                                return (
                                                    <NotificationListRow key={notification.id}>
                                                        <NotificationListRowContentLeft>
                                                            {t('language.current_code') === 'nl' ? notification.labelNL : notification.labelEN}
                                                        </NotificationListRowContentLeft>
                                                        <NotificationListRowContentRight>
                                                            {
                                                                notification.notificationSettings.map((notificationSetting) => {
                                                                    return (
                                                                        <NotificationListRowContentCell key={notificationSetting.id}>
                                                                            <Checkbox
                                                                                disabled={notificationSetting.isDisabled === 1}
                                                                                initialValue={personConfig.enabledNotifications.includes(notificationSetting.key)}
                                                                                name={notificationSetting.key}
                                                                                type={"checkbox"}
                                                                                register={register}
                                                                            />
                                                                        </NotificationListRowContentCell>
                                                                    );
                                                                })
                                                            }
                                                        </NotificationListRowContentRight>
                                                    </NotificationListRow>
                                                )
                                        })}
                                    </NotificationList>
                                </NotificationListContainer>
                            </Accordion>
                        )
                    })
                }
            </NotificationForm>
        )
    }

    function saveNotificationForm(formData) {
        const results = Object.entries(formData)
            .filter((entry) => { return entry[1] === true })
            .map((entry) => { return entry[0] })
        
        GlobalPageMethods.setFullScreenLoading(true)

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            },
            params: {
                "include": "groups.partOf,config",
                'fields[groups]': 'partOf,title,userPermissions,labelNL,labelEN',
                'fields[institutes]': 'title,level,type'
            }
        }

        const patchData = {
            "data": {
                "type": "personConfig",
                "id": props.profileData.config.id,
                "attributes": {
                    "enabledNotifications": JSON.stringify(results)
                }
            }
        };

        Api.patch('personConfigs/' + props.profileData.config.id, () => {
        }, onSuccess, onLocalFailure, onServerFailure, config, patchData);

        function onSuccess(response) {
            const responseData = Api.dataFormatter.deserialize(response.data);
            setPersonConfig(responseData);
            GlobalPageMethods.setFullScreenLoading(false)
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            console.log(error);
            Toaster.showServerError(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
            console.log(error);
        }
    }
}

const LoaderContainer = styled.div`
    position: relative;
    padding-top: 400px;
`;

const NotificationForm = styled.form`
    margin-top: 50px;
    display: flex;
    flex-direction: column;
    row-gap: 10px;
`;

const NotificationListContainer = styled.div`
    margin-top: 14px;
    border-top: 1px solid ${greyLight};
    padding: 10px 15px;
`;

const NotificationList = styled.div`
    
`;

const NotificationListRow = styled.div`
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    padding: 10px 66px 10px 0;
`;

const NotificationListRowContentLeft = styled.div`
    ${openSans};
    font-size: 12px;
`;

const NotificationListRowContentRight = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
    column-gap: 6px;
`;

const NotificationListRowContentCell = styled.div`
    width: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
`;

const ListHeaderTitle = styled(ThemedH6)`

`;

const ListHeaderIconContainer = styled.div`
    display: flex;
    flex-direction: row;
    column-gap: 6px;
`;

const EmailIcon = styled(FontAwesomeIcon)`
    width: 20px;
    height: 20px;
`;


