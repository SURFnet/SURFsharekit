import React, {useRef} from "react"
import './grouppermissiontable.scss'
import '../../components/field/formfield.scss'
import {useTranslation} from "react-i18next";
import {useForm} from "react-hook-form";
import ButtonText from "../../components/buttons/buttontext/ButtonText";
import Toaster from "../../util/toaster/Toaster";
import Api from "../../util/api/Api";
import {GlobalPageMethods} from "../../components/page/Page";
import {Tooltip} from "../../components/field/FormField";
import {useHistory} from "react-router-dom";

function GroupPermissionTable(props) {
    const {register, handleSubmit, errors, setValue, getValues, trigger} = useForm();
    const {t} = useTranslation();
    const formSubmitButton = useRef()
    const history = useHistory()
    let content = undefined;

    if (props.group) {
        content = Object.entries(props.group.codeMatrix).map((codeObject, i) => {
                const code = codeObject[0]
                const permissionObject = codeObject[1]

                const tooltip = t('group.permissiontooltips.' + code.toLowerCase());
                const showTooltip = ('group.permissiontooltips.' + code.toLowerCase()) !== tooltip && tooltip !== ""

                return <div className='permission-checkbox-row' key={i}>
                    <div className='checkbox-row-title'>
                        <PermissionTitle code={code}/>
                        {showTooltip && <Tooltip text={tooltip}/>}
                    </div>
                    <div className='checkbox-row-checkboxes'>
                        <PermissionCheckbox
                            register={register}
                            permissionCode={code}
                            permissionObject={permissionObject}
                            permissionType={'GENERATE DOI'}/>
                        <PermissionCheckbox
                            register={register}
                            permissionCode={code}
                            permissionObject={permissionObject}
                            permissionType={'PUBLISH'}/>
                        <PermissionCheckbox
                            register={register}
                            permissionCode={code}
                            permissionObject={permissionObject}
                            permissionType={'DELETE'}/>
                        <PermissionCheckbox
                            register={register}
                            permissionCode={code}
                            permissionObject={permissionObject}
                            permissionType={'CREATE'}/>
                        <PermissionCheckbox
                            register={register}
                            permissionCode={code}
                            permissionObject={permissionObject}
                            permissionType={'EDIT'}/>
                        <PermissionCheckbox
                            register={register}
                            permissionCode={code}
                            permissionObject={permissionObject}
                            permissionType={'VIEW'}/>
                    </div>
                </div>
            }
        )
    }

    return <div className='group-permission-table'>
        <h2>{t('group.permissions')}</h2>
        <div className='table-header'>
            <div className={'table-header-item'}>{t('group.permissiontypes.generate_doi')}</div>
            <div className={'table-header-item'}>{t('group.permissiontypes.publish')}</div>
            <div className={'table-header-item'}>{t('group.permissiontypes.delete')}</div>
            <div className={'table-header-item'}>{t('group.permissiontypes.create')}</div>
            <div className={'table-header-item'}>{t('group.permissiontypes.edit')}</div>
            <div className={'table-header-item'}>{t('group.permissiontypes.view')}</div>
        </div>
        <form id={"group-permission"}
              onSubmit={handleSubmit(savePermissions)}>
            {content}
            <div className={"save-button-wrapper"}>
                <button type="submit"
                        form="group-permission"
                        ref={formSubmitButton}
                        style={{display: "none"}}/>
                <ButtonText text={t('action.save')}
                            buttonType={"callToAction"}
                            onClick={() => {
                                formSubmitButton.current.click();
                            }}/>
            </div>
        </form>
    </div>

    function savePermissions(formData) {
        const selectedPermissions = Object.entries(formData).filter(permObj => permObj[1]).map(permObj => permObj[0])
        GlobalPageMethods.setFullScreenLoading(true);

        function onValidate(response) {
        }

        function onSuccess(response) {
            GlobalPageMethods.setFullScreenLoading(false);
            props.reloadGroup()
        }

        function onLocalFailure(error) {
            Toaster.showDefaultRequestError()
            GlobalPageMethods.setFullScreenLoading(false);
        }

        function onServerFailure(error) {
            Toaster.showServerError(error)
            GlobalPageMethods.setFullScreenLoading(false);
            if (error && error.response && error.response.status === 401) { //We're not logged, thus try to login and go back to the current url
                history.push('/login?redirect=' + window.location.pathname);
            }
        }

        const config = {
            headers: {
                "Content-Type": "application/vnd.api+json",
            }
        }

        const patchData = {
            "data": {
                "type": "group",
                "id": props.group.id,
                "attributes": {
                    "permissions": selectedPermissions
                }
            }
        };

        Api.patch(`groups/${props.group.id}`, onValidate, onSuccess, onLocalFailure, onServerFailure, config, patchData);
    }
}

function PermissionTitle(props) {
    const {t} = useTranslation()
    return t('group.permissioncodes.' + props.code.toLowerCase())
}

function PermissionCheckbox(props) {
    if (props.permissionObject[props.permissionType]) {
        const codeParts = props.permissionCode.split("_")
        const fullCode = codeParts[0] + "_" + props.permissionType + "_" + codeParts[1]

        const ref = props.permissionObject[props.permissionType]['fromRole'] ? undefined : props.register
        const isSet = props.permissionObject[props.permissionType]['isSet']
        const isEnabled = props.permissionObject[props.permissionType]['canEdit']

        return <div className={"checkbox"} datasrc={props.permissionType}>
            <div className={"option"}>
                <input
                    id={fullCode}
                    name={fullCode}
                    ref={ref}
                    defaultChecked={isSet}
                    disabled={!isEnabled}
                    type="checkbox"/>
                <label htmlFor={fullCode}/>
            </div>
        </div>
    } else {
        return <div className='checkbox' datasrc={props.permissionType}/>
    }
}

export default GroupPermissionTable;