import React, {useEffect, useRef, useState} from "react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faPlus, faTimes} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../../buttons/iconbuttontext/IconButtonText";
import Api from "../../../util/api/Api";
import {useTranslation} from "react-i18next";

export function TagField(props) {
    let classAddition = '';
    classAddition += (props.readonly ? ' disabled' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');

    const {t} = useTranslation();
    const editRef = useRef();
    const suggestionRef = useRef();
    const [tagFieldValue, setTagFieldValue] = useState(getInitialTagFieldValue());
    const [getOptionPrefixState, setGetOptionPrefixState] = useState(null);
    let currentlyEditingTag = editRef.current ? editRef.current.innerText : "";
    let downloadedOptions = [];
    let gettingOptions = false;
    let lastGetOptionPrefix = getOptionPrefixState;
    const hadError = useRef(false);

    useEffect(() => {
        return () => setGetOptionPrefixState(lastGetOptionPrefix)
    }, [])

    if (props.hasError) {
        hadError.current = true;
    }

    useEffect(() => {
        if (props.register) {
            props.register({name: props.name}, {required: props.isRequired})
            props.setValue(props.name, getRealTagFieldValue())
            hadError.current = false
        }
    }, [props.register])

    useEffect(() => {
        if (props.setValue) {
            props.setValue(props.name, getRealTagFieldValue(), {shouldValidate: hadError.current, shouldDirty: true})
        }
    }, [tagFieldValue])

    function getInitialTagFieldValue() {
        return props.defaultValue ? (props.defaultValue.map(value => props.options.find(option => value === option.value) ?? value)) : []
    }

    function getRealTagFieldValue() {
        if (tagFieldValue.length === 0) {
            return null;
        } else {
            return tagFieldValue;
        }
    }

    function removeTag(tag) {
        const index = tagFieldValue.indexOf(tag);
        if (index > -1) {
            const newListOfTags = tagFieldValue;
            newListOfTags.splice(index, 1);
            setTagFieldValue([...newListOfTags])
        }
        updateSuggestedTag()
    }

    //used for multiple new label tags
    function addTags(tags) {
        setTagFieldValue([...tagFieldValue, ...tags.map(t => {
            return {labelNL: t, labelEN: t}
        })])
    }

    //used for a single preexisting option tag
    function addTag(tag) {
        let newListOfTags;
        newListOfTags = [...tagFieldValue, tag];
        setTagFieldValue(newListOfTags)
    }

    function getSuggestion() {
        const relevantOptions = downloadedOptions.filter(o => {
            if (tagFieldValue.find((v) => {
                let tagValue = t('language.current_code') === 'nl' ? v.labelNL : v.labelEN
                let optionValue = t('language.current_code') === 'nl' ? o.labelNL : o.labelEN
                return o.id === v.value || tagValue === optionValue;
            })) {
                return false;
            }
            let label = t('language.current_code') === 'nl' ? o.labelNL : o.labelEN;
            label = label ?? '';
            label = label.replace(/ /g, '\u00a0')
            const ct = currentlyEditingTag.replace(/ /g, '\u00a0')
            return label.startsWith(ct)
        })

        if (relevantOptions.length > 0) {
            return relevantOptions[0];
        }

        return null;
    }

    function addCurrentlyEditingTagToTags() {
        if (currentlyEditingTag.length > 0) {
            const suggestion = getSuggestion();
            if (suggestion && editRef.current.innerText === suggestion.value) {
                addTag(suggestion);
            } else {
                const tagsToAdd = [];

                function addTagToTagsToAddIfPossible(part) {
                    part = part.trim()
                    if (part && part !== '' && !tagFieldValue.some(v => v.labelEN === part) && !tagsToAdd.some(t => part === t)) {
                        tagsToAdd.push(part)
                    }
                }

                currentlyEditingTag.split(';').forEach(
                    partToSplit => partToSplit.split(',').forEach(addTagToTagsToAddIfPossible)
                )
                addTags(tagsToAdd)
            }
            editRef.current.innerText = "";

            updateSuggestedTag()
        }
    }

    function handleKeyUp(e) {
        if (e.keyCode === 39) { //arrow right
            e.preventDefault();
            const suggestion = getSuggestion();
            if (suggestion !== null) {
                editRef.current.innerText = suggestion.value
                placeCaretAtEnd(editRef.current);
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            addCurrentlyEditingTagToTags()
        }
        currentlyEditingTag = editRef.current.innerText;
        updateSuggestedTag();
    }

    function placeCaretAtEnd(el) {
        el.focus();
        if (typeof window.getSelection != "undefined"
            && typeof document.createRange != "undefined") {
            const range = document.createRange();
            range.selectNodeContents(el);
            range.collapse(false);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        } else if (typeof document.body.createTextRange != "undefined") {
            const textRange = document.body.createTextRange();
            textRange.moveToElementText(el);
            textRange.collapse(false);
            textRange.select();
        }
    }

    function updateSuggestedTag() {
        const suggestion = getSuggestion();
        if (suggestion) {
            suggestionRef.current.innerText = getSuggestedTagUniqueEnd(suggestion);
        } else {
            suggestionRef.current.innerText = "";
            if (!gettingOptions && currentlyEditingTag !== lastGetOptionPrefix) {
                getOptions();
            }
        }
    }

    function getSuggestedTagUniqueEnd(suggestedOption) {
        if (!suggestedOption) {
            return "";
        }

        if (editRef.current.innerText.length > 0) {
            const title = t('language.current_code') === 'nl' ? suggestedOption.labelNL : suggestedOption.labelEN;
            const suggestionEnd = title.substr(editRef.current.innerText.length, title.length - (editRef.current.innerText.length - 1));
            return suggestionEnd.replace(/ /g, '\u00a0');
        }

        return "";
    }

    return <div className={"tagfield-wrapper"}>
        {!props.readonly && <div className={'tag-field-input-wrapper'}>
            <div className={"field-input tag" + classAddition} onClick={e => {
                if (editRef.current !== document.activeElement) {
                    placeCaretAtEnd(editRef.current);
                }
            }}>
                <div
                    className={"edit-input"}
                    contentEditable={true}
                    suppressContentEditableWarning={true}
                    ref={editRef}
                    onKeyUp={handleKeyUp}>{currentlyEditingTag}</div>
                <div
                    className={"edit-suggestion"}
                    ref={suggestionRef}>
                    {getSuggestedTagUniqueEnd(getSuggestion())}
                </div>
            </div>
            <IconButtonText faIcon={faPlus} onClick={() => {
                addCurrentlyEditingTagToTags();
                currentlyEditingTag = editRef.current.innerText;
                updateSuggestedTag();
            }}/>
        </div>}

        {props.readonly && !(tagFieldValue && tagFieldValue.length > 0) &&
        <div className={"field-input readonly"}>{"-"}</div>}
        <div className={'tag-chip-wrapper'}>
            {tagFieldValue.map((v, i) => {
                return <div className='tag-chip' key={i}>
                    {t('language.current_code') === 'nl' ? v.labelNL : v.labelEN}
                    {!props.readonly &&
                    <FontAwesomeIcon className={'remove-icon'} icon={faTimes} onClick={() => removeTag(v)}/>}
                </div>
            })}
        </div>
    </div>

    function getOptions() {

        function onValidate(response) {
        }

        function onSuccess(response) {
            gettingOptions = false;
            if (response.data.length > 0) {
                downloadedOptions = response.data;
                updateSuggestedTag();
            }
        }

        function onFailure(error) {
            gettingOptions = false;
        }

        const config = {
            params: {
                'filter[FieldKey][EQ]': props.name,
                'filter[Value][LIKE BINARY]': currentlyEditingTag + '%',
                'filter[IsRemoved][EQ]': 0,
                'sort': 'characterCount',
                'page[size]': 10,
                'page[number]': 1,
            }
        };

        Api.jsonApiGet('metaFieldOptions', onValidate, onSuccess, onFailure, onFailure, config);
        lastGetOptionPrefix = currentlyEditingTag;
        gettingOptions = true;
    }
}