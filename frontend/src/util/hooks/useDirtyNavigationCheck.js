// import {useContext, useEffect} from "react";
// import VerificationPopup from "../../verification/VerificationPopup";
// import {useTranslation} from "react-i18next";
// import {useNavigation} from "../../providers/NavigationProvider";
// import {UNSAFE_NavigationContext} from "react-router-dom";
//
// export const useDirtyNavigationCheck = (history, dirtyFields) => {
//     const {t} = useTranslation();
//     let confirmed = false;
//     const navigate = useNavigation()
//     const navigator = useContext(UNSAFE_NavigationContext).navigator;
//
//     useEffect(() => {
//         navigator.
//         const unblock = history.block((location, action) => {
//             const isDirty = dirtyField
//             s && Object.keys(dirtyFields).length > 0
//             if (isDirty && !confirmed) {
//                 showVerificationPopup(location);
//                 return falsedasd
//             }
//             return true;
//         });
//
//         return () => {
//             unblock();
//         };
//     },[dirtyFields])
//
//     function showVerificationPopup(location) {
//         console.log(location)
//         console.log(location.location.pathname)
//         return VerificationPopup.show(t("verification.unsaved_changes.title"), t("verification.unsaved_changes.subtitle"), () => {
//             confirmed = true
//             if (location.action === "POP") {
//                 navigate("/publications")
//             }
//         })
//     }
// };
//
// // export const useDirtyNavigationCheck = (isDirty) => {
// //     const { t } = useTranslation();
// //     const confirmedRef = useRef(false);
// //
// //     // Create a stable blocker function with useCallback
// //     const handleBlockNavigation = useCallback((tx) => {
// //         console.log("Handling navigation block, isDirty:", isDirty, "confirmed:", confirmedRef.current);
// //
// //         if (isDirty && !confirmedRef.current) {
// //             VerificationPopup.show(
// //                 t("verification.unsaved_changes.title"),
// //                 t("verification.unsaved_changes.subtitle"),
// //                 () => {
// //                     console.log("User confirmed navigation");
// //                     confirmedRef.current = true;
// //                     tx.retry();
// //                 },
// //                 // Add cancel handler to reset state if user cancels
// //                 () => {
// //                     console.log("User cancelled navigation");
// //                     confirmedRef.current = false;
// //                 }
// //             );
// //         } else {
// //             console.log("No blocking needed, proceeding with navigation");
// //             tx.retry();
// //         }
// //     }, [isDirty, t]);
// //
// //     useBlocker(handleBlockNavigation, isDirty);
// //
// //     // Reset the confirmed state when the component unmounts or when isDirty changes
// //     useEffect(() => {
// //         return () => {
// //             confirmedRef.current = false;
// //         };
// //     }, [isDirty]);
// // };
// // export const useDirtyNavigationCheck = (isDirty) => {
// //     const { t } = useTranslation();
// //     const confirmedRef = useRef(false);
// //
// //     useBlocker((tx) => {
// //         if (isDirty && !confirmedRef.current) {
// //             VerificationPopup.show(
// //                 t("verification.unsaved_changes.title"),
// //                 t("verification.unsaved_changes.subtitle"),
// //                 () => {
// //                     confirmedRef.current = true;
// //                     tx.retry();
// //                 }
// //             );
// //         } else {
// //             tx.retry();
// //         }
// //     }, isDirty);
// // };
//
// // export const useDirtyNavigationCheck = (isDirty) => {
// //     const { t } = useTranslation();
// //     let confirmed = false;
// //
// //     useBlocker((tx) => {
// //         if (isDirty && !confirmed) {
// //             VerificationPopup.show(
// //                 t("verification.unsaved_changes.title"),
// //                 t("verification.unsaved_changes.subtitle"),
// //                 () => {
// //                     confirmed = true;
// //                     tx.retry();
// //                 }
// //             );
// //         } else {
// //             tx.retry();
// //         }
// //     }, isDirty);
// //
// //     function showVerificationPopup(location) {
// //         return VerificationPopup.show(t("verification.unsaved_changes.title"), t("verification.unsaved_changes.subtitle"), () => {
// //             confirmed = true
// //             history.push({pathname: location.pathname})
// //         })
// //     }
// // };