import React, {useCallback, useEffect, useMemo, useState} from "react";
import {useTranslation} from "react-i18next";
import {Icon, TaskHeaderIcon} from "../../dashboard/Dashboard";
import styled from "styled-components";
import IconReports from "../../resources/icons/ic-statistics.svg";
import Select from "react-select";
import '../../../src/components/dropdown/dropdown.scss';
import '../reports.scss';
import '../../../src/components/field/datepicker/datepicker.scss'
import {
    cultured,
    flame,
    greyLight,
    majorelleLight,
    maximumYellowLight,
    oceanGreenLight,
    vividSky,
    vividSkyLight
} from "../../Mixins";
import CircleChart from "../../components/charts/circle-chart/CircleChart";
import IconShare from "../../../src/resources/icons/ic-share.svg"
import {ExpandableList} from "../../organisation/ExpandableList";
import {useForm} from "react-hook-form";
import {Form, FormField, Label} from "../../components/field/FormField";
import {data} from '../../../src/util/api/mock';
import {GlobalPageMethods} from "../../components/page/Page";
import Api from "../../util/api/Api";
import Toaster from "../../util/toaster/Toaster";
import LoadingIndicator from "../../components/loadingindicator/LoadingIndicator";
import DatePicker from "../../components/field/datepicker/DatePicker";
import AppStorage, {StorageKey} from "../../util/AppStorage";
import {getMember} from "../../organisation/OrganisationExpandableList";
import {HelperFunctions} from "../../util/HelperFunctions";
import {useNavigate} from "react-router-dom";
import ReportsDatePicker from "./reportsDatepicker/ReportsDatepicker";
import {useNavigation} from "../../providers/NavigationProvider";

function ReportsDashboard() {
    const [memberInstitutes, setMemberInstitutes] = useState([])
    const user = AppStorage.get(StorageKey.USER)
    const navigate = useNavigation();
    const [instituteData, setInstituteData] = useState([]);
    const [loading, setLoading] = useState(false);
    const [selectedOption, setSelectedOption] = useState(
        memberInstitutes?.length ? memberInstitutes[0] : null
    );

    const [dateFrom, setDateFrom] = useState(null);
    const [dateUntil, setDateUntil] = useState(null);

    useEffect(() => {
        setLoading(true)
        getMember(user.id, navigate, false, (member) => {
            setLoading(false)
            setMemberInstitutes(HelperFunctions.getMemberRootInstitutes(member))
        }, (error) => {
            setLoading(false)
            Toaster.showServerError(error)
        })
    }, [])

    useEffect(() => {
        if (memberInstitutes && memberInstitutes.length > 0) {
            getInstituteDataSummary(memberInstitutes[0].id, dateFrom, dateUntil);
            setSelectedOption(memberInstitutes[0])
        }
    }, [memberInstitutes])

    const getInstituteDataSummary = (instituteId, dateFrom, dateUntil) => {
        setLoading(true)

        const instituteDashboardDataConfig = {
            params: {
                'instituteId': instituteId,
                'from': dateFrom ?? null,
                'until': dateUntil ?? null,
            }
        };

        Api.get('instituteDashboardSummaries',
            () => {},
            (response) => {
                console.log(response)
                setInstituteData(response);
                setSelectedOption(memberInstitutes.find(institute => institute.id === instituteId))
            },
            (error) => {
                Toaster.showServerError(error)
            },
            (error) => {
                Toaster.showServerError(error)
                console.log(error)
                if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                    navigate('/login?redirect=' + window.location.pathname);
                }
            },
            instituteDashboardDataConfig);

        setLoading(false)
    }

    return (
        <>
            <ReportsHeader
                selectedOption={selectedOption}
                setInstituteData={(instituteData) => {
                    getInstituteDataSummary(instituteData, null, null)
                }}
                institutes={memberInstitutes}
                setFieldValues={(type, value) => {
                    if (type === 'dateFrom') {
                        setDateFrom(value);
                        getInstituteDataSummary(selectedOption.id, value, dateUntil);
                    } else if (type === 'dateUntil') {
                        setDateUntil(value);
                        getInstituteDataSummary(selectedOption.id, dateFrom, value);
                    }
                }}
                dateUntil={dateUntil}
                dateFrom={dateFrom}
            />

            { loading ? (
                <LoadingIndicator />
            ) : (
                <>
                    { instituteData?.data && (
                        <ReportsStatistics
                            data={instituteData.data}
                        />)
                    }

                    <ReportsDownloads
                        data={instituteData.data}
                    />

                    {selectedOption !== null &&
                        <ReportsOrganogram
                            data={memberInstitutes.filter(institute => institute.id === selectedOption.id)}
                        />
                    }
                </>
            )}
        </>
    )
}

