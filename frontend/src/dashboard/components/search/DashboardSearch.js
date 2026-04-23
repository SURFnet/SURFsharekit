import React, {useEffect, useRef, useState} from "react"
import IconButton from "../../../components/buttons/iconbutton/IconButton";
import {faCross, faSearch, faTimes} from "@fortawesome/free-solid-svg-icons";
import './dashboardsearch.scss'
import styled from "styled-components";

function DashboardSearch(props) {
    const [showSearchInput, setShowSearchInput] = useState(false)
    const [searchInputValue, setSearchInputValue] = useState("")
    const [showCancelButton, setShowCancelButton] = useState(false)
    const [isCanceling, setIsCanceling] = useState(false)
    const inputRef = useRef(null)

    useEffect(() => {
        if (inputRef?.current) {
            if (showSearchInput) {
                inputRef.current.focus()
            } else {
                inputRef.current.blur()
            }
        }
    }, [inputRef, showSearchInput]);

    useEffect(() => {
        if (searchInputValue === "") {
            if (isCanceling) {
                submit()
                setIsCanceling(false)
            }
            setShowCancelButton(false)
        } else {
            setShowCancelButton(true)
        }
    }, [searchInputValue]);

    const toggleShowSearchInput = () => {
        setShowSearchInput(!showSearchInput)
    }

    function cancelSearchInput() {
        setSearchInputValue("")
        setIsCanceling(true)
    }

    const onEnter = (e) => {
        if (e.key === "Enter") {
            submit()
        }
    }

    const submit = () => {
        if (props.onSearchChange) {
            props.onSearchChange(inputRef.current?.value)
        }
    }

    return (
        <div className={'search-wrapper'}>
            <input
                type={"text"}
                className={`search-input ${showSearchInput && 'visible'}`}
                ref={inputRef}
                onKeyUp={onEnter}
                value={searchInputValue}
                onChange={(event) => {
                    setSearchInputValue(event.target.value)
                }}
                disabled={!showSearchInput}
            />
            <div className={'search-button-wrapper'}>
                {!showCancelButton && <IconButton className={'search-button'} icon={faSearch} onClick={toggleShowSearchInput} />}
                {showCancelButton && <IconButton className={'search-button'} icon={faTimes} onClick={cancelSearchInput} />}
            </div>
        </div>
    )
}

export default DashboardSearch