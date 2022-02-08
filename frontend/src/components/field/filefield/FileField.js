import React, {useEffect, useRef, useState} from "react";
import './filefield.scss'
import Constants from '../../../sass/theme/_constants.scss'
import {faFile, faTrash} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import ButtonText from "../../buttons/buttontext/ButtonText";
import {useTranslation} from "react-i18next";
import Toaster from "../../../util/toaster/Toaster";

export function FileField(props) {
    const {t} = useTranslation();
    const hiddenInputFileRef = useRef();
    const [file, setFile] = useState((props.file && props.file[0]) ? props.file[0] : null);
    const [serverFile, setServerFile] = useState((props.defaultValue && props.defaultValue[0]) ? props.defaultValue[0] : null)

    function getFileTitle() {
        if (file) {
            if (file.name) {
                return file.name
            }
        } else if (serverFile) {
            if (serverFile.summary.title) {
                return serverFile.summary.title
            }
        }
        return null
    }

    const hasFile = getFileTitle() !== null
    const fileTitleStyle = {
        color: hasFile ? "initial" : Constants.textColorError
    }

    useEffect(() => {
        props.register({name: props.name})
        props.setValue(props.name, file)
    }, [props.register, file])

    const handleNewFileUploadClick = () => {
        hiddenInputFileRef.current.click()
    }

    const fileChanged = (event) => {
        if (event && event.target && event.target.files && event.target.files.length > 0) {
            const changedFile = event.target.files[0];
            const sizeInMB = changedFile.size / 1000000;
            if (sizeInMB > 500) {
                Toaster.showDefaultRequestError(t('error_message.file_too_large'));
                return;
            }
            setFile(changedFile);
            props.onChange(changedFile);
        }
    }

    const handleDeleteFileClick = () => {
        setFile(null)
        setServerFile(null)
        props.onChange(null);
    }

    return (
        <div className={"file-field-container"} ref={props.register}>
            <div className={"file-container"}>
                <FontAwesomeIcon icon={faFile}/>
                <div className={"file-title"} style={fileTitleStyle}>
                    {hasFile ? getFileTitle() : t("attachment_popup.no_file")}
                </div>
            </div>
            <div className={"trash-icon-wrapper"} onClick={handleDeleteFileClick}>
                <FontAwesomeIcon icon={faTrash}/>
            </div>
            <div className={"new-file-upload-button"}>
                <ButtonText text={"Nieuwe upload"} className={"upload-file-button"} onClick={handleNewFileUploadClick}/>
            </div>
            <input type="file"
                   ref={hiddenInputFileRef}
                   onChange={fileChanged}
                   style={{display: "none"}}/>
        </div>
    )
}