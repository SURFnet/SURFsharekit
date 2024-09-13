/*
import React, {useState} from "react"
import "./progresssection.scss"
import ProgressBar from "../progressbar/ProgressBar";
import {useTranslation} from "react-i18next";

function ProgressSection(props) {
    const {t} = useTranslation();
    const [progressPerSection, setProgressPerSection] = useState([]);
    const [activeSection, setActiveSection] = useState(props.sections[0]);

    props.setProgressPerSectionFunction(setProgressPerSection)
    props.setActiveSectionFunction(setActiveSection)

    const sections = progressPerSection.map((sectionProgress, index) => {
        const section = sectionProgress.section;
        const progress = sectionProgress.progress;
        const title = t('language.current_code') === 'nl' ? section.titleNL : section.titleEN;

        return {
            id: section.id,
            title: title,
            progress: Math.round(progress)
        }
    });

    return (
        <div className={"progress-section-wrapper"}>
            <div className={"progress-section-container"}>
                <ProgressSectionList sections={sections} activeSection={activeSection}/>
            </div>
            {props.footer}
        </div>
    )
}

const ProgressSectionList = (props) => {

    const getActiveClass = (section) => {
        if (props.activeSection && section) {
            return (props.activeSection.id === section.id) ? "active" : "";
        } else {
            return "";
        }
    }

    function goToSection(id) {
        document.getElementById(id).scrollIntoView();
    }

    return (
        props.sections.map((section) => {
            return <div key={section.id} className={"progress-section"} onClick={() => goToSection(section.id)}>
                <div className={"active-indicator " + getActiveClass(section)}/>
                <div className={"progress-section-title"}>
                    <h5 className={getActiveClass(section)}>{section.title}</h5>
                </div>
                <ProgressBar progress={section.progress}/>
            </div>
        })
    )
}

export default ProgressSection*/
