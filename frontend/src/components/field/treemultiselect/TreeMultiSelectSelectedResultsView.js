import React from 'react';
import styled from 'styled-components';
import { useTranslation } from 'react-i18next';
import { greyDark, majorelle, openSans, spaceCadet, spaceCadetLight, white } from '../../../Mixins';
import {
    optionQualifiesForSynonymBadge,
    synonymBadgeTitle,
} from './treeMultiSelectSynonymUtils';
import { SynonymBadgeElementTooltip } from './SynonymBadgeElementTooltip';

/**
 * Read-only list of selected tree options (majorelle panel, flat rows — no per-item boxes).
 */
export function TreeMultiSelectSelectedResultsView({ selectedOptions, resolvingOptionId }) {
    const { t } = useTranslation();
    const langKey = t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN';
    const langIsNl = t('language.current_code') === 'nl';

    return (
        <ResultsRoot>
            <ResultsHeader>
                <ResultsHeaderGroup>
                    <ResultsTitle>{t('treemultiselect_field.selected').toUpperCase()}</ResultsTitle>
                    <ResultsCountBadge>{selectedOptions.length}</ResultsCountBadge>
                </ResultsHeaderGroup>
            </ResultsHeader>
            <ResultsScroll>
                {selectedOptions.length === 0 ? (
                    <ResultsEmpty>{t('treemultiselect_field.nothing_selected')}</ResultsEmpty>
                ) : (
                    selectedOptions.map((option) => {
                        const leafLabel = option[langKey] || option.labelNL;
                        const crumbs = Array.isArray(option.breadcrumb) ? option.breadcrumb : [];
                        const ancestors = crumbs.slice(0, -1);
                        const breadcrumb =
                            ancestors.length > 0 ? ancestors.join(' › ') : null;
                        const synonymTip = synonymBadgeTitle(option, langIsNl);
                        const isResolving =
                            resolvingOptionId != null &&
                            String(resolvingOptionId) === String(option.id);
                        return (
                            <ResultsItem key={option.id}>
                                <ResultsItemInfo>
                                    <ResultsItemTopRow>
                                        <ResultsItemLabel>{leafLabel}</ResultsItemLabel>
                                        {optionQualifiesForSynonymBadge(option, langIsNl) ? (
                                            <SynonymBadgeElementTooltip text={synonymTip}>
                                                <SynonymBadge>
                                                    {t('treemultiselect_field.synonym_badge')}
                                                </SynonymBadge>
                                            </SynonymBadgeElementTooltip>
                                        ) : null}
                                    </ResultsItemTopRow>
                                    {breadcrumb ? <ResultsPath>{breadcrumb}</ResultsPath> : null}
                                    {isResolving && (
                                        <ResultsResolving>
                                            {t('treemultiselect_field.resolving_option')}
                                        </ResultsResolving>
                                    )}
                                </ResultsItemInfo>
                            </ResultsItem>
                        );
                    })
                )}
            </ResultsScroll>
        </ResultsRoot>
    );
}

const ResultsRoot = styled.div`
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    margin-top: 16px;
    box-sizing: border-box;
    background-color: rgba(144, 106, 241, 0.15);
    border: 2px solid rgba(144, 106, 241, 0.15);
    overflow: hidden;
`;

const ResultsHeader = styled.div`
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 10px 12px;
    border-bottom: 2px solid rgba(144, 106, 241, 0.15);
`;

const ResultsHeaderGroup = styled.div`
    display: flex;
    align-items: center;
    gap: 10px;
`;

const ResultsTitle = styled.span`
    ${openSans()};
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    color: ${spaceCadetLight};
`;

const ResultsCountBadge = styled.span`
    background-color: ${majorelle};
    color: ${white};
    font-size: 11px;
    font-weight: 700;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
`;

const ResultsScroll = styled.div`
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow-y: auto;
    overscroll-behavior: contain;
    min-height: 0;
    max-height: 560px;
    padding: 4px 16px 16px;
    background: transparent;
`;

const ResultsEmpty = styled.p`
    ${openSans()};
    font-size: 14px;
    color: ${greyDark};
    text-align: center;
    margin-top: 40px;
    padding: 0 16px 24px;
`;

const ResultsItem = styled.div`
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    box-sizing: border-box;
    cursor: default;
    min-width: 0;
    padding: 14px 0;
    border-bottom: 1px solid rgba(115, 68, 238, 0.45);

    &:last-child {
        border-bottom: none;
    }
`;

const ResultsItemInfo = styled.div`
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: 4px;
    min-width: 0;
`;

const ResultsItemTopRow = styled.div`
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
`;

const ResultsItemLabel = styled.span`
    ${openSans()};
    font-size: 14px;
    font-weight: 700;
    color: ${spaceCadet};
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
    flex: 1;
`;

const SynonymBadge = styled.span`
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    padding: 2px 8px;
    border: 1px solid ${spaceCadetLight};
    border-radius: 4px;
    color: ${spaceCadet};
    background: ${white};
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    ${openSans()};
    cursor: help;
`;

const ResultsPath = styled.span`
    ${openSans()};
    font-size: 12px;
    font-weight: 500;
    line-height: 1.4;
    color: ${spaceCadetLight};
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: left;
`;

const ResultsResolving = styled.span`
    ${openSans()};
    font-size: 11px;
    font-style: italic;
    font-weight: 600;
    color: ${spaceCadet};
    margin-top: 2px;
`;