const ReportsHeader = ({...props}) => {
    const {t} = useTranslation();

    const Header = styled.div` 
        display: flex;
        justify-content: space-between;
        align-items: center;
        
        div:nth-child(1){
            margin-bottom: 0 !important; 
        }
    `
    const HeaderFilters = styled.div`
        display: flex;
        gap: 10px;
        align-items: center;
        position: relative;
        bottom: 10px;
    `

    const ColumnFlex = styled.div`
        display: flex;
        flex-direction: column;
        gap: 5px;
        
        label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        ${FormField}{
            height: 50px !important;
        }
    `

    return (
        <Header>
            <div className={"reports-title-row"}>
                <TaskHeaderIcon width={"82px"} height={"82px"}>
                    <Icon src={IconReports} width={"26px"}/>
                </TaskHeaderIcon>
                <h1>{t("report.tabs.dashboard.title")}</h1>
            </div>
            <HeaderFilters>
                <ColumnFlex>
                    <Label text={t("report.filters.organisation")} />
                    <Select
                        key={props.selectedOption}
                        defaultValue={props.selectedOption}
                        getOptionValue={option => (option.title)}
                        options={props.institutes.map(option => {return option})}
                        className={"surf-dropdown"}
                        classNamePrefix={"surf-select"}
                        formatOptionLabel={option => (
                            <div className={"align-center"}>
                                <span style={{fontSize: "12px"}}>{option.title}</span>
                            </div>
                        )}
                        onChange={(selection) => {
                            props.setInstituteData(selection.id)
                        }}
                    />
                </ColumnFlex>

                <ColumnFlex>
                    <Label className={"add-margin"} text={t("report.filters.from")} />
                    <ReportsDatePicker
                        key={"dateFrom"}
                        isRequired={false}
                        type={"datepicker"}
                        name={"dateFrom"}
                        defaultValue={props.dateFrom ?? null}
                        onChange={(value) => props.setFieldValues('dateFrom', value)}
                    />
                </ColumnFlex>
                <ColumnFlex>
                    <Label className={"add-margin"} text={t("report.filters.until")} />
                    <ReportsDatePicker
                        key={"dateUntil"}
                        isRequired={false}
                        type={"datepicker"}
                        name={"dateUntil"}
                        defaultValue={props.dateUntil ?? null}
                        onChange={(value) => props.setFieldValues('dateUntil', value)}
                    />
                </ColumnFlex>
            </HeaderFilters>
        </Header>
    )
}

const ReportsStatistics = ({...props}) => {
    const {t} = useTranslation();

    const Container = styled.section`
        margin-top: 50px;
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        grid-column-gap: 20px;
        grid-row-gap: 40px;

        @media (max-width: 1000px) {
            grid-template-columns: 1fr;
            grid-template-rows: auto;
            grid-row-gap: 20px;

            ${Item},
            ${GraphItem} {
                grid-column: span full;
            }
        }
    `;

    return (
        <Container>
            <Item data={props.data?.publicationCount} subtitle={t("report.imported_publications")} />
            <Item data={props.data?.downloads} subtitle={t("report.downloads")} />
            <Item data={props.data?.activeUsers} subtitle={t("report.active_users")} />
            <GraphItem data={props.data?.publicationTypes} colors={[flame, vividSky, maximumYellowLight, majorelleLight]} title={t("report.type_publications")} subtitle={t('language.current_code') === 'nl' ? props.data.labelNL : props.data.labelEN}></GraphItem>
            <GraphItem data={props.data?.publicationStatuses} colors={[oceanGreenLight, majorelleLight, vividSkyLight, maximumYellowLight, cultured]} title={t("report.status_publications")} subtitle={t('language.current_code') === 'nl' ? props.data.labelNL : props.data.labelEN}></GraphItem>
        </Container>
    )
}

export const Item = ({...props}) => {

    const formattedNumber = new Intl.NumberFormat('de-DE').format(props.data);

    const ItemContainer = styled.div`
        display: flex;
        flex-direction: column;
        padding: 25px 35px;
        justify-content: center;
        align-items: center;
        gap: 6px;
        grid-column: span 2;
        height: 120px;
        background: white;
        border-radius: 2px 20px 20px 20px;
        
        h3 {
            font-weight: 700;
        }

        hr {
            border: solid 0.5px ${greyLight};
            width: 100%;
        }
        
        p {
            font-size: 12px;
            margin: 0;
        }
    `

    return (
        <ItemContainer>
            <h3>{formattedNumber}</h3>
            <hr/>
            <p>{props.subtitle}</p>
        </ItemContainer>
    )
}

