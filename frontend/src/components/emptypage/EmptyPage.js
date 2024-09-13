import React, {useState} from "react";
import LoadingIndicator from "../loadingindicator/LoadingIndicator";
import './emptypage.scss'
import {useGlobalState} from "../../util/GlobalState";

export class GlobalEmptyPageMethods {
    static setFullScreenLoading = {}
}

function EmptyPage(props) {
    const [isLoading, setIsLoading] = useState(props.showFullscreenLoader ?? false);

    GlobalEmptyPageMethods.setFullScreenLoading = setIsLoading

    return (
        <div id={props.id} className="main empty-page" style={props.style}>
            <div className={"dark-gradient-overlay"}/>
            <div className={"page-content-container"}>

                <div className={"page-wrapper"} onScroll={props.onScroll} ref={props.contentRef}>
                    <div className={"page-content row with-margin"}>
                        {props.content}
                    </div>
                    <LoadingIndicator
                        isLoading={isLoading ?? false}
                        isFullscreen={true}/>
                </div>
            </div>
        </div>
    );
}

export default EmptyPage;