import {faPlus} from "@fortawesome/free-solid-svg-icons";
import confettiSvg from "../resources/images/confetti.svg";
import LoadingIndicator from "../components/loadingindicator/LoadingIndicator";
import styled from "styled-components";
import React, {useEffect, useState} from "react";
import checkmarkSVG from "../resources/images/checkmark.svg";
import {ThemedH1} from "../Elements";
import {useTranslation} from "react-i18next";
import SURFButton from "../styled-components/buttons/SURFButton";
import {majorelle, majorelleLight, white} from "../Mixins";
import {useHistory} from "react-router-dom";
import {GlobalPageMethods} from "../components/page/Page";
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import {
    createAndNavigateToRepoItem,
    instituteCanCreateRepoItem,
    instituteCreateRepoItemPermissions, instituteCreateRepoItemPermissionToRealType
} from "../publications/Publications";
import AppStorage, {StorageKey} from "../util/AppStorage";
import Toaster from "../util/toaster/Toaster";
import Api from "../util/api/Api";
import ValidationError from "../util/ValidationError";
import AddPublicationPopup from "../publications/addpublicationpopup/AddPublicationPopup";
import {copyRepoItem} from "../components/reacttable/tables/publication/ReactPublicationTable";


function PublishPublicationCompletion(props){

    const [isLoading, setIsLoading] = useState(false)

    const {t} = useTranslation()
    const history = useHistory()

    const getChannels = () => {
        if (props.repoItem){
            const repoItem = props.repoItem

            const answers = repoItem.answers.map(answer => {
                return answer
            })

            let sections = [];
            repoItem.steps.forEach(step => {
                step.templateSections.forEach(section => {
                    sections.push(section)
                })
            })

            let fields = []
            sections.map(section => {
                return section.fields.filter(field => field.fieldType === "Switch-row" && field.channelType !== null).forEach(field => { fields.push(field)})
            })

            let result = []
            answers.forEach((answer) => {
                const validAnswer = fields.find((field) => field.key === answer.fieldKey);
                if (validAnswer) {
                    result.push(validAnswer.titleNL)
                }
            })

            return result.map(test => { return test })
        }
    }

    function createAndNavigateToRepoItem(props, successCallback = () => {
    }, errorCallback = () => {
    }, isProject = false) {
        const user = AppStorage.get(StorageKey.USER);

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
            errorCallback()
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
            if (error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                props.history.push('/login?redirect=' + window.location.pathname);
            }

            errorCallback()
        }

        function getUserWithGroups() {
            function personCallSuccess(response) {
                try {
                    getInstitutes(response.data.groups)
                } catch (e) {
                    onLocalFailure(e)
                }
            }

            const personCallConfig = {
                params: {
                    'include': "groups.partOf",
                    'filter[isRemoved]': "false",
                    'fields[groups]': 'partOf,title,amountOfPersons,codeMatrix,userPermissions,permissions,labelNL,labelEN',
                    'fields[institutes]': 'title,permissions,isRemoved,level,abbreviation,summary,type,childrenInstitutesCount'
                }
            };

            Api.jsonApiGet('persons/' + user.id, () => {
            }, personCallSuccess, onLocalFailure, onServerFailure, personCallConfig)
        }

        function getInstitutes(groups) {
            const institutes = []
            for (let i = 0; i < groups.length; i++) {
                const institute = groups[i].partOf
                if (instituteCanCreateRepoItem(institute)) {
                    institutes.push(institute)
                }
            }

            if (institutes.length === 0) {
                onLocalFailure(new ValidationError("No groups with permissions to create repoItem"))
                return
            }

            const newInstitutes = []

            function institutesCallSuccess(response) {
                setIsLoading(false)

                const apiCallInstitutations = response.data
                for (let i = 0; i < apiCallInstitutations.length; i++) {
                    const institute = apiCallInstitutations[i]
                    if (instituteCanCreateRepoItem(institute)) {
                        newInstitutes.push(institute)
                    }
                }

                showPublicationTypePopup(newInstitutes)
            }

            const institutesCallConfig = {
                params: {
                    'filter[distinctTemplates]': "1",
                    "filter[scope]": institutes.map(i => i.id).join(","),
                    "fields[institutes]": "title,permissions"
                }
            };

            Api.jsonApiGet('institutes/',
                () => {
                },
                (response) => {
                    institutesCallSuccess(response)
                },
                onLocalFailure,
                onServerFailure,
                institutesCallConfig)
        }

        function showPublicationTypePopup(institutes) {
            setIsLoading(false)
            try {
                if (institutes.length > 0) {
                    const firstInstitute = institutes[0]
                    const createRepoItemPermissions = instituteCreateRepoItemPermissions(firstInstitute)
                    if (institutes.length === 1 && isProject) {
                        //If there is only 1 institute when creating a new project, skip the popup
                        const createProjectPermission = createRepoItemPermissions.find(permission => permission === "canCreateProject")
                        if (createProjectPermission) {
                            createRepoItem(firstInstitute, instituteCreateRepoItemPermissionToRealType(createProjectPermission))
                        }
                    } else if (institutes.length === 1 && createRepoItemPermissions.length === 1) {
                        //If there is only 1 institute with only 1 publication type option, skip the popup
                        createRepoItem(firstInstitute, instituteCreateRepoItemPermissionToRealType(createRepoItemPermissions[0]))
                    } else {
                        AddPublicationPopup.show(institutes, (instituteAndType) => {
                            setIsLoading(true)
                            createRepoItem(instituteAndType.institute, instituteAndType.selectedPublicationType)
                        }, (repoItemToCopy) => {
                            setIsLoading(true)
                            copyRepoItem(repoItemToCopy.id, props.history, (response) => {
                                setIsLoading(false)
                                props.history.push(`../publications/${response.data.id}`)
                                successCallback()
                            })
                        }, () => {
                            successCallback()
                        }, isProject)
                    }
                } else {
                    throw new ValidationError("No publication types possible in all groups")
                }
            } catch (e) {
                onLocalFailure(e)
            }
        }

        function createRepoItem(institute, repoType) {
            function validator(response) {
                const repoItemData = response.data ? response.data.data : null;
                if (!(repoItemData && repoItemData.id && repoItemData.attributes)) {
                    setIsLoading(false)
                    errorCallback()
                    throw new ValidationError("The received repo item data is invalid")
                }
            }

            function onSuccess(response) {
                setIsLoading(false)
                const repoItemData = response.data.data
                isProject ? props.history.push(`../projects/${repoItemData.id}`, {isProject: true}) : props.history.push(`../publications/${repoItemData.id}`)
                successCallback()
            }

            const config = {
                headers: {
                    "Content-Type": "application/vnd.api+json",
                },
                params: {
                    'fields[repoItems]': ''
                }
            }

            const postData = {
                "data": {
                    "type": "repoItem",
                    "attributes": {
                        "repoType": repoType
                    },
                    "relationships": {
                        "relatedTo": {
                            "data": {
                                "type": "institute",
                                "id": institute.id
                            }
                        }
                    }
                }
            };

            Api.post('repoItems', validator, onSuccess, onLocalFailure, onServerFailure, config, postData)
        }

        getUserWithGroups()
    }

    return (
        <PublishPublicationPopupContentRoot>
            { isLoading && <LoadingIndicator isFullscreen={true}/>}
            <Confetti src={confettiSvg}/>
            <MessageContainer>
                <CheckMark src={checkmarkSVG}/>
                <Title>{t("publication.completion.title")}</Title>
                <Body>{`${t("publication.completion.body1")} "${props.repoItem.title}" ${t("publication.completion.body2")} ${getChannels()}`}</Body>
                <ButtonContainer>
                    <SURFButton
                        form="surf-form-edit-publication-form-id"
                        backgroundColor={majorelle}
                        highlightColor={majorelleLight}
                        text={t("publication.completion.to_dashboard")}
                        textSize={"14px"}
                        textColor={white}
                        padding={"0 30px"}
                        onClick={() => {
                            history.replace("/dashboard")
                        }}
                    />
                    <IconButtonText
                        disabled={true}
                        faIcon={faPlus}
                        buttonText={t("my_publications.add_publication")}
                        onClick={() => {
                            setIsLoading(true)
                            createAndNavigateToRepoItem(props, () => {
                                    setIsLoading(false)
                                },
                                () => {
                                    setIsLoading(false)
                                })
                        }}
                    />
                </ButtonContainer>
            </MessageContainer>

        </PublishPublicationPopupContentRoot>
    )
}

const ButtonContainer = styled.div`
    display: flex;
    flex-direction: row;
    gap: 25px;
    margin: auto;
    margin-top: 38px;
`;

const Body = styled.p`
    margin-top: 25px;
    text-align: center;
    max-width: 471px;
    margin-left: auto;
    margin-right: auto;
`;

const Title = styled(ThemedH1)`
    margin-top: 62px;
    text-align: center;
`;

const MessageContainer = styled.div`
    display: flex;
    flex-direction: column;
    position: relative;
    top: 128px;
    justify-content: center;
`;

const CheckMark = styled.img`
    height: 115px;
    width: auto;
    z-index: 2;
`;

const PublishPublicationPopupContentRoot = styled.div`
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    overflow-x: hidden;
    overflow-y: hidden;
`;

const Confetti = styled.img`
    z-index: 1;
    width: 100%;
    position: absolute;
    top: 0;
    left: 0;
`;

const LoadingIndicatorContainer = styled.div`
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
`;

export default  PublishPublicationCompletion;