import React from 'react';
import styled from "styled-components";
import {majorelle, openSans, spaceCadet, SURFShapeLeft, SURFShapeRight, white} from "../../../Mixins";
import {Tooltip} from "../../../components/field/FormField";
import BasicDropdownItem from '../dropdown-items/BasicDropdownItem';
import DropdownItemWithIcon from "../dropdown-items/DropdownItemWithIcon";


const DropdownListShape = {
    LEFT: "LEFT",
    RIGHT: "RIGHT"
}

/**
 * This component expects a list of BasicDropdownItem items
 * */
function BasicDropdownList(props) {

    const {
        dropdownItems,
        listShape,
        itemHeight,
        listWidth,
        modalButtonClose  // This prop is only defined when BasicDropDownList is part of the ModalButton component
    } = props

    const hasSingleItem = dropdownItems && dropdownItems.length === 1;
    const defaultItemHeight = '40px';
    const defaultListWidth = '180px';

    return (
        <DropdownList
            shape={listShape ?? DropdownListShape.LEFT}
            hasSingleItem={hasSingleItem}
            listWidth={listWidth ?? defaultListWidth}
        >
            {dropdownItems.map((dropdownItem, index) => {
                switch (true) {
                    case dropdownItem instanceof BasicDropdownItem: {
                        return getBasicDropDownItem(dropdownItem, index)
                    }
                    case dropdownItem instanceof DropdownItemWithIcon: {
                        return getDropDownItemWithIcon(dropdownItem, index)
                    }
                    default: {
                        return getDropDownItemWithIcon(dropdownItem, index)
                    }
                }
            })}
        </DropdownList>
    )

    function getBasicDropDownItem(dropdownItem, index) {
        return (
            <Item
                key={(dropdownItem.text + index)}
                itemHeight={itemHeight ?? defaultItemHeight}
                onClick={() => {
                    if(modalButtonClose) {
                        modalButtonClose()
                    }
                    dropdownItem.onClick()
                }}
            >
                <Text>{dropdownItem.text}</Text>
                {dropdownItem.tooltipText &&
                <Tooltip text={dropdownItem.tooltipText}/>
                }
            </Item>
        )
    }

    function getDropDownItemWithIcon(dropdownItem, index) {
        return (
            <Item
                key={(dropdownItem.text + index)}
                dropdownItem={dropdownItem}
                itemHeight={itemHeight ?? defaultItemHeight}
                onClick={() => {
                    if(modalButtonClose) {
                        modalButtonClose()
                    }
                    dropdownItem.onClick()
                }}
            >
                <IconWrapper>
                    <Icon src={dropdownItem.icon}/>
                </IconWrapper>
                <Text>{dropdownItem.text}</Text>
            </Item>
        )
    }
}

const DropdownList = styled.div`
    ${props => props.shape === DropdownListShape.LEFT ? `${SURFShapeLeft()}` : `${SURFShapeRight()}`};
    display: flex;
    flex-direction: column;
    background: ${white};
    width: ${props => props.listWidth};
    box-shadow: 0px 4px 10px rgba(45, 54, 79, 0.2);
    z-index: 100;
    position: relative;
    > * {
        &:first-child:hover {
            ${props => props.shape === DropdownListShape.LEFT ? 'border-radius: 2px 15px 0 0' : 'border-radius: 15px 2px 0 0'};
        }
        &:last-child:hover {
            ${props => {if(props.hasSingleItem) {
               return props.shape === DropdownListShape.LEFT ? 'border-radius: 2px 15px 15px 15px;' : 'border-radius: 15px 2px 15px 15px;';
            } else {
               return 'border-radius: 0 0 15px 15px;';
            }}
        }
    }
`;

const Item = styled.div`
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: ${props => props.dropdownItem instanceof DropdownItemWithIcon ? 'flex-start' : 'space-between'};
    gap: ${props => props.dropdownItem instanceof DropdownItemWithIcon ? '20px' : '0'};
    padding: 0 15px;
    height: ${props => props.itemHeight};
    color: ${spaceCadet};
    &:hover {
        background: rgba(172, 166, 235, 0.3);
        color: ${majorelle};
    }
    cursor: pointer;
    user-select: none;
`;

const Icon = styled.img`
    display: block;
    margin: 0 auto;
    width: 14px;
`;

const IconWrapper = styled.div`
    width: 24px;
`;

const Text = styled.div`
    ${openSans};
    font-size: 12px;
`;

export default BasicDropdownList;