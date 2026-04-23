import React from "react"
import './expandablelist.scss'
import {ExpandableRow} from "./ExpandableRow";
import {ExpandableListLoadingIndicator} from "./ExpandableListLoadingIndicator";
import styled from "styled-components";
import {useTranslation} from "react-i18next";

const ExpandableRowHeaders = styled.div`
    display: flex;
    justify-content: end;
    width: 100%;
    gap: 50px;
    
    .second-section {
        padding-left: 15px;
        display: flex;
        width: 300px;
        gap: 50px;
    }
    
    span {
        text-transform: uppercase;
        font-size: 12px;
        font-weight: 700;
    }

    span:nth-of-type(1) {
        width: 70px;
    }
    
    span:nth-of-type(2) {
        width: 130px;
    }
`

export function ExpandableList(props) {

    const {t} = useTranslation()

    if (props.isLoading || props.data === null) {
        return (
            <div className={"expandable-list with-margin"}>
                <ExpandableListLoadingIndicator loadingText={props.loadingText}/>
            </div>
        )
    }

    return (
        <>
            { props.includeHeaders &&
                <ExpandableRowHeaders>
                    <div className={'first-section'} />
                    <div className={'second-section'}>
                        <span></span>
                        {props.includeHeaders.map(item => {
                            return (
                                <span>{t('language.current_code') === 'nl' ? item.labelNL : item.labelEN}</span>
                            )
                        })}
                    </div>
                </ExpandableRowHeaders>
            }
            <div className={`expandable-list ${!props.includeHeaders && 'with-margin'}`}>
                {
                    props.data.map((institute) =>
                        <ExpandableRow key={institute.id}
                                       data={institute}
                                       isRootInstitute={true}
                                       showInactive={props.showInactive}
                                       onClickExpand={props.onClickExpand}
                                       showReportsData={props.showReportsData}
                        />
                    )
                }
            </div>
        </>

    )
}