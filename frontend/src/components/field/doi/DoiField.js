import React, { useEffect, useState } from "react";
import ButtonText from "../../buttons/buttontext/ButtonText";
import { useTranslation } from "react-i18next";
import Api from "../../../util/api/Api";
import { GlobalPageMethods } from "../../page/Page";
import Toaster from "../../../util/toaster/Toaster";
import VerificationPopup from "../../../verification/VerificationPopup";

export function DoiField(props) {
    let classAddition = '';
    classAddition += (props.readonly ? ' disabled readonly-hidden' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');
    let oldValue = props.defaultValue;
    const [generatedDoi, setGeneratedDoi] = useState(oldValue ? oldValue : null);
    const {t} = useTranslation();

    function validateWithRegex(value, validationRegex) {
        if (!validationRegex) {
            return true;
        }

        if (generatedDoi) {
            return true;
        }

        if (!value && !props.isRequired) {
            return true
        }

        return RegExp(validationRegex).test(value);
    }

    let readonlyValue = null
    if (props.readonly) {
        readonlyValue = (props.defaultValue && props.defaultValue.length > 0) ? props.defaultValue : "-"
    }

    let canCreateNewDoi = false
    if (props.repoItem !== null && props.repoItem !== undefined) {
        const permissions = props.repoItem.permissions
        if (permissions !== null && permissions !== undefined) {
            canCreateNewDoi = permissions.canGenerateDoi === true
        }
    }

    function generateDoi() {
        VerificationPopup.show(t("publication.generate_doi_confirmation.title"), t("publication.generate_doi_confirmation.subtitle"), () => {
            doGenerateDoi()
        })
    }

    function doGenerateDoi() {
        GlobalPageMethods.setFullScreenLoading(true)

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false)
            const parsedDoi = Api.dataFormatter.deserialize(response.data);
            props.register({
                name: props.name
            }, {
                required: props.isRequired,
                validate: (v => validateWithRegex(v, props.validationRegex))
            })
            props.setValue(props.name, parsedDoi.doi)
            setGeneratedDoi(parsedDoi.doi)
        }

        function onLocalFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showDefaultRequestError()
        }

        function onServerFailure(error) {
            GlobalPageMethods.setFullScreenLoading(false)
            Toaster.showServerError(error)
        }

        Api.get('repoItems/' + props.repoItem.id + '/doi', onValidate, onSuccess, onLocalFailure, onServerFailure);
    }

    return (
        <div className={"field-input-wrapper"}>
            {props.readonly && <div className={"field-input text readonly"}>
                {props.repoItem.status === 'Published' && props.defaultValue ? <a href={`/public/${props.repoItem.id}`} target={'__blank'} className={"doi-link"}>{readonlyValue}</a> : readonlyValue}
            </div>}

            <input type="text"
                 key={'a'}
                 disabled={props.readonly || generatedDoi !== null}
                 className={`field-input text ${generatedDoi && 'readonly'}` + classAddition}
                 defaultValue={props.defaultValue}
                 placeholder={props.placeholder}
                 size={1}
                 onChange={(e) => {
                     props.setValue(props.name, e.target.value)
                     if (props.onValueChangedUnchecked) {
                         props.onValueChangedUnchecked(e)
                     }
                     if ((!oldValue || oldValue.length === 0) && e.target.value.length > 0) {
                         props.onChange(e)
                     } else if ((oldValue && oldValue.length > 0) && e.target.value.length === 0) {
                         props.onChange(e)
                     }

                     oldValue = e.target.value;
                 }}
                 ref={props.register && props.register({
                     required: props.isRequired,
                     validate: (v => validateWithRegex(v, props.validationRegex))
                 })}
                 name={props.name}
                 onClick={(e) => {
                     e.stopPropagation()
                 }}
            />

            {(canCreateNewDoi && !props.readonly && generatedDoi == null) && <div className={"field-button"}>
                <ButtonText text={t('action.create_new_doi')}
                            buttonType={"callToAction"}
                            onClick={() => {
                                generateDoi();
                            }}/>
            </div>
            }
        </div>
    )
}