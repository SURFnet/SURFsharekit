import {PieChart} from "react-minimal-pie-chart";
import React from "react";
import './reportpiechart.scss'
import LoadingIndicator from "../loadingindicator/LoadingIndicator";
import {useTranslation} from "react-i18next";

export function ReportPieChart(props) {

    const {t} = useTranslation();

    let report = null;
    if (props.reportData && props.reportData.length > 0) {
        let total = 0;
        let learningObjects = 0;
        let researchObjects = 0;
        let publicationRecords = 0;
        let datasets = 0;
        props.reportData.forEach(v => {
            total += v.repoItems.total
            learningObjects += v.repoItems.learningObjects
            researchObjects += v.repoItems.researchObjects
            publicationRecords += v.repoItems.publicationRecords
            datasets += v.repoItems.datasets
        })

        report = {}
        report.reportData = [
            {title: t("report.publication_records"), value: publicationRecords, color: '#E35F3C'},
            {title: t("report.learning_objects"), value: learningObjects, color: '#5AC4ED'},
            {title: t("report.research_objects"), value: researchObjects, color: '#FACD34'},
            {title: t("report.datasets"), value: datasets, color: '#7444ee'},
        ]
        report.totalItems = total;
    }

    function LegendItem(dataItem) {
        return (
            <div className={"legend-item"}>
                <div className={"legend-item-color-indicator"} style={{backgroundColor: dataItem.color}}/>
                <div className={"legend-item-title"}>
                    {dataItem.title}
                </div>
            </div>
        )
    }

    function PieChartLegend() {
        return (
            <div className={"pie-chart-legend"}>
                {
                    report.reportData.map((dataItem) => {
                        return (
                            <LegendItem key={dataItem.title}
                                        {...dataItem}/>
                        )
                    })
                }
            </div>
        )
    }

    if (report === null) {
        return (
            <div className={"report-pie-chart max-height"}>
                <LoadingIndicator/>
            </div>
        )
    }

    if (report.length === 0) {
        return (
            <div className={"report-pie-chart max-height"}>
                {t("report.report_empty")}
            </div>
        )
    }

    return (
        <div className={"report-pie-chart"}>
            <div className={"pie-chart-container"}>
                <PieChart
                    data={report.reportData}
                    lineWidth={20}
                    rounded
                />
                <div className={"pie-chart-title-container"}>
                    <h4 className={"pie-chart-title"}>{report.totalItems}</h4>
                    <div className={"pie-chart-subtitle"}>{t("report.report_publications")}</div>
                </div>
            </div>
            <PieChartLegend/>
        </div>
    )
}