import React, {useCallback, useEffect, useRef, useState} from "react";
import {useDropzone} from 'react-dropzone'
import './repoItemField.scss'
import {useTranslation} from "react-i18next";
import arrayMove from 'array-move';
import {SortableContainer, SortableElement, SortableHandle} from "react-sortable-hoc";
import {faPlus} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../../buttons/iconbuttontext/IconButtonText";
import Toaster from "../../../util/toaster/Toaster";
import {cultured, spaceCadet, white} from "../../../Mixins";
import {ThemedH6} from "../../../Elements";
import styled from "styled-components";
import EllipsisIcon from "../../../resources/icons/ic-ellipsis.svg"

export function RepoItemField(props) {

    let classAddition = '';
    classAddition += (props.readonly ? ' disabled' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');
    const {t} = useTranslation();

    const onDrop = useCallback(acceptedFiles => {
        const maxSizeInMB = 10240; // 10GB limit

        const oversizedFiles = acceptedFiles.filter(file => file.size / 1048576 > maxSizeInMB);
        const nonOversizedFiles = acceptedFiles.filter(file => file.size / 1048576 <= maxSizeInMB);

        if (oversizedFiles.length > 0) {
            oversizedFiles.forEach(fileName => {
                Toaster.showDefaultRequestError(t('error_message.file_too_large', { fileName: fileName.name }));
            })

            return;
        }

        const action = {
            type: 'create',
            value: nonOversizedFiles
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
                <IconButtonText className={"plus-button-centered"}
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

const EmptyPlaceholder = styled.div`
    width: 100%;
    background: ${cultured};
    margin-top: 12px;
    padding: 20px 0px;
    text-align: center;
    border: 1px solid #F3F3F3;
    border-radius: 5px;
`;


export const DragHandle = SortableHandle(({  }) =>
    (
        <DragHandleIconContainer>
            <DragHandleDot />
            <DragHandleDot />
            <DragHandleDot />
            <DragHandleDot />
            <DragHandleDot />
            <DragHandleDot />
            {/*<DragHandleIcon src={EllipsisIcon}/>*/}
            {/*<DragHandleIcon src={EllipsisIcon}/>*/}
        </DragHandleIconContainer>
    )
);

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

    return (
        <SortableList
            items={props.items}
            itemToComponent={props.itemToComponent}
            onItemAction={props.onItemAction}
            onSortEnd={onSortEnd}
            useDragHandle={true}
            axis={"y"}
            lockAxis={"y"}
            lockToContainerEdges={true}
            readonly={props.readonly}/>
    )
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

const DragHandleIconContainer = styled.div`
  display: flex;
  flex-wrap: wrap;
  height: 12px;
  cursor: grab;
  max-width: 10px;
`

const DragHandleDot = styled.div`
  width: 3px;
  height: 3px;
  border-radius: 100%;
  background: ${spaceCadet};
  margin: 1px 1px;
`


