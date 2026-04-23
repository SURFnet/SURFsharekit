import styled from "styled-components";
import {ThemedH3} from "../../Elements";


export const ContentRoot = styled.div`
    padding: 15px;
`

export const CloseButtonContainer = styled.div`
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    
    &:hover {
        opacity: 0.7;
    }
`;

export const Header = styled.div`
    padding: 0 0 10px 0;
    margin-top: 10px;
    display: flex;
    align-items: center;
`;

export const Title = styled(ThemedH3)`
    margin: 0;
    flex-grow: 1;
`;

