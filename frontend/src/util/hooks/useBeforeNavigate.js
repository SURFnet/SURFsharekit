import {useBlocker, useLocation} from "react-router-dom";
import {useEffect} from "react";

export const useBeforeNavigate = (callback: () => void) => {
    const location = useLocation();

    const blocker = useBlocker(
        ({ currentLocation, nextLocation }) =>
            currentLocation.pathname !== nextLocation.pathname &&
            currentLocation.pathname === location.pathname,
    );

    useEffect(() => {
        if (blocker.state === 'blocked') {
            callback?.();
            blocker.proceed?.();
        }
    }, [blocker]);
};