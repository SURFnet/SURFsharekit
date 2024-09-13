import React, {useEffect, useState} from "react";
import './treeMultiSelectField.scss'
import {useTranslation} from "react-i18next";
import {SortableContainer, SortableElement} from "react-sortable-hoc";
import {faChevronDown, faCross, faPlus, faTimes, faWindowClose} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../../buttons/iconbuttontext/IconButtonText";
import VocabularyPopup from "../vocabularypopup/VocabularyPopup";
import {ThemedH6} from "../../../Elements";
import styled from "styled-components";
import {cultured, greyLight, majorelle, openSans, roundedBackgroundPointyUpperLeft, white} from "../../../Mixins";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";

export function TreeMultiSelectField(props) {

    const {t} = useTranslation()

    let classAddition = '';
    classAddition += (props.readonly ? ' disabled' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');
    const items = props.formReducerState[props.name] ?? [];
    const [options, setOptions] = useState(props.options ? props.options : [])
    let stringifiedDefaultValue = null
    if (!(props.defaultValue === undefined || props.defaultValue === null || props.defaultValue.length === 0)) {
        stringifiedDefaultValue = JSON.stringify(props.defaultValue);
    }

    let stringifiedCurrentValue = null
    if (!(items === undefined || items === null || items.length === 0)) {
        stringifiedCurrentValue = JSON.stringify(items);
    }

    useEffect(() => {
        const isDirty = stringifiedDefaultValue !== stringifiedCurrentValue
        props.setValue(props.name, stringifiedCurrentValue, {shouldDirty: isDirty})
    }, [items]);

    useEffect(() => {
        props.register({name: props.name}, {required: props.isRequired})
        props.setValue(props.name, stringifiedDefaultValue)
    }, [props.register]);

    return (

        <TreeMultiSelectFieldRoot>
                {props.readonly && !(items && items.length > 0) &&
                    <>
                        {props.showEmptyState
                            ?   <EmptyPlaceholder>
                                <ThemedH6>{props.emptyText}</ThemedH6>
                            </EmptyPlaceholder>
                            :   <div className={"field-input readonly"}>{"-"}</div>
                        }
                    </>
                }

                <TagContainer isEmpty={getSelectedItemsFromOptions().length === 0 || getSelectedItemsFromOptions().length === null}>
                   {getSelectedItemsFromOptions().map((option) => {
                        return (
                            <Tag>
                                <Value>{t('language.current_code') === 'nl' ? option.coalescedLabelNL : option.coalescedLabelEN}</Value>
                                {!props.readonly &&
                                    <DeleteButton icon={faTimes} onClick={() => deleteValue(option.value)}/>
                                }
                            </Tag>
                        )
                    })}
                </TagContainer>


                {!props.readonly &&
                <AddButton key={'add-vocabulary-button'} onClick={() => openVocabularyPopup()}>
                    <IconButtonText
                        className={"plus-button with-top-margin"}
                        faIcon={faPlus}
                        buttonText={items && items.length === 0 ? props.addText : ""}
                    />
                </AddButton>
                }
        </TreeMultiSelectFieldRoot>
    );

    function getSelectedItemsFromOptions() {
        return options.filter((o) => items.find((i) => o.value === i));
    }

    function deleteValue(value) {
        const action = {
            type: 'delete',
            value: value
        };
        props.onChange(action)
    }

    function createValue(value) {
        const action = {
            type: 'create',
            value: value
        };
        props.onChange(action)
    }

    function openVocabularyPopup() {
        VocabularyPopup.show(props.name, (vocabularyArr) => {
            const ids = vocabularyArr.value.map(o => o.id);
            const newOptions = vocabularyArr.value.map(o =>  {
                o.value = o.id;
                return o;
            })
            setOptions(options.concat(newOptions));
            createValue(ids);
        }, () => {}, props.retainOrder);
    }
}

const EmptyPlaceholder = styled.div`
    width: 100%;
    background: ${cultured};
    margin: 20px 0;
    text-align: center;
`;

const TreeMultiSelectFieldRoot = styled.div`
    display: flex;
    flex-direction: column;
    flex: 1;
    background: ${cultured};
`;

const Tag = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    ${roundedBackgroundPointyUpperLeft(majorelle, '8px')};
    padding: 12px 13px;
    flex-shrink: 0;
    flex-basis: fit-content;
`;

const Value = styled.div`
    ${openSans()};
    font-size: 12px;
    color: ${white};
`;

const DeleteButton = styled(FontAwesomeIcon)`
    font-size: 14px;
    color: ${white};
    cursor: pointer;
`;

const TagContainer = styled.div`
    background: ${cultured};
    display: flex;
    flex: 1; 
    flex-direction: row;
    row-gap: 15px;
    column-gap: 6px;
    flex-wrap: wrap;
    margin-top: ${props => props.isEmpty ? "0" : "15px"};
`;

const AddButton = styled.div`
    margin-top: 20px;
`;




