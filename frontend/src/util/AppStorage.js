import {useEffect, useState} from "react";

export const StorageKey = {
    USER: 'user',
    USER_PERMISSIONS: 'userPermissions',
    USER_ROLES: 'userRoles',
    USER_CAN_VIEW_ORGANISATION: 'userCanViewOrganisation',
    USER_CAN_VIEW_TEMPLATES: 'userCanViewTemplates',
    USER_CAN_VIEW_PERSONS: 'userCanViewPersons',
    USER_INSTITUTE: 'userInstitute',
    LANGUAGE_LOCALE: 'languageLocale',
    STATE_REDIRECT: 'stateRedirect',
    STATE_NEEDS_ACCESSTOKEN: 'stateAccessToken'
};

class AppStorage {
    static subscribers = [];

    static get(storageKey) {
        const storageValue = localStorage.getItem(storageKey);
        return storageValue ? JSON.parse(storageValue) : null;
    };

    static set(storageKey, storageValue) {
        if (storageValue) {
            localStorage.setItem(storageKey, JSON.stringify(storageValue, getCircularJsonReplacer()));

            if (this.subscribers[storageKey]) {
                this.subscribers[storageKey].forEach(method => method(this.get(storageKey)))
            }
        } else {
            this.remove(storageKey);
        }
    };

    static remove(storageKey) {
        if (this.isAvailable(storageKey)) {
            localStorage.removeItem(storageKey);
        }

        if (this.subscribers[storageKey]) {
            this.subscribers[storageKey].forEach(method => method(null))
        }
    };

    static isAvailable(key) {
        return localStorage.getItem(key) !== null;
    }


    static subscribe(storageKey, method) {
        if (!this.subscribers[storageKey]) {
            this.subscribers[storageKey] = [];
        }

        this.subscribers[storageKey].push(method);
    }

    static unsubscribe(storageKey, method) {
        if (!this.subscribers[storageKey]) {
            return;
        }

        const index = this.subscribers[storageKey].indexOf(method);
        if (index > -1) {
            this.subscribers[storageKey].splice(index, 1);
        }
    }
}

//https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Errors/Cyclic_object_value
const getCircularJsonReplacer = () => {
    const seen = new WeakSet();
    return (key, value) => {
        if (typeof value === "object" && value !== null) {
            if (seen.has(value)) {
                return;
            }
            seen.add(value);
        }
        return value;
    };
};


export function useAppStorageState(storageKey) {
    const [state, setState] = useState(AppStorage.get(storageKey));

    function onExternalStateChange(newStateIncomming) {
        setState(newStateIncomming);
    }

    useEffect(() => {
        AppStorage.subscribe(storageKey, onExternalStateChange);
        return () => {
            AppStorage.unsubscribe(storageKey, onExternalStateChange);
        };
    }, []);

    function setStateInAppStorage(newStateOutgoing) {
        AppStorage.set(storageKey, newStateOutgoing);
    }

    return [state, setStateInAppStorage];
}

export default AppStorage;
