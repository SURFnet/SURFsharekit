import React from 'react';
import styled from "styled-components";
import {majorelle, nunitoBold, spaceCadet, spaceCadetLight, SURFShapeRight} from "../../Mixins";
import {faTimes} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";

function ProfileCard(props) {

    return (
        <ProfileCardShape >
           <Text onClick={props.goToProfile}>{props.name}</Text>
           <TimesIcon icon={faTimes} onClick={props.onClick} />
        </ProfileCardShape>
    )
}

const ProfileCardShape = styled.div`
    ${SURFShapeRight}
    display: flex;
    align-items: center;
    justify-content: space-around;
    background: ${majorelle};
    padding: 0px 15px 0px 20px; 
    height: 40px;
    margin: 0 0px 0 15px;
    cursor: pointer;
`;

const TimesIcon = styled(FontAwesomeIcon)`
    color: white;
    cursor: pointer;
    margin-left: 10px;
`;

const Text = styled.div`
    color: white;
    ${nunitoBold};
    font-size: 14px;
    line-height: 19px;
`;

export default ProfileCard;