import styled from "styled-components";
import OrcidLogo from "../../resources/icons/orcid-logo.png";
import {spaceCadet} from "../../Mixins";

export const OrcidBanner = ({orcid}) =>  {
    if (!orcid) return null;

    return (
        <ORCIDContent>
            <ORCIDImage src={OrcidLogo} alt="Orcid logo"/>
            <ORCIDText>{orcid}</ORCIDText>
        </ORCIDContent>
    )
}

export const ORCIDContent = styled.div`
    background: linear-gradient(to right, rgb(240, 240, 240, 1), rgb(248, 248, 248, 1));
    padding: 10px 20px;
    display: flex;
    justify-items: center;
    align-items: center;
    width: 100%;
    height: fit-content;
    align-self: center;
    gap: 20px;
`;

export const ORCIDImage = styled.img`
    height: 20px;
    width: 20px;
`;

export const ORCIDText = styled.p`
    font-size: 16px;
    font-weight: 700;
    color: ${spaceCadet};
    margin: 0;
`;