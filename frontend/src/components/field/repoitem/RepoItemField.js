import React, {useCallback, useEffect, useRef, useState} from "react";
import {useDropzone} from 'react-dropzone'
import './repoItemField.scss'
import {useTranslation} from "react-i18next";
import arrayMove from 'array-move';
import {SortableContainer, SortableElement, SortableHandle} from "react-sortable-hoc";
import {faPlus} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../../buttons/iconbuttontext/IconButtonText";
import Toaster from "../../../util/toaster/Toaster";

export function RepoItemField(props) {
    let classAddition = '';
    classAddition += (props.readonly ? ' disabled' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');
    const {t} = useTranslation();

    const onDrop = useCallback(acceptedFiles => {
        const fileSizeInMB = acceptedFiles[0].size / 1000000;
        if (fileSizeInMB > 500) {
            Toaster.showDefaultRequestError(t('error_message.file_too_large'));
            return;
        }
        const action = {
            type: 'create',
            value: acceptedFiles
        };
        props.onChange(action);
    }, []);
    const {getRootProps, getInputProps, isDragActive} = useDropzone({onDrop});
    const items = props.formReducerState[props.name];

    let stringifiedDefaultValue = null
    if(!(props.defaultValue === undefined || props.defaultValue === null || props.defaultValue.length === 0)) {
        stringifiedDefaultValue = JSON.stringify(props.defaultValue);
    }

    let stringifiedCurrentValue = null
    if(!(items === undefined || items === null ||  items.length === 0)) {
        stringifiedCurrentValue = JSON.stringify(items);
    }

    useEffect(() => {
        props.register({name: props.name}, {required: props.isRequired})
        props.setValue(props.name, stringifiedDefaultValue)
    }, [props.register]);

    const isDirty = stringifiedDefaultValue !== stringifiedCurrentValue
    props.setValue(props.name, stringifiedCurrentValue, {shouldDirty: isDirty})


    let clickToAddElement;
    if (props.readonly) {
        clickToAddElement = <></>
    } else {
        if (props.hasFileDrop) {
            clickToAddElement = <div {...getRootProps()} className='dropzone'>
                <input {...getInputProps()} />
                {isDragActive ? t('file_drop.hover') : t('file_drop.open')}
                <IconButtonText className={"plus-button"}
                                faIcon={faPlus}
                                buttonText={t("file_drop.select")}/>
            </div>
        } else {
            clickToAddElement =
                <div onClick={() => props.onChange({type: "create"})}>
                    <IconButtonText className={"plus-button with-top-margin"}
                                    faIcon={faPlus}
                                    buttonText={props.addText}/>
                </div>
        }
    }

    const rowsHeaderElement = <div className='field-label'>{props.getRowHeader && props.getRowHeader()}</div>

    const sortableRows = <SortableComponent items={items}
                                            onItemAction={onItemAction}
                                            readonly={props.readonly}
                                            itemToComponent={props.itemToComponent}/>

    return (
        <div className={'repoitem ' + classAddition}>
            {props.readonly && !(items && items.length > 0) && <div className={"field-input readonly"}>{"-"}</div>}
            {props.hasFileDrop ? [clickToAddElement, rowsHeaderElement, sortableRows] : [rowsHeaderElement, sortableRows, clickToAddElement]}
        </div>
    );

    function onItemAction(event) {
        switch (event.type) {
            case "edit":
                props.onChange(event);
                break;
            case "delete":
                deleteRepoItem(event.value);
                break;
            case "sort change":
                props.onChange(event);
                break;
            default:
                break;
        }
    }

    function deleteRepoItem(repoItemId) {
        const action = {
            type: 'delete',
            value: repoItemId
        };
        props.onChange(action)
    }
}


export const DragHandle = SortableHandle(() => <i className="fas fa-arrows-alt order-icon"/>);

function SortableComponent(props) {
    const [items, setItems] = useState(props.items);

    useEffect(() => {
        setItems(props.items);
    }, [props.items]);

    const onSortEnd = ({oldIndex, newIndex}) => {
        const newlyOrderedItems = arrayMove(items, oldIndex, newIndex);
        const action = {
            type: 'sort change',
            value: newlyOrderedItems
        };
        props.onItemAction(action);

        setItems(newlyOrderedItems);
    };

    return <SortableList items={props.items}
                         itemToComponent={props.itemToComponent}
                         onItemAction={props.onItemAction}
                         onSortEnd={onSortEnd}
                         useDragHandle={true}
                         readonly={props.readonly}/>;
}

const SortableList = SortableContainer(({items, onItemAction, itemToComponent, readonly}) => {

    return <div className='sortable-container'>
        {(items ?? []).map((item, index) => (
            <SortableItem key={`item-${item.id}`}
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


