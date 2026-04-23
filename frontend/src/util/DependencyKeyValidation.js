import i18n from "i18next";

function isObject(value) {
    return value !== null && typeof value === "object" && !Array.isArray(value);
}

/**
 * "Filled" semantics for dependency-key validation:
 * - strings: non-empty after trim
 * - arrays: non-empty
 * - numbers: any number (including 0) counts as filled
 * - booleans: only true counts as filled
 * - objects: any non-empty object counts as filled
 */
export function isFilledForDependency(value) {
    if (value === null || typeof value === "undefined") {
        return false;
    }
    if (Array.isArray(value)) {
        return value.length > 0;
    }
    if (typeof value === "string") {
        return value.trim() !== "";
    }
    if (typeof value === "number") {
        return !Number.isNaN(value);
    }
    if (typeof value === "boolean") {
        return value === true;
    }
    if (isObject(value)) {
        return Object.keys(value).length > 0;
    }
    return !!value;
}

function uniqueNonEmptyStrings(values) {
    const list = Array.isArray(values) ? values : [];
    const trimmed = list
        .map((v) => (typeof v === "string" ? v.trim() : ""))
        .filter((v) => v.length > 0);
    return [...new Set(trimmed)];
}

function dependencyKeyErrorMessage(dependencyGroupLabels) {
    // Only show human-readable field labels here (no UUID/key fallbacks).
    const labels = uniqueNonEmptyStrings(dependencyGroupLabels);
    const fieldsPart = labels.length > 0 ? `: ${labels.join(", ")}` : "";

    if (i18n.language === "nl") {
        return `Om verder te gaan, vul minimaal één van de volgende velden in${fieldsPart}.`;
    }

    return `In order to progress, please make sure that one of the following fields is filled in${fieldsPart}.`;
}

/**
 * Validates a "dependencyKey group required" rule:
 * If multiple fields share the same dependencyKey, at least one of them must be filled.
 *
 * Returns `true` when valid, otherwise returns a translated error message string.
 */
export function validateDependencyKeyGroup({ dependencyKey, dependencyGroupKeys, dependencyGroupLabels, getValues }) {
    if (!dependencyKey) {
        return true;
    }

    // Check if the dependency group has at least 2 fields. 
    // Meaning this will return true if for example only link has a key.
    // If two fields have the same key, it will continue.
    const keys = Array.isArray(dependencyGroupKeys) ? dependencyGroupKeys : [];
    if (keys.length < 2) {
        return true;
    }
    
    if (typeof getValues !== "function") {
        // Can't evaluate sibling fields; skip validation rather than breaking all forms.
        return true;
    }

    const anyFilled = keys.some((key) => isFilledForDependency(getValues(key)));
    return anyFilled ? true : dependencyKeyErrorMessage(dependencyGroupLabels);
}

