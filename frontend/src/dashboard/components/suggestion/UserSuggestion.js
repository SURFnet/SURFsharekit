import {ThemedH4, ThemedP} from "../../../Elements";
import styled from "styled-components";
import UserIconMajorelle from '../../../resources/icons/ic-user-majorelle.svg';
import {SwitchField} from "../../../components/field/switch/Switch";
import React, {useEffect, useState} from "react";
import {useTranslation} from "react-i18next";
import ChevronDown from "../../../resources/icons/ic-chevron-down-black.svg";
import File from "../../../resources/icons/File.svg";
import {cultured, greyLight, greyLighter} from "../../../Mixins";
import LoadingIndicator from "../../../components/loadingindicator/LoadingIndicator";
import Api from "../../../util/api/Api";
import Toaster from "../../../util/toaster/Toaster";
import AppStorage, {StorageKey} from "../../../util/AppStorage";

const StyledUserSuggestion = styled.div `
    border-radius: 2px 15px 15px 15px;
    padding: 12px;
    box-shadow: 0 4px 10px rgba(196, 196, 196, 0.2);
    background-color: white;
`

const InfoSection = styled.div`
    position: relative;
`

const PersonInfo = styled.div `
    width: 100%;
    display: flex;
    gap: 15px;
`;

const PersonPictureContainer = styled.div`
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 2px 15px 15px 15px;
    border: ${greyLight} solid 1px;
    width: 75px;
    height: 75px;
`

const PersonPicture = styled.img`
    width: ${props => props.src === UserIconMajorelle ? '48px' : '100%'};
    height: ${props => props.src === UserIconMajorelle ? '48px' : '100%'};
`

const SwitchFieldContainer = styled.div`
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
`

const FoundInButton = styled.div`
    display: flex;
    margin-top: 16px;
    gap: 10px;
    cursor: pointer;
`

export const Icon = styled.img`
    width: 12px;
    transform: ${props => props.expanded === "true" ? 'rotate(180deg)' : 'rotate(0deg)'};
`;

const FoundInSection = styled.div`
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 15px;
    margin-bottom: 5px;
`

const FoundInItemTitle = styled(ThemedP)`
    font-weight: 700;
`

const FoundInItemDescription = styled(ThemedP)`
    font-size: 9px;
    line-height: 12px;
`

const StyledFoundInItem = styled.div`
    display: flex;
    gap: 10px;
    padding: 10px;
    border: ${greyLighter} 1px solid;
    border-radius: 5px;
`

const FoundInItemIconContainer = styled.div`
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: ${cultured};
    width: 33px;
    height: 33px;
    border-radius: 4px;
    
`

const FileIcon = styled.img`
    width: 14px;
    height: 14px;
`

export function UserSuggestion(props) {
    const {t} = useTranslation();

    const [isExpanded, setIsExpanded] = React.useState(false);
    const [isSelected, setIsSelected] = useState(0);
    const [isLoading, setIsLoading] = useState(false);
    const [repoItems, setRepoItems] = useState([]);

    useEffect(() => {
        if (isExpanded && repoItems.length === 0 && !isLoading) {
            getRepoItems()
        }
    }, [isExpanded]);

    useEffect(() => {
        if (isSelected) {
            props.addProfileDataToMergeList(props.person)
        } else {
            props.removeProfileDataToMergeList(props.person)
        }
    }, [isSelected]);


    function getRepoItems() {
        console.log("Fetching repo items for user: ", props.person.id)
        setIsLoading(true)

        const config = {
            params: {
                "filter[isRemoved]": "false",
                "filter[repoType][NEQ]": "Project",
                "filter[scope]": "off",
                "filter[search]": props.person.id ?? 0,
                "page[size]": 2
            }
        };

        Api.jsonApiGet('repoItemSummaries', onValidate, onSuccess, onLocalFailure, onServerFailure, config);

        function onValidate(response) {
        }

        function onSuccess(response) {
            setIsLoading(false)
            setRepoItems(response.data)
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
        }

        function onLocalFailure(error) {
            Toaster.showServerError(error)
        }
    }


    return (
        <StyledUserSuggestion>
            <InfoSection>
                <PersonInfo>
                    <PersonPictureContainer>
                        <PersonPicture src={props.person.image ?? UserIconMajorelle} alt={props.person.name}/>
                    </PersonPictureContainer>

                    <div>
                        <ThemedH4>{props.person.name ?? ""}</ThemedH4>
                        <ThemedP>{props.person.institutes[0] ?? ""}</ThemedP>
                    </div>
                </PersonInfo>
                <SwitchFieldContainer>
                    <SwitchField
                        defaultValue={0}
                        onChange={setIsSelected}
                        trueText={t("switch_field.yes")}
                        falseText={t("switch_field.no")}
                    />
                </SwitchFieldContainer>

                {props.person.repoCount > 0 &&
                    <FoundInButton onClick={() => setIsExpanded(!isExpanded)}>
                        <Icon src={ChevronDown} expanded={isExpanded.toString()}/>
                        <ThemedP>{t("onboarding.suggestion_step.person_card.found_in")} {props.person.repoCount} {t(props.person.repoCount > 1 ? 'onboarding.suggestion_step.person_card.publications' : 'onboarding.suggestion_step.person_card.publication')}</ThemedP>
                    </FoundInButton>
                }
            </InfoSection>

            { isExpanded &&
                <FoundInSection>
                    <LoadingIndicator isLoading={isLoading}/>
                    {repoItems.map((item, index) => {
                        return <FoundInItem item={item} key={index}/>
                    })}
                </FoundInSection>
            }


        </StyledUserSuggestion>
    )
}

export function FoundInItem(props) {
    return (
        <StyledFoundInItem>
            <FoundInItemIconContainer>
                <FileIcon src={File} alt="file icon"/>
            </FoundInItemIconContainer>
            <div>
                <FoundInItemTitle>{props.item.title ?? "-"}</FoundInItemTitle>
                <FoundInItemDescription>
                    {props.item.extra.publisher}
                    {props.item.extra.organisations ? ", " + props.item.extra.organisations :  ""}
                    {props.item.extra.theme ? ", " + props.item.extra.theme:  ""}
                </FoundInItemDescription>
            </div>
        </StyledFoundInItem>
    )
}