import React from "react";
import './progressbar.scss'

function ProgressBar(props) {

    return (
        <div className={"surf-progress-bar " + props.className ?? ""}>
            <div className={"progress-bar-container"}>
                <div className={"progress-bar"} style={{width: `${props.progress}%`}}/>
            </div>
            <div className={"progress-percentage-text"}>{props.progress}%</div>
        </div>
    )
}

export default ProgressBar