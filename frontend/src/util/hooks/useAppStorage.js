import { useEffect, useState } from "react";
import AppStorage from "../AppStorage";

function getValueFromLocalStorage(storageKey, defaultValue) {
    const value = AppStorage.get(storageKey)
    if (value) {
        return value;
    } else {
        return defaultValue;
    }
}

// experimental, do not use
export default function useAppStorage(storageKey, defaultValue = null) {
    const [value, setValue] = useState(getValueFromLocalStorage(storageKey, defaultValue));

    function handleAppStorageChange(event) {
        setValue(getValueFromLocalStorage(storageKey, defaultValue));
    }

    useEffect(() => {
        window.addEventListener(storageKey, handleAppStorageChange);
        return () => window.removeEventListener(storageKey, handleAppStorageChange);
    }, []);

    return value;
}