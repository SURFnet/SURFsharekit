import React, {useState} from "react";
import NavigationBar from "../navigationbar/NavigationBar";
import Constants from '../../sass/theme/_constants.scss'
import SideMenu from "../../sidemenu/SideMenu";
import LoadingIndicator from "../loadingindicator/LoadingIndicator";
import {useTranslation} from "react-i18next";

export class GlobalPageMethods {
    static setFullScreenLoading = {}
}

function Page(props) {
    const [isLoading, setIsLoading] = useState(props.showFullscreenLoader ?? false);
    const [isSideMenuVisibleOnMobile, setIsSideMenuVisibleOnMobile] = useState(false);
    GlobalPageMethods.setFullScreenLoading = setIsLoading

    return (
        <div id={props.id} className="main" style={props.style}>
            <SideMenu history={props.history}
                      activeMenuItem={props.activeMenuItem}
                      showOnMobile={isSideMenuVisibleOnMobile}
                      toggleMenu={() => {
                          setIsSideMenuVisibleOnMobile(!isSideMenuVisibleOnMobile);
                      }}
                      menuButtonColor={props.menuButtonColor ?? Constants.majorelle}
            />

            {props.showDarkGradientOverlay && <div className={"dark-gradient-overlay"}/>}
            {props.showHalfPageGradient && <div className={"half-page-gradient"}/>}

            <div className={"page-content-container"}>
                <NavigationBar history={props.history}
                               showBackButton={props.showBackButton}
                               tintColor={props.tintColor}
                               backButtonAction={props.backButtonAction}
                               breadcrumbs={props.breadcrumbs}
                               showNavigationBarGradient={props.showNavigationBarGradient}/>
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

export default Page;