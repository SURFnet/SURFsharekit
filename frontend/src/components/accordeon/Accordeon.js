import React, { useState } from 'react';
import styled from 'styled-components';
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faChevronDown, faChevronUp} from "@fortawesome/free-solid-svg-icons";
import {nunitoBold, nunitoRegular, nunitoSemiBold} from "../../Mixins";

const AccordionContainer = styled.div`
  width: 100%;
  background-color: #2D364F;
  border-radius: 10px;
  margin: 10px 0;
  padding: 10px 30px;
  color: white !important;
`;

const AccordionTitle = styled.div`
  color: white;
  cursor: pointer;
  ${nunitoSemiBold()};
  
  &:hover {
    text-decoration: underline;
  }
`;

const AccordionContent = styled.div`
  display: ${props => (props.isOpen ? 'block' : 'none')};
  padding: 15px 0;
  
  & > div {
    margin-bottom: 10px;

    &:last-child {
      margin-bottom: 0;
    }
  }
  
`;

const AccordeonHeader = styled.div`
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
`

function Accordeon({ title, children }) {
    const [isOpen, setIsOpen] = useState(false);

    const toggleAccordion = () => {
        setIsOpen(!isOpen);
    };

    return (
        <AccordionContainer>
            <AccordeonHeader onClick={toggleAccordion}>
                <AccordionTitle>{title}</AccordionTitle>
                <FontAwesomeIcon color={"white"} icon={isOpen ? faChevronUp : faChevronDown}/>
            </AccordeonHeader>
            <AccordionContent isOpen={isOpen}>{isOpen && children}</AccordionContent>
        </AccordionContainer>
    )
}

export default Accordeon