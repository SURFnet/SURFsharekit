import React, {useEffect, useState} from "react";
import './treeMultiSelectField.scss'
import {useTranslation} from "react-i18next";
import {SortableContainer, SortableElement} from "react-sortable-hoc";
import {faPlus} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../../buttons/iconbuttontext/IconButtonText";
import VocabularyPopup from "../vocabularypopup/VocabularyPopup";

export function TreeMultiSelectField(props) {
    let classAddition = '';
    classAddition += (props.readonly ? ' disabled' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');
    const items = props.formReducerState[props.name] ?? [];
    const [options, setOptions] = useState(props.options ?? [])
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


    let clickToAddElement;
    if (props.readonly) {
        clickToAddElement = <></>
    } else {
        clickToAddElement =
            <div key={'add-vocabulary-button'} onClick={() => openVocabularyPopup()}>
                <IconButtonText className={"plus-button with-top-margin"}
                                faIcon={faPlus}
                                buttonText={props.addText}/>
            </div>
    }

    const rowsHeaderElement = <div className='field-label'
                                   key={'rows-header-element'}>{props.getRowHeader && props.getRowHeader()}</div>
    const sortableRows = <SortableComponent items={options.filter((o) => items.find((i) => o.value === i))}
                                            onItemAction={onItemAction}
                                            readonly={props.readonly}
                                            itemToComponent={props.itemToComponent}
                                            key={options.filter((o) => items.find((i) => o.value === i))}/>

    return (
        <div className={'tree-multiselect ' + classAddition}>
            {props.readonly && !(items && items.length > 0) && <div className={"field-input readonly"}>{"-"}</div>}
            {[rowsHeaderElement, sortableRows, clickToAddElement]}

        </div>
    );

    function onItemAction(event) {
        switch (event.type) {
            case "delete":
                deleteValue(event.value);
                break;
            case "create":
                createValue(event.value)
                break;
            default:
                break;
        }
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
            const ids = vocabularyArr.value.map(o=>o.id);
            const newOptions = vocabularyArr.value.map(o => {
                o.value = o.id
                return o
            })
            setOptions([...options, ...newOptions])
            createValue(ids)
        }, () => {
        })
    }
}


function SortableComponent(props) {
    return <SortableList items={props.items}
                         itemToComponent={props.itemToComponent}
                         onItemAction={props.onItemAction}
                         useDragHandle={true}
                         readonly={props.readonly}
                         key={'sortable list'}/>;
}

const SortableList = SortableContainer(({items, onItemAction, itemToComponent, readonly}) => {

    return <div className='sortable-container'>
        {(items ?? []).map((item, index) => (
            <SortableItem key={`item-${item.value}`}
                          index={index}
                          value={item}
                          onItemAction={onItemAction}
                          readonly={readonly}
                          itemToComponent={itemToComponent}/>
        ))}
    </div>
});

const SortableItem = SortableElement(({value: valuePart, onItemAction, itemToComponent, readonly}) => {
        const {t} = useTranslation();
        return itemToComponent(valuePart, onItemAction, readonly, t)
    }
);


