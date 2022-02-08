import React, {useEffect} from "react";
import './navigationbar.scss'
import Dropdown from "../dropdown/Dropdown";
import {useTranslation} from "react-i18next";
import {faArrowLeft, faChevronRight} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../buttons/iconbuttontext/IconButtonText";
import {ReactComponent as IconLanguage} from "../../resources/icons/icon-language.svg";
import moment from 'moment'
import {StorageKey, useAppStorageState} from "../../util/AppStorage";
import {SearchInput} from "../searchinput/SearchInput";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";

function NavigationBar(props) {
    const [languageLocale, setLanguageLocale] = useAppStorageState(StorageKey.LANGUAGE_LOCALE);
    const {t, i18n} = useTranslation();

    const languageOptions = [
        {value: 'nl', labelEN: 'Dutch', labelNL: 'Nederlands'},
        {value: 'en', labelEN: 'English', labelNL: 'Engels'}
    ];

    useEffect(() => {
        i18n.changeLanguage(languageLocale, (error, t) => {
            if (error) {
                return console.log('something went wrong loading', error);
            }
            changeMomentLocale(languageLocale)
        });
    }, [languageLocale])

    function languageDidChange(selectedOption) {
        if (selectedOption !== null && selectedOption.length > 0) {
            const languageCode = selectedOption;
            setLanguageLocale(languageCode)
        }
    }

    function changeMomentLocale(languageCode) {
        //Changes moment's locale. This is used in the DatePickers to set the correct language and date notation.
        moment.locale(languageCode);
    }

    function getTintColorClass() {
        let tintColorClass = ""
        if (props.tintColor && props.tintColor === "light") {
            tintColorClass = "light";
        }

        function addSpaceToClassName(className) {
            if (className.length > 0) {
                className = " " + className
            }
            return className;
        }

        tintColorClass = addSpaceToClassName(tintColorClass)

        return tintColorClass;
    }

    function getNavigationBarGradientClass() {
        return (props.showNavigationBarGradient || props.showNavigationBarGradient === undefined) ? " navigation-bar-gradient" : "";
    }

    function navigateBack() {
        if (props.backButtonAction) {
            props.backButtonAction()
        } else {
            props.history.goBack();
        }
    }

    function didPressSearch(e) {
        const searchQuery = e.target.value;
        if (e.key === 'Enter' && searchQuery && searchQuery.length > 0) {
            props.history.push('../search/' + searchQuery)
        }
    }

    const selectedLanguageLocale = languageLocale ?? t("language.current_code")
    const languageIcon = <IconLanguage className={"surf-select__custom-select-icon"}/>;

    return (
        <div className={"navigation-bar" + getNavigationBarGradientClass()}>
            <div className={"navigation-bar-wrapper row with-margin"}>
                <div className={"navigation-bar-container"}>
                    <div className={"left-content-container"}>
                        {props.showBackButton &&
                        <IconButtonText className={"back-button"}
                                        faIcon={faArrowLeft}
                                        buttonText={t("navigation_bar.back")}
                                        onClick={navigateBack}/>
                        }
                        {props.breadcrumbs &&
                        <BreadCrumbs history={props.history} parts={props.breadcrumbs}/>
                        }
                    </div>
                    <div className={"right-content-container"}>
                        {
                            !props.hideSearchInput &&
                            <SearchInput placeholder={t("navigation_bar.search")}
                                         onKeyDown={didPressSearch}/>
                        }
                        <Dropdown onChange={languageDidChange}
                                  options={languageOptions}
                                  icon={languageIcon}
                                  defaultValue={selectedLanguageLocale}/>
                        {
                            // !props.hideNotifications &&
                            // <div className={"notifications-container"}>
                            //     <i className={"fas fa-bell" + getTintColorClass()}/>
                            //     <div className={"notifications-counter"}>12</div>
                            // </div>
                        }
                    </div>
                </div>
            </div>
        </div>
    );
}


function BreadCrumbs(props) {
    const {t} = useTranslation();
    const items = [];
    props.parts.forEach((p, i) => {
        if (i !== props.parts.length - 1) {
            items.push(<a onClick={() => {
                props.history.push(p.path)
            }} className={'breadcrumb clickable'}>{t(p.title)}</a>)
        } else {
            items.push(<a className={'breadcrumb'}>{t(p.title)}</a>)
        }
        if (i !== props.parts.length - 1) {
            items.push(<FontAwesomeIcon icon={faChevronRight} className={'breadcrumb-divider'}/>)
        }
    })
    return <div className={'breadcrumbs'}>
        {items}
    </div>
}


export default NavigationBar;