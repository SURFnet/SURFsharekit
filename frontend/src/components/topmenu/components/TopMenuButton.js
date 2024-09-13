import React, {useState} from "react";
import styled from "styled-components";
import {flame, majorelleLight, openSansBold, SURFShapeRight, white} from "../../../Mixins";

function TopMenuButton(props) {

    const {
        count,
        icon,
        onClick
    } = props

    return (
        <TopMenuButtonRoot onClick={() => onClick()}>
            <Icon src={icon}/>
            {count > 0 && <Counter count={count}>{count > 99 ? 99 : count}</Counter>}
        </TopMenuButtonRoot>
    )
}

const TopMenuButtonRoot = styled.div`
    ${SURFShapeRight};
    background: ${majorelleLight};
    width: 34px;
    min-width: 34px;
    height: 34px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    user-select: none;
`;

const Counter = styled.div`
    ${openSansBold};
    font-size: ${props => props.count <= 9 ? "9px" : "7px"};;
    color: ${white};
    background: ${flame};
    width: 14px;
    height: 14px;
    text-align: center;
    position: absolute;
    top: -6px;
    right: -6px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
`;

const Icon = styled.img`
    -webkit-user-drag: none;
    -khtml-user-drag: none;
    -moz-user-drag: none;
    -o-user-drag: none;
    user-drag: none;
`;

export default TopMenuButton;