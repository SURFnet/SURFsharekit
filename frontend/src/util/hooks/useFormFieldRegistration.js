import React from 'react';
import { useTranslation } from "react-i18next";
import {isFilledForDependency, validateDependencyKeyGroup} from "../DependencyKeyValidation";

/**
 * Custom hook for registering form fields that don't have native HTML input elements
 * (like react-select components) with react-hook-form validation
 * 
 * @param {Object} props - Component props containing register, name, isRequired, etc.
 * @param {Function} getValue - Function that returns the current field value
 * @param {Object} validationOptions - Additional validation options
 * @returns {Object} - Object containing the hidden input element and registration props
 */
export const useFormFieldRegistration = (props, getValue, validationOptions = {}) => {
    const { t } = useTranslation();
    
    const registerProps = {
        required: props.isRequired,
        validate: (value) => {
            if (props.isRequired) {
                // Handle different value types
                if (Array.isArray(value)) {
                    return value && value.length > 0 ? true : t('validation.required');
                } else if (typeof value === 'string') {
                    return value && value.trim() !== '' ? true : t('validation.required');
                } else {
                    return value && value !== null && value !== undefined ? true : t('validation.required');
                }
            }
            // DependencyKey group validation (at least one field in the group should be filled)
            if (props.dependencyKey && Array.isArray(props.dependencyGroupKeys) && props.dependencyGroupKeys.length > 1) {
                // If one of the fields is filled in, validation passes immediately
                if (isFilledForDependency(value)) {
                    return true;
                }
                return validateDependencyKeyGroup({
                    dependencyKey: props.dependencyKey,
                    dependencyGroupKeys: props.dependencyGroupKeys,
                    dependencyGroupLabels: props.dependencyGroupLabels,
                    getValues: props.getValues
                });
            }
            return true;
        },
        ...validationOptions
    };

    const canRegister = typeof props.register === 'function' && !!props.name;

    const hiddenInput = canRegister ? (
        <input
            type="hidden"
            {...props.register(props.name, registerProps)}
            value={getValue() ?? ''}
        />
    ) : null;

    return {
        hiddenInput,
        registerProps
    };
}; 