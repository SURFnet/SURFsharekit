import React, { useEffect, useState, useRef } from "react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faTimes } from "@fortawesome/free-solid-svg-icons";
import "./remediateprogresspopupcontent.scss";
import "../components/field/formfield.scss";
import { useTranslation } from "react-i18next";
import ProgressBar from "../components/progressbar/ProgressBar";
import {
    PopupProgressBarHolder,
    ProgressTitle,
} from "../components/field/relatedrepoitempopup/RelatedRepoItemContent";
import Api from "../util/api/Api";
import Toaster from "../util/toaster/Toaster";
import ButtonText from "../components/buttons/buttontext/ButtonText";
import styled from "styled-components";

export function RemediateProgressPopupContent(props) {
    const [progressPercentage, setProgressPercentage] = useState(0);
    const isCancelledRef = useRef(false);
    const { t } = useTranslation();

    useEffect(() => {
        if (progressPercentage >= 100) {
            Toaster.showToaster({ message: t("remediate.finished") });
            props.onCancel();
        }
    }, [progressPercentage]);

    useEffect(() => {
        updateProgress();
    }, []);

    let popupContent = (
        <div className={"remediate-layer-title"}>
            <h3>{t("report.remediate")}</h3>
            <PopupProgressBarHolder>
                <ProgressTitle>
                    {t("remediate.remediating", {
                        count: props.count,
                        repoType: props.repoType,
                        action: props.action,
                    }) + "..."}
                </ProgressTitle>
                <br />
                <ProgressBar height={"15px"} progress={progressPercentage} />
            </PopupProgressBarHolder>
            <br />
            <br />
            <ButtonHolder>
                <ButtonText
                    text={t("action.close")}
                    buttonType={"callToAction"}
                    onClick={() => {
                        props.onCancel();
                        isCancelledRef.current = true; // Set cancellation flag
                    }}
                />
            </ButtonHolder>
        </div>
    );

    return (
        <div className={"remediate-progress-popup-content-wrapper"}>
            <div className={"remediate-progress-popup-content"}>
                <div className={"close-button-container"} onClick={() => { props.onCancel(); isCancelledRef.current = true;}}>
                    <FontAwesomeIcon icon={faTimes} />
                </div>
                {popupContent}
            </div>
        </div>
    );

    function updateProgress() {
        if (isCancelledRef.current) {
            return;
        }

        function onValidate(response) {
        }

        function onSuccess(response) {
            const bulkAction = Api.dataFormatter.deserialize(response.data);

            if (bulkAction.totalCount <= 0) {
                setProgressPercentage(100);
            } else {
                const newProgressPercentage =
                    ((bulkAction.failCount + bulkAction.successCount) /
                        bulkAction.totalCount) *
                    100;
                setProgressPercentage(newProgressPercentage);

                if (
                    newProgressPercentage < 100 &&
                    !isCancelledRef.current
                ) {
                    setTimeout(updateProgress, 5000);
                }
            }
        }

        function onLocalFailure(error) {
            setTimeout(updateProgress, 5000);
        }

        function onServerFailure(error) {
            setTimeout(updateProgress, 5000);
        }

        if (progressPercentage < 100 && !isCancelledRef.current) {
            Api.get(
                "bulkactions/" + props.bulkActionId,
                onValidate,
                onSuccess,
                onLocalFailure,
                onServerFailure
            );
        }
    }
}

const ButtonHolder = styled.div`
  width: 100%;
  display: flex;
  flex-direction: row-reverse;
`;
