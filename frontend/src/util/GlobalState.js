import {useEffect, useState} from "react";

class GlobalState {
    static values = [];
    static subscribers = [];

    static get(subscribeKey) {
        return this.values[subscribeKey] ? this.values[subscribeKey] : null;
    };

    static set(subscribeKey, value) {
        if (value === false || value) {
            this.values[subscribeKey] = value
            if (this.subscribers[subscribeKey]) {
                this.subscribers[subscribeKey].forEach(method => method(this.get(subscribeKey)))
            }
        } else {
            this.values[subscribeKey] = undefined
        }
    };

    static remove(subscribeKey) {
        if (this.isAvailable(subscribeKey)) {
            this.values[subscribeKey] = undefined
        }

        if (this.subscribers[subscribeKey]) {
            this.subscribers[subscribeKey].forEach(method => method(null))
        }
    };

    static isAvailable(subscribeKey) {
        return !!this.values[subscribeKey];
    }


    static subscribe(subscribeKey, method) {
        if (!this.subscribers[subscribeKey]) {
            this.subscribers[subscribeKey] = [];
        }

        this.subscribers[subscribeKey].push(method);
    }

    static unsubscribe(subscribeKey, method) {
        if (!this.subscribers[subscribeKey]) {
            return;
        }

        const index = this.subscribers[subscribeKey].indexOf(method);
        if (index > -1) {
            this.subscribers[subscribeKey].splice(index, 1);
        }
    }
}

export function useGlobalState(subscribeKey, defaultGlobalValue) {
    const [state, setState] = useState(GlobalState.get(subscribeKey));

    if (defaultGlobalValue && !GlobalState.isAvailable(subscribeKey)) {
        GlobalState.set(subscribeKey, defaultGlobalValue)
    }

    function onExternalStateChange(newStateIncomming) {
        setState(newStateIncomming);
    }

    useEffect(() => {
        GlobalState.subscribe(subscribeKey, onExternalStateChange);
        return () => {
            GlobalState.unsubscribe(subscribeKey, onExternalStateChange);
        };
    }, []);

    function setStateInSubscribers(newStateOutgoing) {
        GlobalState.set(subscribeKey, newStateOutgoing);
    }

    return [state, setStateInSubscribers];
}

export default GlobalState;
