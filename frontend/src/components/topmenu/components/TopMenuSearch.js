import React, {useEffect, useState} from "react";
import styled from "styled-components";
import SearchIcon from '../../../resources/icons/ic-search.svg';
import {majorelleLight, openSans, SURFShapeRight} from "../../../Mixins";
import TopMenuButton from "./TopMenuButton";
import NotificationIcon from "../../../resources/icons/ic-notification.svg";
import {useOutsideElementClicked} from "../../../util/hooks/useOutsideElementClicked";
import {useHistory} from "react-router-dom";

function TopMenuSearch(props) {

    const searchRef = React.createRef()
    const inputRef = React.createRef()
    const [isExpanded, setExpanded] = useState(false);
    const [inputValue, setInputValue] = useState(props.defaultValue ? props.defaultValue : "")
    const history = useHistory();

    useOutsideElementClicked(() => {
        setExpanded(false)
    }, searchRef);

    return (
        <TopMenuSearchRoot ref={searchRef}>
            <TopMenuButtonWrapper>
                <TopMenuButton
                    icon={SearchIcon}
                    onClick={() => onSearchButtonPressed()}
                />
            </TopMenuButtonWrapper>
            <SearchInputTextField
                type="text"
                defaultValue={props.defaultValue}
                placeholder={isExpanded ? props.placeholder : null}
                onKeyDown={(e) => onEnterPressed(e)}
                onChange={(event) => setInputValue(event.target.value)}
                value={inputValue}
                isExpanded={isExpanded}
                ref={inputRef}
            />
        </TopMenuSearchRoot>
    )

    function onEnterPressed(e) {
        if (e.key === 'Enter' && inputValue && inputValue.length > 0) {
            history.push('../search/' + inputValue)
        }
    }

    function onSearchButtonPressed() {
        if(isExpanded) {
            if (inputValue && inputValue.length > 0) {
                history.push('../search/' + inputValue)
            } else {
                inputRef.current.focus()
            }
        } else {
            setExpanded(true)
            inputRef.current.focus()
        }
    }

}

const TopMenuSearchRoot = styled.div`
    height: 34px;
    max-width: 270px;
    min-width: 270px;
    width: 100%;
    position: relative;
    display: inline-flex;
`;

const TopMenuButtonWrapper = styled.div`
    z-index: 10;
    position: absolute;
    top: 0;
    right: 0;
`;

const SearchInputTextField = styled.input`
    ${openSans};
    max-width: 270px;
    height: 100%;
    position: absolute;
    top: 0;
    right: 16px;
    background: white;
    font-size: 12px !important;
    line-height: 12px;
    padding-left: 19px;
    outline: none;
    border-radius: 15px 0 0 15px !important;
    transition: all 0.2s ease-in;
    transition-property: width, padding;
    
    ${props => {
        if(props.isExpanded) {
            return (`
                width: 100%;
                padding: 0 30px 0 19px !important;
            `);
        } else {
            return (`
                width: 0%;
                padding: 0 !important;
            `);
        }
    }};
`;

export default TopMenuSearch;