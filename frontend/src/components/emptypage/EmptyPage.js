import React, {useState} from "react";
import NavigationBar from "../navigationbar/NavigationBar";
import LoadingIndicator from "../loadingindicator/LoadingIndicator";
import './emptypage.scss'

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
                <NavigationBar history={props.history}
                               showBackButton={false}
                               tintColor={props.tintColor}
                               showNavigationBarGradient={false}
                               hideSearchInput={true}
                               hideNotifications={true}
                />
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