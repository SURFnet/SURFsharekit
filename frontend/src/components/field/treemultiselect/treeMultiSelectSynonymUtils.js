/**
 * Meta field options may expose copy on the node or under JSON:API-style `attributes`.
 */
function pick(option, key) {
    if (!option || typeof option !== 'object') return undefined;
    if (option[key] != null && option[key] !== '') return option[key];
    const a = option.attributes;
    if (a && typeof a === 'object' && a[key] != null && a[key] !== '') return a[key];
    return undefined;
}

export function metaFieldOptionDescription(option, langIsNl) {
    if (!option || typeof option !== 'object') return '';
    if (langIsNl) {
        return (
            pick(option, 'descriptionNL') ??
            pick(option, 'descriptionNl') ??
            pick(option, 'description') ??
            ''
        );
    }
    return (
        pick(option, 'descriptionEN') ??
        pick(option, 'descriptionEn') ??
        pick(option, 'description') ??
        ''
    );
}

export function synonymBadgeTitle(option, langIsNl) {
    const desc = String(metaFieldOptionDescription(option, langIsNl) || '').trim();
    if (desc) return desc;
    const alt = pick(option, 'altLabel');
    return alt != null && String(alt).trim() !== '' ? String(alt) : '';
}

/** Show SYNONYM badge when there is alt text and/or a description to show in the tooltip. */
export function optionQualifiesForSynonymBadge(option, langIsNl) {
    const alt = pick(option, 'altLabel');
    if (alt != null && String(alt).trim() !== '') return true;
    return String(metaFieldOptionDescription(option, langIsNl) || '').trim() !== '';
}
