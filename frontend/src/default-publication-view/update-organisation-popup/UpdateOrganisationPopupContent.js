import * as Styled from './UpdateOrganisationPopupStyledComponents'
import React, {useEffect, useRef, useState} from "react";
import SwalClaimRequestPopup from "sweetalert2";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import {useTranslation} from "react-i18next";
import {FormField} from "../../components/field/FormField";
import {useForm} from "react-hook-form";
import Api from "../../util/api/Api";
import {BlackButton, NextButton} from "../../styled-components/buttons/NavigationButtons";
import LoadingSpinner from "../../resources/images/spinner.png";
import Toaster from "../../util/toaster/Toaster";

function UpdateOrganisationPopupContent(props) {
    const {register, setValue, getValues, watch} = useForm();
    const {t} = useTranslation();

    const [rootOptions, setRootOptions] = useState([]);
    const [treeOptions, setTreeOptions] = useState([]);
    const [isLoadingTree, setIsLoadingTree] = useState(false);
    const treeCache = useRef({});
    const selectedRoot = watch("rootOrganisation");

    const levelOrder = ['organisation', 'department', 'lectorate', 'discipline', 'consortium'];

    useEffect(() => {
        getRootInstitutes();
    }, []);

    useEffect(() => {
        setValue("scopedInstitutes", null);
        if (selectedRoot) {
            getTreeInstitutes(selectedRoot);
        } else {
            setTreeOptions([]);
        }
    }, [selectedRoot]);

    function getRootInstitutes() {
        const config = {
            params: {
                'filter[level]': 'organisation',
                'fields[institutes]': 'title,summary',
                'sort': 'title'
            }
        };

        Api.jsonApiGet('institutes', () => {}, (response) => {
            const data = response.data ?? [];
            const roots = data.map(org => ({
                value: org.id,
                labelNL: org.summary?.title || org.title || "Geen titel",
                labelEN: org.summary?.title || org.title || "Geen titel"
            }));

            setRootOptions(roots);

            if (roots.length === 1) {
                setValue("rootOrganisation", roots[0].value);
                getTreeInstitutes(roots[0].value);
            }
        }, () => {}, () => {}, config);
    }

    function getTreeInstitutes(rootUuid) {
        const config = {
            params: {
                'filter[organisation]': rootUuid,
                'fields[institutes]': 'title,summary,level'
            }
        };

        setIsLoadingTree(true);
        Api.jsonApiGet('institutes', () => {}, (response) => {
            const data = response.data ?? [];
            const levelIndex = Object.fromEntries(levelOrder.map((l, i) => [l, i]));

            const finalSelection = data
                .map(org => ({
                    value: org.id,
                    labelNL: org.summary?.title || org.title || "Geen titel",
                    labelEN: org.summary?.title || org.title || "Geen titel",
                    _level: (org.level || org.attributes?.level || '').toLowerCase()
                }))
                .sort((a, b) => (levelIndex[a._level] ?? 999) - (levelIndex[b._level] ?? 999))
                .map(({_level, ...rest}) => rest);

            setTreeOptions(finalSelection);
            setIsLoadingTree(false);
        }, () => { setIsLoadingTree(false); }, () => { setIsLoadingTree(false); }, config);
    }

    return (
        <Styled.ContentRoot>
            <Styled.CloseButtonContainer onClick={() => SwalClaimRequestPopup.close()}>
                <FontAwesomeIcon icon={faTimes}/>
            </Styled.CloseButtonContainer>
            <Styled.Header>
                <Styled.Title>{t("publication.update_organisation_popup.title")}</Styled.Title>
            </Styled.Header>
            {rootOptions.length === 0 ? (
                /* Show Spinner when length is 0 */
                <div className="loading-indicator">
                    <img src={LoadingSpinner} alt={'loading spinner'}/>
                </div>
            ) : (
                /* Show Form when length > 0 */
                <form>
                    <div className="form-row flex-row form-field-container">
                        {/* Show Root Dropdown only if there is more than 1 option */}
                        {rootOptions.length > 1 && (
                            <FormField
                                key="rootOrganisation"
                                type="dropdown"
                                options={rootOptions}
                                label={t('profile.root_organisation')}
                                isRequired={true}
                                name="rootOrganisation"
                                register={register}
                                setValue={setValue}
                            />
                        )}

                        {/* Always show Scoped Institutes if not loading */}
                        <FormField
                            key="scopedInstitutes"
                            type="dropdown"
                            options={treeOptions}
                            label={t('profile.organisation')}
                            isRequired={false}
                            name="scopedInstitutes"
                            register={register}
                            setValue={setValue}
                            tooltip={t("publication.update_organisation_popup.tooltip")}
                            isLoading={isLoadingTree}
                            disabled={!selectedRoot || isLoadingTree}
                        />
                    </div>
                </form>
            )}
            <div className={"flex-row justify-between"}>
                <BlackButton text={t("action.cancel")} onClick={() => props.onCancel()} />
                <NextButton
                    text={t("action.confirm")}
                    disabled={!selectedRoot}
                    onClick={() => {
                        const selected = getValues('scopedInstitutes') || getValues('rootOrganisation');
                        if (selected === props.currentInstitute) {
                            Toaster.showToaster({type: "info", message: t("toast.organisation_already_selected")});
                        } else {
                            props.onConfirm(selected);
                        }
                    }}
                />
            </div>
        </Styled.ContentRoot>
    )
}

export default UpdateOrganisationPopupContent;