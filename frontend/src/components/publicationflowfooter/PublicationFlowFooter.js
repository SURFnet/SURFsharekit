import SURFButton from "../../styled-components/buttons/SURFButton";
import {desktopSideMenuWidth, greyLight, greyMedium, majorelle, white} from "../../Mixins";
import PublicationFormStep from "./PublicationFormStep";
import styled from "styled-components";
import React, {useEffect, useLayoutEffect, useReducer, useRef, useState} from "react";
import {useTranslation} from "react-i18next";

function PublicationFlowFooter(props) {

    const {t} = useTranslation();

    return (<StepFooterContainer isSideMenuCollapsed={props.isSideMenuCollapsed}>
            <StepFooterProgressBar>
                <StepFooterProgressBarChild percentage={(props.validatedStepIds.length / props.repoItem.steps.length) * 100} />
            </StepFooterProgressBar>
            <StepFooterIndicators
                isSideMenuCollapsed={props.isSideMenuCollapsed}
                className={"row with-margin"}>
                <SURFButton
                    width={"131px"}
                    backgroundColor={white}
                    text={props.currentIndex === 0 ? t("publication_flow.cancel") : t("publication_flow.previous_step")}
                    textColor={"black"}
                    border={`2px solid black`}
                    padding={"0 12px"}
                    onClick={props.handlePrevious}
                />

                <div className='flex-row form-step-list justify-between'>
                    {props.repoItem && props.repoItem.steps.map((step, index) => {
                        return (
                            <>
                                <div key={step.id} className='form-step-container'>
                                    <PublicationFormStep
                                        active={props.currentSelectedStep && props.currentSelectedStep.id === step.id}
                                        key={step.id}
                                        number={index + 1}
                                        subheader={(t('language.current_code') === 'nl' ? step.titleNL : step.titleEN)}
                                        header={(t('language.current_code') === 'nl' ? step.subtitleNL : step.subtitleEN)}
                                        handleStepClick={() => props.handleStepClick(index)}
                                        isValidated={props.validatedStepIds.includes(step.id)}
                                        isActive={props.currentStepId.id === step.id}
                                    />
                                </div>
                                {index !== props.repoItem.steps.length - 1 && <StepDivider className='form-step-divider'></StepDivider>}
                            </>

                        );
                    })}
                </div>

                <SURFButton
                    width={"131px"}
                    backgroundColor={majorelle}
                    text={props.currentIndex === (props.repoItem.steps.length - 1) ? (props.repoItem.permissions.canPublish ? t("publication_flow.publish") : t("publication_flow.submit")) : t("publication_flow.next_step")}
                    textColor={"white"}
                    padding={"0 12px"}
                    onClick={props.handleNext}
                />
            </StepFooterIndicators>
        </StepFooterContainer>)
}


const StepFooterContainer = styled.div`
    width: ${props => props.isSideMenuCollapsed ? '100%' : `calc(100% - ${desktopSideMenuWidth})`};
    margin-left: ${props => props.isSideMenuCollapsed ? 0 : desktopSideMenuWidth};
    height: 95px;
    position: fixed;
    bottom: 0;
    left: 0;
    background-color: white;
    transition: margin width 0.2s ease;
`

const StepFooterProgressBar = styled.div`
    width: 100%;
    height: 5px;
    background-color: transparent;
    overflow: hidden ;
`;

const StepFooterProgressBarChild = styled.div`
    width: ${props => props.percentage}%;
    height: 5px;
    background-color: ${majorelle};
    transition: 0.7s ease;
`;

const StepFooterIndicators = styled.div`
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%; 
    height: 100%;
    padding-left: 160px;
    padding-right: 160px;
`;

const StepDivider = styled.div`
  border-left: 1px solid ${greyLight};
  height: 100%;
  margin: 0 10px;
`;

export default PublicationFlowFooter;