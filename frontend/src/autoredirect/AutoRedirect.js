import React, {useState} from "react";
import {Redirect} from "react-router-dom";
import AppStorage, {StorageKey, useAppStorageState} from "../util/AppStorage";

function AutoRedirect() {
    const [user] = useAppStorageState(StorageKey.USER);
    if (!user) {
        return <Redirect to={'/401'}/>
    }
    //else if(noPermissionToViewPage){
        //return <Redirect to={'/403'}/>
    //}

    return null;
}

export default AutoRedirect;