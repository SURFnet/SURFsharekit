import React from "react"
import './emptystate.scss'
import {useTranslation} from "react-i18next";

export function EmptyState(props) {

    const {t} = useTranslation()

    return (
        <div className='empty-state-container'>
            <div className={'empty-list-title'}>
                {props.title ?? t("search.no_results")}
            </div>
        </div>
    )
}