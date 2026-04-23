import { useEffect } from 'react'

function useDocumentTitle(title, prevailOnUnmount = false) {
    const defaultTitle = "SURFsharekit"

    useEffect(() => {
        document.title = title;
    }, [title]);

    useEffect(() => () => {
        if (!prevailOnUnmount) {
            document.title = defaultTitle;
        }
    }, [prevailOnUnmount])

    return title;
}

export default useDocumentTitle