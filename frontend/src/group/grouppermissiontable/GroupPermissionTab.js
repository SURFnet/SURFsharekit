import React, {useEffect, useMemo, useRef, useState} from "react"
import './grouppermissiontable.scss'
import '../../components/field/formfield.scss'
import {useTranslation} from "react-i18next";
import {useForm} from "react-hook-form";
import ButtonText from "../../components/buttons/buttontext/ButtonText";
import Toaster from "../../util/toaster/Toaster";
import Api from "../../util/api/Api";
import {GlobalPageMethods} from "../../components/page/Page";
import {FoldButton, Tooltip} from "../../components/field/FormField";
import {useHistory} from "react-router-dom";
import LoadingIndicator from "../../components/loadingindicator/LoadingIndicator";
import styled from "styled-components";
import {Accordion} from "../../components/Accordion";
import {Checkbox} from "../../components/Checkbox";
import {greyLight, majorelle, nunitoRegular, openSans} from "../../Mixins";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faChevronDown, faChevronUp} from "@fortawesome/free-solid-svg-icons";

function GroupPermissionTab(props) {
    const [permissionDescriptions, setPermissionDescriptions] = useState(null)
    const [extendedSections, setExtendedSections] = useState([])
    const {register, handleSubmit, errors, setValue, getValues, trigger} = useForm();
    const {t} = useTranslation();
    const formSubmitButton = useRef()
    const history = useHistory()
    let content = undefined;

    const permissionCategories = useMemo(() => {
        if (permissionDescriptions) {
            return Array.from(new Set(permissionDescriptions.map((permissionDescription) => {
                return permissionDescription.permissionCategory;
            }))).sort((a, b) => { return a.sortOrder - b.sortOrder})
        } else return [];
    }, [permissionDescriptions])

    let allSectionsAreExtended = permissionCategories && (permissionCategories.length === extendedSections.length)

    useEffect(() => {
        getPermissionDescriptions()
    }, [props.group]);

    function extendAllSections() {
        const extendedSections = permissionCategories.map((category) => {
            return category.id
        })
        setExtendedSections(extendedSections)
    }

    function collapseOrExtendAllSections() {
        if ((extendedSections.length >= 0 && extendedSections.length < permissionCategories.length) || extendedSections.length === 0) {
            extendAllSections()
        } else {
            setExtendedSections([])
        }
    }

    return (
        <>
            {
                permissionDescriptions ? (
                    <PermissionTabContentContainer>
                        <PermissionExplanation>{t("group.permission_explanation")}</PermissionExplanation>
                        <ButtonContainer>
                            <CollapseButton onClick={() => {
                                collapseOrExtendAllSections()
                            }}>
                                <FontAwesomeIcon icon={ allSectionsAreExtended ? faChevronDown : faChevronUp}/>
                                <div>{allSectionsAreExtended ? t("action.collapse_all") : t("action.extend_all")}</div>
                            </CollapseButton>
                        </ButtonContainer>

                        { permissionCategories.map((permissionCategory) => {
                            return (
                                <Accordion
                                    key={permissionCategory.id}
                                    isExtended={extendedSections.includes(permissionCategory.id)}
                                    title={t('language.current_code') === 'nl' ? permissionCategory.labelNL : permissionCategory.labelEN}
                                    onChange={() => {
                                        let isExtended = extendedSections.includes(permissionCategory.id)
                                        if (isExtended) {
                                            setExtendedSections(extendedSections.filter((section) => { return section !== permissionCategory.id}))
                                        } else {
                                            setExtendedSections([...extendedSections, permissionCategory.id])
                                        }
                                    }}
                                >
                                    <PermissionListRowContainer>
                                        { permissionDescriptions
                                            .filter((permissionDescription) => { return permissionDescription.permissionCategory.id === permissionCategory.id})
                                            .sort((a, b) => {return a.sortOrder - b.sortOrder})
                                            .map((permissionDescription) => {
                                                return (
                                                    <PermissionListRow key={permissionDescription.id}>
                                                        <PermissionListRowContent dangerouslySetInnerHTML={{__html: getPermissionDescriptionText(permissionDescription)}}>
                                                        </PermissionListRowContent>
                                                    </PermissionListRow>
                                                )
                                            })}
                                    </PermissionListRowContainer>
                                </Accordion>
                            );
                        })

                        }
                    </PermissionTabContentContainer>
                ) : (
                    <LoadingContainer>
                        <LoadingIndicator centerInPage={true}/>
                    </LoadingContainer>
                )
            }
        </>
    );

    function getPermissionDescriptionText(permissionDescription) {
        let text = t('language.current_code') === 'nl' ? permissionDescription.textNL : permissionDescription.textEN
        const subText =  [...text.matchAll("\\*\\*\\*\((.|\\n)*?)\\*\\*\\*")];

        subText.forEach((match) => {
            text = text.replace(match[0], `<span>${match[1]}</span>`)
        })

        return text
    }

    function getPermissionDescriptions() {

        const config = {
            params: {
                'include': "permissionCategory",
                'filter[group][EQ]': props.group.id,
            }
        };

        function onValidate(response) {}
        function onSuccess(response) {
            setPermissionDescriptions(response.data)
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
        }

        Api.jsonApiGet('permission-descriptions/', onValidate, onSuccess, onLocalFailure, onServerFailure, config);
    }
}

const PermissionTabContentContainer = styled.div`
    display: flex;
    flex-direction: column;
    gap: 16px;
`;

const CollapseButton = styled.div`
    top: 20px;
    font-size: 12px;
    font-weight: 400;
    ${openSans};
    display: flex;
    gap: 5px;
    align-items: center;
    cursor: pointer;
`;

const ButtonContainer = styled.div`
    display: flex;
    flex-direction: row;
    justify-content: end;
    align-items: center;
    height: 30px;
`;

const LoadingContainer = styled.div`
    position: relative;
    padding-top: 400px;
`;

const PermissionListRow = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: start;
    height: 50px;
    width: 100%;
    border-bottom: 1px solid ${greyLight};
`;

const PermissionListRowContent = styled.div`
    ${openSans};
    font-size: 12px;
    
    span {
        color: ${majorelle};
        font-weight: 700;
    }
`;

const PermissionListRowContainer = styled.div`
    margin-top: 12px;
`;

const PermissionExplanation = styled.div`
  ${nunitoRegular};
  font-size: 14px;
  max-width: 700px;
`;

export default GroupPermissionTab;