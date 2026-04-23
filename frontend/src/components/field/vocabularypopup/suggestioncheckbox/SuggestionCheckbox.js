import React from 'react';
import {useTranslation} from "react-i18next";
import {SuggestionBoxContainer} from "./SuggestionCheckboxStyled";

/**
 * SuggestionCheckboxes - a checkbox selection function for generated options
 *
 * Features:
 * - Renders a list of checkboxes from provided suggestions
 * - Supports multiple language labels (English and Dutch)
 * - Controlled component with external state management
 *
 * @component
 * @param {Array} suggestions - List of suggestion options to render
 * @param {string[]} selectedOptions - Currently selected option UUIDs
 * @param {Function} onOptionChange - Callback function for option selection
 *
 * @example
 * How to use:
 *
 * return (
 *   <SuggestionCheckboxes
 *     suggestions={suggestionData}
 *     selectedOptions={selected}
 *     onOptionChange={handleChange}
 *   />
 * )
 */

const SuggestionCheckboxes = ({suggestions, selectedOptions, onOptionChange}) => {

    const {t} = useTranslation()

    return (
        <SuggestionBoxContainer>
            <h4>{t("vocabulary_field.popup.suggestions.title")}</h4>
            {suggestions.map((suggestion) => (
                <div key={suggestion.metaFieldOptionUuid}>
                    <input type="checkbox"
                           id={suggestion.metaFieldOptionUuid}
                           name={suggestion.metaFieldOptionUuid}
                           checked={selectedOptions?.includes(suggestion.metaFieldOptionUuid)}
                           onChange={() => onOptionChange(suggestion.metaFieldOptionUuid)}
                    />
                    <label htmlFor={suggestion.metaFieldOptionUuid}>
                        {t('language.current_code') === 'nl' ? suggestion.labelNL : suggestion.labelEN}
                    </label>
                </div>
            ))}
        </SuggestionBoxContainer>
    );
};

export default SuggestionCheckboxes;