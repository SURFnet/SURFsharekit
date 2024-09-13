import {useEffect} from "react";

/**
 * Hook that alerts clicks outside of the passed ref
 */
export function useInsideElementClicked(callback, ref) {
    useEffect(() => {
        /**
         * Alert if clicked on outside of element
         */
        function handleClickInside(event) {
            if (ref.current && ref.current.contains(event.target)) {
                callback();
            }
        }

        // Bind the event listener
        document.addEventListener("mousedown", handleClickInside);
        return () => {
            // Unbind the event listener on clean up
            document.removeEventListener("mousedown", handleClickInside);
        };
    }, [ref]);
}