export const GraphItem = ({data = [], ...props}) => {

    const {t} = useTranslation()

    const Container = styled.div`
        display: flex;
        flex-direction: column;
        padding: 28px;
        gap: 6px;
        grid-column: span 3;
        height: 430px;
        background: white;
        border-radius: 2px 20px 20px 20px;
    `

    const ChartContainer = styled.div`
        width: 100%;
        display: flex;
        gap: 20px;
        align-items: center;
    `

    const ChartNumbers = styled.div`
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        font-size: 12px;
        left: 5px;
        bottom: 170px;
    `

    const LegendContainer = styled.div`
        display: flex;
        flex-direction: column;
        gap: 40px;
        margin-bottom: 50px;
        
        div {
            font-size: 12px;
            display: flex;
            gap: 5px;
            align-items: center;
            word-break: break-all;
        }
    `

    const LegendBox = styled.div`
        width: 15px; height: 15px; border-radius: 2px 5px 5px 5px;
        background-color: ${props => props.fill && props.fill};
    `

    const totalValue = data.reduce((accumulator, item) => {
        return accumulator + item.value;
    }, 0);

    const formattedNumber = new Intl.NumberFormat('de-DE').format(totalValue);

    return (
        <Container>
            <div>
                <h2>{props.title}</h2>
                <h5>{props.subtitle}</h5>
            </div>
            <ChartContainer>
                <div>
                    <CircleChart data={data} colors={props.colors} width={300} height={300}/>
                    <ChartNumbers>
                        <h3>{formattedNumber}</h3>
                        <span>{t("report.report_publications")}</span>
                    </ChartNumbers>
                </div>
                <LegendContainer>
                    {data.map((entry, index) => (
                        <div>
                            <LegendBox fill={props.colors[index]} />
                            {t('language.current_code') === 'nl' ? entry.labelNL : entry.labelEN}
                        </div>
                    ))}
                </LegendContainer>
            </ChartContainer>
        </Container>
    )
}

const ReportsDownloads = ({...props}) => {

    const {t} = useTranslation()

    const Container = styled.div`
        margin-top: 50px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    `

    const TableHeaders = styled.div`
        width: 100%;
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        padding-right: 50px;
    `

    const LeftSide = styled.div`
        display: flex;
        align-items: center;
        font-family: 'Open Sans', sans-serif;
        
        img {
            padding-left: 20px;
        } 
        
        span {
            font-size: 14px;
            font-weight: 600;
            padding-left: 15px;
        }
        
        div:nth-of-type(2){
            width: 150px;
            padding-left: 50px;
        }
    `

    const RightSide = styled.div`
        display: flex;
        width: 350px;
        justify-content: flex-end;
        gap: 50px;
        font-size: 12px;
        font-weight: 700;
        font-family: 'Open Sans', sans-serif;

        span {
            width: 80px;
        }
    `

    const TableRow = styled.div`
        background-color: white;
        height: 50px;
        border-radius: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-right: 50px;
    `

    return (
        <>
            { props.data?.downloadsPerChannel && props.data?.downloadsPerChannel.length > 0 &&
                <Container>
                    <h2>{t("report.downloads_per_channel")}</h2>
                    <TableHeaders>
                        <LeftSide>
                            <div></div>
                            <div>{t("report.channel")}</div>
                        </LeftSide>
                        <RightSide>
                            <span>{t("report.link")}</span>
                            <span>{t("report.file")}</span>
                            <span>{t("report.total")}</span>
                        </RightSide>
                    </TableHeaders>
                    {props.data?.downloadsPerChannel
                        .sort((a, b) => a.channel.localeCompare(b.channel))
                        .map(({ channel, total, download, link }) => {

                        const formatNumber = (num) => new Intl.NumberFormat('de-DE').format(num);

                        return (
                            <TableRow key={channel}>
                                <LeftSide>
                                    <img src={IconShare} alt={`Icon for ${channel}`} />
                                    <span>{channel === "unknown" ? t("report.unknown") : channel}</span>
                                </LeftSide>
                                <RightSide>
                                    <span>{formatNumber(download)}</span>
                                    <span>{formatNumber(link)}</span>
                                    <span>{formatNumber(total)}</span>
                                </RightSide>
                            </TableRow>
                        );
                    })}
                </Container>
            }
        </>
    )
}

const ReportsOrganogram = ({...props}) => {
    const Container = styled.div`
        display: flex;
        flex-direction: column;
        gap: 15px;
        width: 100%;
        margin-top: 50px;
    `

    const {t} = useTranslation()

    return (
        <Container>
            <h2>{t("report.total_publications_per_department")}</h2>
            <ExpandableList data={props.data}
                            showReportsData={true}
                            includeHeaders={[t("report.total")]}
            />
        </Container>
    )
}

export default ReportsDashboard
