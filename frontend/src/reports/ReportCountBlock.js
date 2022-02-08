import React from "react";
import './reportcountblock.scss'

export function ReportCountBlock(props) {

    return (
        <div className={"report-count-block"}>
            <h3 className={"report-count-title"}>
                {props.title}
            </h3>
            <div className={"report-count-subtitle"}>
                {props.subtitle}
            </div>
        </div>
    )
}