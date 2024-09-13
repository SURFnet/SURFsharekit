import React, {useEffect} from "react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faArrowRight, faFileInvoice, faUsers} from "@fortawesome/free-solid-svg-icons";
import './searchresultsrow.scss'
import {useTranslation} from "react-i18next";
import IconButtonText from "../components/buttons/iconbuttontext/IconButtonText";
import {ProfileBanner} from "../components/profilebanner/ProfileBanner";

export function SearchResultRow(props) {

    const {t} = useTranslation();

    function getHrefLink(){
        switch (props.type.toLowerCase()) {
            case 'repoitem':
                return `../publications/${props.id}`
            case 'group':
                return `../groups/${props.id}`
            case 'person':
                return `../profile/${props.id}`
            default:
                return null;
        }
    }


    function navigateToSearchResultDetailPage() {
        switch (props.type.toLowerCase()) {
            case 'repoitem':
                props.history.push(`../publications/${props.id}`)
                break;
            case 'group':
                props.history.push(`../groups/${props.id}`)
                break;
            case 'person':
                props.history.push(`../profile/${props.id}`)
                break;
            default:
                return null;
        }
    }

    function SearchResultIcon() {
        let iconContent;

        if (props.type.toLowerCase() === 'person') {
            iconContent = <ProfileBanner imageUrl={undefined}/>
        } else {
            let icon;
            switch (props.type.toLowerCase()) {
                case 'repoitem':
                    icon = faFileInvoice
                    break;
                case 'group':
                    icon = faUsers
                    break;
            }
            iconContent = <FontAwesomeIcon icon={icon}/>
        }

        return (
            <div className={"icon-container"}>
                {iconContent}
            </div>
        )
    }

    return (
        <a className={"search-result-row"} href={getHrefLink()}>
            <div className={"row-content"}>
                <SearchResultIcon/>
                <div className={"row-information"}>
                    <div className={"search-result-title"}>
                        {props.title}
                    </div>
                    <div className={"search-result-subtitle"}>
                        {props.subtitle}
                    </div>
                </div>

                <IconButtonText buttonText={t('search.view')}
                                faIcon={faArrowRight}
                                onClick={navigateToSearchResultDetailPage}/>
            </div>
        </a>
    )
}