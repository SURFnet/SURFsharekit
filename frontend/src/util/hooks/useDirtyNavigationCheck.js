import {useEffect} from "react";
import {useTranslation} from "react-i18next";
import VerificationPopup from "../../verification/VerificationPopup";

export const useDirtyNavigationCheck = (history, dirtyFields) => {
    const {t} = useTranslation();
    let confirmed = false;

    useEffect(() => {
        const unblock = history.block((location, action) => {
            const isDirty = dirtyFields && Object.keys(dirtyFields).length > 0
            if (isDirty && !confirmed) {
                showVerificationPopup(location);
                return false
            }
            return true;
        });

        return () => {
            unblock();
        };
    },[dirtyFields])

    function showVerificationPopup(location) {
        return VerificationPopup.show(t("verification.unsaved_changes.title"), t("verification.unsaved_changes.subtitle"), () => {
            confirmed = true
            history.push({pathname: location.pathname})
        })
    }
};