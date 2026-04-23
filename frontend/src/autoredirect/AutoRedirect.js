import React from "react";
import {Navigate} from "react-router-dom";
import {StorageKey, useAppStorageState} from "../util/AppStorage";

function AutoRedirect() {
    const [user] = useAppStorageState(StorageKey.USER);
    if (!user) {
        return <Navigate to={'/401'}/>
    }
    //else if(noPermissionToViewPage){
        //return <Redirect to={'/403'}/>
    //}

    return null;
}

export default AutoRedirect;