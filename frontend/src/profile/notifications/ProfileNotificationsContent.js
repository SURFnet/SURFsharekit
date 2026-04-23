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
import LoadingIndicator from "../../components/loadingindicator/LoadingIndicator";
import {useNavigation} from "../../providers/NavigationProvider";

export function ProfileNotificationsContent(props) {

    const {
        notifications
    } = props;

    const makingNewProfile = props.profileData === undefined
    const [personConfig, setPersonConfig] = useState(!makingNewProfile ? props.profileData.config : {});
    const navigate = useNavigation();
    const {t} = useTranslation();

    const defaultValues = useMemo(() => {
        if (!notifications) {
            return {};
        }
        const values = {};
        notifications.forEach((notification) => {
            notification.notificationSettings.forEach((notificationSetting) => {
                values[notificationSetting.key] = Boolean(
                    personConfig?.enabledNotifications?.includes(notificationSetting.key)
                );
            });
        });
        return values;
    }, [notifications, personConfig]);

    const {register, handleSubmit, formState: {errors}, watch, formState, reset} = useForm({defaultValues});

    useEffect(() => {
        if (notifications) {
            reset(defaultValues);
        }
    }, [notifications, defaultValues, reset]);

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
                                                                                initialValue={personConfig?.enabledNotifications?.includes(notificationSetting.key)}
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
            .filter((entry) => entry[1] === true)
            .map((entry) => entry[0]);
        const merged = new Set(results);
        notifications.forEach((notification) => {
            notification.notificationSettings.forEach((ns) => {
                if (ns.isDisabled === 1 && personConfig?.enabledNotifications?.includes(ns.key)) {
                    merged.add(ns.key);
                }
            });
        });
        const enabledNotificationKeys = Array.from(merged);
        
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
                    "enabledNotifications": JSON.stringify(enabledNotificationKeys)
                }
            }
        };

        Api.patch('personConfigs/' + props.profileData.config.id, () => {
        }, onSuccess, onLocalFailure, onServerFailure, config, patchData);

        const errorCallback = (error) => {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
            console.log(error);
        }

        function onSuccess(response) {
            const responseData = Api.dataFormatter.deserialize(response.data);
            Toaster.showDefaultRequestSuccess()
            setPersonConfig(responseData);
            GlobalPageMethods.setFullScreenLoading(false)
        }

        function onServerFailure(error) {
            errorCallback(error)
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                navigate('/login?redirect=' + window.location.pathname);
            }
        }

        function onLocalFailure(error) {
            errorCallback(error)
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


