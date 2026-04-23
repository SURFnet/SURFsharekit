import React, {useCallback, useEffect, useMemo, useRef, useState} from "react";
import axios from "axios";
import './treeMultiSelectField.scss'
import {useTranslation} from "react-i18next";
import {
    faChevronDown,
    faChevronRight,
    faPlus, faTimes,
    faSearch,
} from "@fortawesome/free-solid-svg-icons";
import IconButtonText from "../../buttons/iconbuttontext/IconButtonText";
import LoadingIndicator from "../../loadingindicator/LoadingIndicator";
import {ThemedH6} from "../../../Elements";
import styled, { css, keyframes } from "styled-components";
import {
    cultured,
    greyLight,
    greyLighter,
    greyMedium,
    greyDark,
    majorelle,
    openSans,
    spaceCadetLight,
    white, independence, majorelleLight
} from "../../../Mixins";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import VocabularyPopup2 from "../vocabularypopup/VocabularyPopup2";
import Toaster from "../../../util/toaster/Toaster";
import {GlobalPageMethods} from "../../page/Page";
import Api from "../../../util/api/Api";
import {useNavigation} from "../../../providers/NavigationProvider";
import {validateDependencyKeyGroup} from "../../../util/DependencyKeyValidation";
import {TreeMultiSelectSelectedResultsView} from "./TreeMultiSelectSelectedResultsView";
import {
    optionQualifiesForSynonymBadge,
    synonymBadgeTitle,
} from "./treeMultiSelectSynonymUtils";
import { SynonymBadgeElementTooltip } from "./SynonymBadgeElementTooltip";

const TREE_SEARCH_DEBOUNCE_MS = 500;
const TREE_SEARCH_PAGE_SIZE = 10;

const META_FIELD_OPTIONS_PAGE_SIZE = 50;
const META_FIELD_OPTIONS_MAX_PAGES = 200;
/** Backend embeds nested `children` on list responses when `filter[includeChildren]=1`. */
const META_FIELD_OPTIONS_INCLUDE_CHILDREN = true;

function extractTreeSearchItems(response) {
    const d = response?.data;
    if (Array.isArray(d)) return d;
    if (d == null) return [];
    if (typeof d === 'object') {
        if (Array.isArray(d.data)) return d.data;
        if (Array.isArray(d.results)) return d.results;
        if (Array.isArray(d.items)) return d.items;
    }
    return [];
}

function treeSearchHasNextPage(response, itemCount) {
    const links = response?.links;
    if (links?.next) return true;
    const m = response?.meta || {};
    if (typeof m.currentPage === 'number' && typeof m.totalPages === 'number') {
        return m.currentPage < m.totalPages;
    }
    if (typeof m.page === 'number' && typeof m.totalPages === 'number') {
        return m.page < m.totalPages;
    }
    if (typeof m.page === 'number' && typeof m.lastPage === 'number') {
        return m.page < m.lastPage;
    }
    return itemCount >= TREE_SEARCH_PAGE_SIZE;
}

function mapTreeSearchHitToRow(hit, labelKey) {
    const rowId = pickTreeSearchHitId(hit);
    if (!rowId) return null;
    const pref =
        hit.prefLabel ||
        hit[labelKey] ||
        hit.labelNL ||
        hit.labelEN ||
        '';
    const alt =
        hit.altLabel ||
        (labelKey === 'labelNL' ? hit.labelEN : hit.labelNL) ||
        '';
    const breadcrumb =
        hit.breadcrumb ||
        hit.path ||
        (Array.isArray(hit.breadcrumbs) ? hit.breadcrumbs.join(' › ') : '') ||
        '';
    return { id: rowId, prefLabel: pref, altLabel: alt, breadcrumb };
}

function breadcrumbPartsFromSearchString(breadcrumb) {
    if (!breadcrumb || typeof breadcrumb !== 'string') return [];
    return breadcrumb
        .split(/\s*(?:>|›|→)\s*/)
        .map((s) => s.trim())
        .filter(Boolean);
}

/** Search vs list endpoints may disagree on string vs number ids. */
function metaFieldOptionIdsEqual(a, b) {
    if (a == null || b == null) return false;
    return String(a) === String(b);
}

function checkedItemsHasId(checkedItems, id) {
    if (id == null || !checkedItems?.length) return false;
    const s = String(id);
    return checkedItems.some((c) => String(c) === s);
}

/** Consistent keys for expandedItems / loadingChildrenById (avoids string/number key mismatches). */
function treeExpansionKey(id) {
    if (id == null || id === '') return '';
    return String(id);
}

function pickTreeSearchHitId(hit) {
    if (!hit || typeof hit !== 'object') return null;
    const raw =
        hit.id ??
        hit.optionId ??
        hit.metaFieldOptionId ??
        hit.uuid ??
        (hit.attributes &&
            (hit.attributes.id ?? hit.attributes.uuid ?? hit.attributes.optionId));
    if (raw == null || raw === '') return null;
    return String(raw);
}

/** JSON:API may expose parentOption / rootNode as a string id or as { id }. */
function metaFieldRelationId(val) {
    if (val == null) return null;
    if (typeof val === 'object' && val !== null) {
        if (val.id != null) return String(val.id);
        return null;
    }
    return String(val);
}

/** Ensures tree nodes have children arrays and _childrenLoaded for lazy-fetch when hasChildren is true. */
function normalizeLazyOptionNodes(nodes) {
    if (!nodes || !Array.isArray(nodes)) return [];
    return nodes.map((n) => {
        const rawChildren = Array.isArray(n.children) ? n.children : [];
        const children = normalizeLazyOptionNodes(rawChildren);
        const _childrenLoaded =
            children.length > 0 ||
            n.hasChildren === false ||
            n.hasChildren !== true;
        return {
            ...n,
            id: n.id != null ? String(n.id) : n.id,
            parentOption:
                n.parentOption != null ? metaFieldRelationId(n.parentOption) : n.parentOption,
            rootNode: n.rootNode != null ? metaFieldRelationId(n.rootNode) : n.rootNode,
            children,
            _childrenLoaded,
        };
    });
}

/** Same ordering as API list requests (sort=labelNL|labelEN): ascending A→Z for the active label. */
function sortSiblingsByMetaFieldLabel(nodes, labelKey) {
    if (!labelKey || !nodes?.length) return nodes;
    return [...nodes].sort((a, b) => {
        const la = String(a[labelKey] ?? a.labelNL ?? '').toLocaleLowerCase();
        const lb = String(b[labelKey] ?? b.labelNL ?? '').toLocaleLowerCase();
        return la.localeCompare(lb, undefined, { sensitivity: 'base' });
    });
}

/** Collapse duplicate ids in a sibling list (keeps first position, merges data from later copies). */
function dedupeSiblingsByIdPreserveOrder(nodes) {
    if (!nodes?.length) return nodes || [];
    const byId = new Map();
    const order = [];
    for (const n of nodes) {
        if (!byId.has(n.id)) {
            byId.set(n.id, n);
            order.push(n.id);
        } else {
            byId.set(n.id, mergeTwoOptionNodes(byId.get(n.id), n, null));
        }
    }
    return order.map((id) => byId.get(id));
}

/** When lazy load returns the same ids as nodes already merged (e.g. from search), merge by id instead of duplicating. */
function mergeTwoOptionNodes(existing, incoming, sortLabelKey) {
    const a = existing;
    const b = incoming;
    const aCh = a.children || [];
    const bCh = b.children || [];
    let children;
    if (aCh.length && bCh.length) {
        children = mergeReplacedChildrenWithExisting(aCh, bCh, sortLabelKey);
    } else {
        children = aCh.length ? aCh : bCh;
    }
    return {
        ...b,
        ...a,
        children,
        _childrenLoaded: !!a._childrenLoaded || !!b._childrenLoaded,
        hasChildren:
            a.hasChildren === true || b.hasChildren === true
                ? true
                : a.hasChildren === false && b.hasChildren === false
                  ? false
                  : b.hasChildren !== undefined
                    ? b.hasChildren
                    : a.hasChildren,
    };
}

function mergeReplacedChildrenWithExisting(existing, fetched, sortLabelKey) {
    if (!fetched?.length) return existing || [];
    const fetchedDeduped = dedupeSiblingsByIdPreserveOrder(fetched);
    const existingById = new Map((existing || []).map((x) => [x.id, x]));
    const mergedMain = fetchedDeduped.map((f) => {
        const e = existingById.get(f.id);
        return e ? mergeTwoOptionNodes(e, f, sortLabelKey) : f;
    });
    const fetchedIds = new Set(fetchedDeduped.map((f) => f.id));
    const onlyInExisting = (existing || []).filter((e) => !fetchedIds.has(e.id));
    const combined = [...mergedMain, ...onlyInExisting];
    return sortLabelKey ? sortSiblingsByMetaFieldLabel(combined, sortLabelKey) : combined;
}

function appendRootPageChildrenDeduped(existing, pageBatch, sortLabelKey) {
    const result = [...(existing || [])];
    const indexById = new Map(result.map((n, i) => [n.id, i]));
    for (const raw of dedupeSiblingsByIdPreserveOrder(pageBatch || [])) {
        const i = indexById.get(raw.id);
        if (i !== undefined) {
            result[i] = mergeTwoOptionNodes(result[i], raw, sortLabelKey);
        } else {
            indexById.set(raw.id, result.length);
            result.push(raw);
        }
    }
    return sortLabelKey ? sortSiblingsByMetaFieldLabel(result, sortLabelKey) : result;
}

function patchOptionSubtreeChildren(nodes, nodeId, newChildren, sortLabelKey) {
    if (!nodes || !nodes.length) return nodes;
    return nodes.map((n) => {
        if (metaFieldOptionIdsEqual(n.id, nodeId)) {
            const merged = mergeReplacedChildrenWithExisting(n.children || [], newChildren, sortLabelKey);
            return { ...n, children: merged, _childrenLoaded: true };
        }
        if (n.children && n.children.length > 0) {
            return { ...n, children: patchOptionSubtreeChildren(n.children, nodeId, newChildren, sortLabelKey) };
        }
        return n;
    });
}

function patchVocabularyOptionChildren(vocabularies, vocabularyId, nodeId, newChildren, sortLabelKey) {
    return vocabularies.map((v) =>
        metaFieldOptionIdsEqual(v.id, vocabularyId)
            ? { ...v, children: patchOptionSubtreeChildren(v.children || [], nodeId, newChildren, sortLabelKey) }
            : v
    );
}

/** Single-resource JSON:API responses may deserialize to an object; collections to an array. */
function firstDeserializedMetaFieldOption(response) {
    const d = response?.data;
    if (d == null) return null;
    return Array.isArray(d) ? d[0] : d;
}

function mergeOptionIntoTree(vocabularies, rootNodeId, node, sortLabelKey) {
    const parentId = node.parentOption;
    return vocabularies.map((v) => {
        if (!metaFieldOptionIdsEqual(v.id, rootNodeId)) return v;
        if (metaFieldOptionIdsEqual(parentId, rootNodeId)) {
            const ch = v.children || [];
            const idx = ch.findIndex((c) => metaFieldOptionIdsEqual(c.id, node.id));
            const mergedChild =
                idx >= 0
                    ? {
                          ...ch[idx],
                          ...node,
                          children:
                              ch[idx].children && ch[idx].children.length
                                  ? ch[idx].children
                                  : node.children || [],
                          _childrenLoaded:
                              ch[idx]._childrenLoaded ||
                              node._childrenLoaded ||
                              !!(node.children && node.children.length),
                      }
                    : node;
            const newCh =
                idx >= 0 ? ch.map((c, i) => (i === idx ? mergedChild : c)) : [...ch, mergedChild];
            const sortedCh = sortLabelKey ? sortSiblingsByMetaFieldLabel(newCh, sortLabelKey) : newCh;
            return { ...v, children: sortedCh };
        }
        return {
            ...v,
            children: mergeOptionUnderParentInNodes(v.children || [], parentId, node, sortLabelKey),
        };
    });
}

function mergeOptionUnderParentInNodes(nodes, parentId, node, sortLabelKey) {
    if (!nodes?.length) return nodes;
    return nodes.map((n) => {
        if (metaFieldOptionIdsEqual(n.id, parentId)) {
            const ch = n.children || [];
            const idx = ch.findIndex((c) => metaFieldOptionIdsEqual(c.id, node.id));
            const mergedChild =
                idx >= 0
                    ? {
                          ...ch[idx],
                          ...node,
                          children:
                              ch[idx].children && ch[idx].children.length
                                  ? ch[idx].children
                                  : node.children || [],
                          _childrenLoaded: true,
                      }
                    : { ...node, _childrenLoaded: true };
            const newCh =
                idx >= 0 ? ch.map((c, i) => (i === idx ? mergedChild : c)) : [...ch, mergedChild];
            const sortedCh = sortLabelKey ? sortSiblingsByMetaFieldLabel(newCh, sortLabelKey) : newCh;
            return { ...n, children: sortedCh, _childrenLoaded: true };
        }
        if (n.children?.length) {
            return { ...n, children: mergeOptionUnderParentInNodes(n.children, parentId, node, sortLabelKey) };
        }
        return n;
    });
}

export function TreeMultiSelectField(props) {

    const {t} = useTranslation()

    let label = t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN';

    let classAddition = '';
    classAddition += (props.readonly ? ' disabled' : '');
    classAddition += (props.isValid ? ' valid' : '');
    classAddition += (props.hasError ? ' invalid' : '');
    const items = props.formReducerState[props.name] ?? [];

    const [options, setOptions] = useState(props.options ? props.options : [])
    const [vocabularies, setVocabularies] = useState([])
    const [vocabularyOptions, setVocabularyOptions] = useState([])
    const [activeVocabulary, setActiveVocabulary] = useState(null)
    const [checkedItems, setCheckedItems] = useState(props.defaultValue ? props.defaultValue : []);
    const [foldedCategories, setFoldedCategories] = useState({})
    const [expandedItems, setExpandedItems] = useState({});
    const [trail, setTrail] = useState([]);
    const [searchQuery, setSearchQuery] = useState('');
    /** When false, hide the search hits panel even if the query is non-empty (e.g. after click-away). */
    const [searchResultsOpen, setSearchResultsOpen] = useState(false);
    const [selectedSearchResults, setSelectedSearchResults] = useState([]);
    const [highlightedOptionId, setHighlightedOptionId] = useState(null);
    const optionsListRef = useRef(null);
    const vocabularyOptionsRef = useRef([]);
    const navigate = useNavigation()

    const [isLoading, setIsLoading] = useState(false);
    const [isInitialLoading, setIsInitialLoading] = useState(!!props.defaultValue);
    const [isLoadingRootPage, setIsLoadingRootPage] = useState(false);
    const [loadingChildrenById, setLoadingChildrenById] = useState({});
    /** Search hit ids selected before tree hydration; metadata for right panel. */
    const [pendingHydrationMetaById, setPendingHydrationMetaById] = useState({});
    /**
     * For each search-picked leaf id, ancestor ids merged into checkedItems for checkbox trail only.
     * Those ancestors must not appear as separate rows in the selected panel or in selection counts.
     */
    const [searchPickAncestorIdsByLeafId, setSearchPickAncestorIdsByLeafId] = useState({});
    const [rightPanelHydrateLoadingId, setRightPanelHydrateLoadingId] = useState(null);
    const [treeSearchHits, setTreeSearchHits] = useState([]);
    const [treeSearchHasMore, setTreeSearchHasMore] = useState(false);
    const [treeSearchLoading, setTreeSearchLoading] = useState(false);
    const [treeSearchLoadingMore, setTreeSearchLoadingMore] = useState(false);
    const treeSearchCancelRef = useRef(null);
    const searchDebounceRef = useRef(null);
    const searchResultsListRef = useRef(null);
    const searchBarAreaRef = useRef(null);
    const rightPanelHydrateLockRef = useRef(false);
    const lastTreeSearchPageLoadedRef = useRef(0);
    const searchQueryRef = useRef(searchQuery);
    const activeVocabularyRef = useRef(activeVocabulary);

    useEffect(() => {
        searchQueryRef.current = searchQuery;
    }, [searchQuery]);

    useEffect(() => {
        activeVocabularyRef.current = activeVocabulary;
    }, [activeVocabulary]);

    useEffect(() => {
        vocabularyOptionsRef.current = vocabularyOptions;
    }, [vocabularyOptions]);

    const [availableVocabularyIds, setAvailableVocabularyIds] = useState(null);

    useEffect(() => {
        const config = {
            params: {
                "filter[fieldKey]": props.name,
                "filter[ParentOption]": 'null',
                "filter[isRemoved][EQ]": 0,
                'page[number]': 1,
                'page[size]': 10,
            }
        };
        if (!props.retainOrder) {
            config.params.sort = label;
        }
        Api.jsonApiGet(
            'metaFieldOptions',
            () => {},
            (response) => {
                const ids = (response.data || []).map(option => String(option.id));
                setAvailableVocabularyIds(ids);
            },
            () => {},
            () => {},
            config,
        );
    }, [props.name]);

    const allVocabulariesSelected = availableVocabularyIds !== null
        && availableVocabularyIds.length > 0
        && availableVocabularyIds.every(id =>
            vocabularyOptions.some(vocab => metaFieldOptionIdsEqual(vocab.id, id))
        );

    useEffect(() => {
        if (!searchResultsOpen || !searchQuery.trim()) return;
        function handlePointerDown(e) {
            const el = searchBarAreaRef.current;
            if (el && !el.contains(e.target)) {
                setSearchResultsOpen(false);
            }
        }
        document.addEventListener('mousedown', handlePointerDown);
        document.addEventListener('touchstart', handlePointerDown, { passive: true });
        return () => {
            document.removeEventListener('mousedown', handlePointerDown);
            document.removeEventListener('touchstart', handlePointerDown);
        };
    }, [searchResultsOpen, searchQuery]);

    useEffect(() => {
        setSearchPickAncestorIdsByLeafId((prev) => {
            let changed = false;
            const next = { ...prev };
            for (const leafKey of Object.keys(next)) {
                if (!checkedItemsHasId(checkedItems, leafKey)) {
                    delete next[leafKey];
                    changed = true;
                }
            }
            return changed ? next : prev;
        });
    }, [checkedItems]);

    const fetchTreeSearch = useCallback(
        (vocabularyId, queryStr, pageNum, append) => {
            const langKey = t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN';

            if (!append) {
                setTreeSearchLoading(true);
                lastTreeSearchPageLoadedRef.current = 0;
            } else {
                setTreeSearchLoadingMore(true);
            }

            const params = {
                metaFieldOptionUuid: vocabularyId,
                query: queryStr,
                'page[number]': pageNum,
                'page[size]': TREE_SEARCH_PAGE_SIZE,
            };

            if (treeSearchCancelRef.current) {
                treeSearchCancelRef.current.cancel('Operation canceled due to new request.');
            }
            const source = axios.CancelToken.source();
            const requestConfig = Api.getRequestConfig({
                params,
                cancelToken: source.token,
            });
            const url = Api.normalizeUrl('metaFieldOptionTreeSearch');

            const applyStaleGuard = () =>
                vocabularyId !== activeVocabularyRef.current ||
                queryStr !== searchQueryRef.current.trim();

            treeSearchCancelRef.current = source;

            axios
                .get(url, requestConfig)
                .then((response) => {
                    if (applyStaleGuard()) {
                        setTreeSearchLoading(false);
                        setTreeSearchLoadingMore(false);
                        return;
                    }
                    try {
                        const items = extractTreeSearchItems(response);
                        const rows = items
                            .map((h) => mapTreeSearchHitToRow(h, langKey))
                            .filter(Boolean);
                        const hasMore = treeSearchHasNextPage(response, items.length);
                        lastTreeSearchPageLoadedRef.current = pageNum;
                        setTreeSearchHasMore(hasMore);
                        if (append) {
                            setTreeSearchHits((prev) => {
                                const byId = new Map(prev.map((r) => [r.id, r]));
                                for (const r of rows) {
                                    if (!byId.has(r.id)) byId.set(r.id, r);
                                }
                                return Array.from(byId.values());
                            });
                        } else {
                            setTreeSearchHits(rows);
                        }
                    } catch (err) {
                        console.error(err);
                        if (!append) setTreeSearchHits([]);
                        setTreeSearchHasMore(false);
                    }
                    setTreeSearchLoading(false);
                    setTreeSearchLoadingMore(false);
                })
                .catch((err) => {
                    if (axios.isCancel(err)) {
                        setTreeSearchLoading(false);
                        setTreeSearchLoadingMore(false);
                        return;
                    }
                    setTreeSearchLoading(false);
                    setTreeSearchLoadingMore(false);
                    if (err?.response?.status === 401) {
                        navigate('/login?redirect=' + window.location.pathname);
                    } else {
                        Toaster.showServerError(err);
                    }
                    if (!append) {
                        setTreeSearchHits([]);
                        setTreeSearchHasMore(false);
                    }
                });
        },
        [navigate, t]
    );

    useEffect(() => {
        if (searchDebounceRef.current) {
            clearTimeout(searchDebounceRef.current);
            searchDebounceRef.current = null;
        }

        const q = searchQuery.trim();
        if (!activeVocabulary || !q) {
            setTreeSearchHits([]);
            setTreeSearchHasMore(false);
            setTreeSearchLoading(false);
            setTreeSearchLoadingMore(false);
            lastTreeSearchPageLoadedRef.current = 0;
            return;
        }

        searchDebounceRef.current = setTimeout(() => {
            fetchTreeSearch(activeVocabulary, q, 1, false);
        }, TREE_SEARCH_DEBOUNCE_MS);

        return () => {
            if (searchDebounceRef.current) {
                clearTimeout(searchDebounceRef.current);
            }
        };
    }, [searchQuery, activeVocabulary, fetchTreeSearch]);

    const loadMoreTreeSearchResults = useCallback(() => {
        if (
            treeSearchLoadingMore ||
            treeSearchLoading ||
            !treeSearchHasMore ||
            !activeVocabulary
        ) {
            return;
        }
        const q = searchQuery.trim();
        if (!q) return;
        fetchTreeSearch(activeVocabulary, q, lastTreeSearchPageLoadedRef.current + 1, true);
    }, [
        activeVocabulary,
        fetchTreeSearch,
        searchQuery,
        treeSearchHasMore,
        treeSearchLoading,
        treeSearchLoadingMore,
    ]);

    const handleSearchResultsScroll = useCallback(
        (e) => {
            const el = e.currentTarget;
            if (
                treeSearchLoadingMore ||
                treeSearchLoading ||
                !treeSearchHasMore
            ) {
                return;
            }
            if (el.scrollHeight - el.scrollTop - el.clientHeight < 56) {
                loadMoreTreeSearchResults();
            }
        },
        [
            loadMoreTreeSearchResults,
            treeSearchHasMore,
            treeSearchLoading,
            treeSearchLoadingMore,
        ]
    );

    let stringifiedDefaultValue = null
    if (!(props.defaultValue === undefined || props.defaultValue === null || props.defaultValue.length === 0)) {
        stringifiedDefaultValue = JSON.stringify(props.defaultValue);
    }

    let stringifiedCurrentValue = null
    if (!(checkedItems === undefined || checkedItems === null || checkedItems.length === 0)) {
        stringifiedCurrentValue = JSON.stringify(checkedItems);
    }

    const findRootOptions = (options, defaultValueIds) => {
        function findRootNodes(array) {
            let rootNodes = [];

            array.forEach(item => {
                if (defaultValueIds.some((dv) => metaFieldOptionIdsEqual(dv, item.id))) {
                    rootNodes.push(item.rootNode);
                }

                if (item.children && item.children.length > 0) {
                    rootNodes = rootNodes.concat(findRootNodes(item.children));
                }
            });

            return rootNodes;
        }

        const matchedRootNodes = findRootNodes(options);
        const matchedOptions = options.filter((option) =>
            matchedRootNodes.some((r) => metaFieldOptionIdsEqual(r, option.id))
        );

        vocabularyOptionsRef.current = matchedOptions;
        setVocabularyOptions(matchedOptions);
    };

    useEffect(() => {
        if (props.defaultValue) {
            setIsInitialLoading(true);
            (async () => {
                try {
                    const mergedRootsRaw = await fetchAllPagesForParent(props, 'null');
                    if (Array.isArray(mergedRootsRaw)) {
                        const mergedRoots = normalizeLazyOptionNodes(mergedRootsRaw);
                        findRootOptions(mergedRoots, props.defaultValue);
                        const matchedRootNodes = [];
                        const findRoots = (arr) => {
                            arr.forEach(item => {
                                if (props.defaultValue.some((dv) => metaFieldOptionIdsEqual(dv, item.id))) {
                                    matchedRootNodes.push(item.rootNode);
                                }
                                if (item.children?.length) findRoots(item.children);
                            });
                        };
                        findRoots(mergedRoots);
                        const firstMatch = mergedRoots.find((opt) =>
                            matchedRootNodes.some((r) => metaFieldOptionIdsEqual(r, opt.id))
                        );
                        if (firstMatch) setActiveVocabulary(firstMatch.id);
                    }
                } catch (error) {
                    console.error("Failed to fetch vocabulary options:", error);
                } finally {
                    setIsInitialLoading(false);
                }
            })();
        }
    }, []);

    useEffect(() => {
        const isDirty = stringifiedDefaultValue !== stringifiedCurrentValue
        props.setValue(props.name, stringifiedCurrentValue, {shouldDirty: isDirty})
    }, [checkedItems]);

    useEffect(() => {
        props.register(props.name, {
            required: props.isRequired,
            validate: () => validateDependencyKeyGroup({
                dependencyKey: props.dependencyKey,
                dependencyGroupKeys: props.dependencyGroupKeys,
                dependencyGroupLabels: props.dependencyGroupLabels,
                getValues: props.getValues
            })
        })
        props.setValue(props.name, stringifiedCurrentValue)
    }, [props.register]);

    const handleFieldSetClick = (selectedItem) => {
        setCheckedItems((prevCheckedItems) => {
            const { id, parentOption } = selectedItem;

            const getAllIds = (item, allIds = []) => {
                allIds.push(item.id);
                if (item.children && item.children.length > 0) {
                    item.children.forEach((child) => getAllIds(child, allIds));
                }
                return allIds;
            };

            const collectParentIds = (currentItem, collectedIds = []) => {
                if (currentItem.parentOption === currentItem.rootNode) {
                    return collectedIds;
                }

                collectedIds.push(currentItem.parentOption);

                const parentItem = findOptionById(
                    currentItem.parentOption,
                    vocabularyOptionsRef.current.filter((option) =>
                        metaFieldOptionIdsEqual(option.id, currentItem.rootNode)
                    )
                );
                if (parentItem) {
                    return collectParentIds(parentItem, collectedIds);
                }

                return collectedIds;
            };

            if (!checkedItemsHasId(prevCheckedItems, id)) {
                let idsToAdd = [id];

                if (parentOption) {
                    const parentIds = collectParentIds(selectedItem);
                    idsToAdd = [...idsToAdd, ...parentIds];
                }

                return [...new Set([...prevCheckedItems, ...idsToAdd])];
            }
            else {
                const idsToRemove = getAllIds(selectedItem);
                let newCheckedItems = prevCheckedItems.filter((itemId) => !idsToRemove.includes(itemId));

                let currentItem = selectedItem;
                while (currentItem.parentOption && currentItem.parentOption !== currentItem.rootNode) {
                    const parentItem = findOptionById(
                        currentItem.parentOption,
                        vocabularyOptionsRef.current.filter((option) =>
                            metaFieldOptionIdsEqual(option.id, currentItem.rootNode)
                        )
                    );
                    if (!parentItem) break;

                    const parentDescendantIds = getAllIds(parentItem);
                    const hasSelectedDescendant = parentDescendantIds.some(
                        (descId) =>
                            !metaFieldOptionIdsEqual(descId, parentItem.id) &&
                            checkedItemsHasId(newCheckedItems, descId)
                    );

                    if (!hasSelectedDescendant) {
                        newCheckedItems = newCheckedItems.filter(
                            (itemId) => !metaFieldOptionIdsEqual(itemId, parentItem.id)
                        );
                    }

                    currentItem = parentItem;
                }

                return newCheckedItems;
            }
        });
    };

    const clearStateById = (setState, id) => {
        setState((prev) => {
            const { [id]: removed, ...rest } = prev;
            return rest;
        });
    };

    const updateTrail = (optionId) => {
        let newTrail = [];
        let currentOption = findOptionById(optionId, vocabularyOptionsRef.current);

        while (currentOption) {
            newTrail.unshift(currentOption.id);
            currentOption = currentOption.parentOption
                ? findOptionById(currentOption.parentOption, vocabularyOptionsRef.current)
                : null;
        }

        setTrail(newTrail);
    };

    const findOptionById = (id, options) => {
        if (id == null || !options?.length) return null;
        const sid = String(id);
        for (let option of options) {
            if (metaFieldOptionIdsEqual(option.id, sid)) {
                return option;
            }

            if (option.children && option.children.length > 0) {
                const found = findOptionById(id, option.children);
                if (found) {
                    return found;
                }
            }
        }

        return null;
    };

    /** Open folders from root to each checked option that already exists in the loaded tree. */
    useEffect(() => {
        const roots = vocabularyOptions;
        if (!roots?.length) return;

        setExpandedItems((prev) => {
            const next = { ...prev };
            let changed = false;
            for (const id of checkedItems) {
                const opt = findOptionById(id, roots);
                if (!opt) continue;
                let cur = opt;
                while (cur && cur.parentOption && cur.parentOption !== cur.rootNode) {
                    const pid = treeExpansionKey(cur.parentOption);
                    if (!next[pid]) {
                        next[pid] = true;
                        changed = true;
                    }
                    const rootFiltered = roots.filter((v) => metaFieldOptionIdsEqual(v.id, cur.rootNode));
                    cur = findOptionById(cur.parentOption, rootFiltered);
                    if (!cur) break;
                }
            }
            return changed ? next : prev;
        });
    }, [vocabularyOptions, checkedItems]);

    /**
     * When lazy loading merges a selected leaf into the tree, ensure ancestor option ids
     * are in checkedItems so checkboxes from root to leaf match tree selection behaviour.
     */
    useEffect(() => {
        const roots = vocabularyOptions;
        if (!roots?.length) return;

        setCheckedItems((prev) => {
            const prevSet = new Set(prev.map((x) => String(x)));
            const toAdd = [];

            for (const leafId of prev) {
                const opt = findOptionById(leafId, roots);
                if (!opt) continue;

                let cur = opt;
                while (cur && cur.parentOption && cur.parentOption !== cur.rootNode) {
                    const pid = cur.parentOption;
                    const pidStr = String(pid);
                    if (!prevSet.has(pidStr)) {
                        toAdd.push(pid);
                        prevSet.add(pidStr);
                    }
                    const rootFiltered = roots.filter((v) =>
                        metaFieldOptionIdsEqual(v.id, cur.rootNode)
                    );
                    cur = findOptionById(pid, rootFiltered);
                    if (!cur) break;
                }
            }

            if (toAdd.length === 0) return prev;
            return [...new Set([...prev, ...toAdd])];
        });
    }, [vocabularyOptions, checkedItems]);

    const toggleExpanded = (id) => {
        const key = treeExpansionKey(id);
        setExpandedItems((prevState) => ({
            ...prevState,
            [key]: !prevState[key],
        }));
    };

    const findOptionRowEl = (container, optionId) => {
        if (!container) return null;
        return Array.from(container.querySelectorAll('[data-option-id]')).find((node) =>
            metaFieldOptionIdsEqual(node.getAttribute('data-option-id'), optionId)
        );
    };

    const expandToOption = (option) => {
        const vocabs = vocabularyOptionsRef.current;
        const idsToExpand = {};
        let current = findOptionById(option.parentOption, vocabs);
        while (current) {
            idsToExpand[treeExpansionKey(current.id)] = true;
            current = current.parentOption
                ? findOptionById(current.parentOption, vocabs)
                : null;
        }
        setExpandedItems((prev) => ({ ...prev, ...idsToExpand }));
        setHighlightedOptionId(null);
        setTimeout(() => {
            setHighlightedOptionId(option.id);
            const container = optionsListRef.current;
            const el = findOptionRowEl(container, option.id);
            if (el && container) {
                const elTop = el.offsetTop - container.offsetTop;
                const scrollTo = elTop - container.clientHeight / 2 + el.clientHeight / 2;
                container.scrollTo({ top: scrollTo, behavior: 'smooth' });
            }
            setTimeout(() => setHighlightedOptionId(null), 1500);
        }, 100);
    };

    /**
     * When lazy loading finally contains a search-picked option, drop pending metadata so the
     * right panel uses the real tree row. We intentionally do not expand/scroll/highlight here —
     * that was jarring when the node appeared after "load more" pagination.
     * (Explicit focus: right-panel click / hydrateSelectedOptionFromSearch still calls expandToOption*.)
     */
    useEffect(() => {
        const roots = vocabularyOptions;
        if (!roots?.length) return;
        const pendingKeys = Object.keys(pendingHydrationMetaById);
        if (!pendingKeys.length) return;

        const resolvedOpts = [];
        for (const id of pendingKeys) {
            const opt = findOptionById(id, roots);
            if (opt) resolvedOpts.push({ id, opt });
        }
        if (!resolvedOpts.length) return;

        setPendingHydrationMetaById((prev) => {
            const next = { ...prev };
            for (const { id } of resolvedOpts) {
                for (const k of Object.keys(next)) {
                    if (metaFieldOptionIdsEqual(k, id)) delete next[k];
                }
            }
            return next;
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [vocabularyOptions, pendingHydrationMetaById]);

    const activeVocabularyObject = useMemo(() => {
        return vocabularyOptions.find((item) =>
            metaFieldOptionIdsEqual(item.id, activeVocabulary)
        );
    }, [vocabularyOptions, activeVocabulary]);

    /** Main tree list: server search is shown in the dropdown; tree stays fully navigable. */
    const filteredOptions = useMemo(() => {
        if (!activeVocabularyObject || !activeVocabularyObject.children) return [];
        return activeVocabularyObject.children;
    }, [activeVocabularyObject]);

    const searchPanelCheckboxOnlyAncestorIds = useMemo(() => {
        const s = new Set();
        for (const list of Object.values(searchPickAncestorIdsByLeafId)) {
            for (const id of list) {
                s.add(String(id));
            }
        }
        return s;
    }, [searchPickAncestorIdsByLeafId]);

    const selectedOptionObjects = useMemo(() => {
        if (!activeVocabularyObject) return [];
        const langKey = t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN';
        const vocabId = activeVocabularyObject.id;

        const hasCheckedChild = (option) => {
            if (!option.children || option.children.length === 0) return false;
            return option.children.some(child =>
                checkedItemsHasId(checkedItems, child.id) || hasCheckedChild(child)
            );
        };

        const collectLeafSelections = (options, ancestors = [], result = []) => {
            for (const option of options) {
                const currentPath = [...ancestors, option[langKey] || option.labelNL];

                if (checkedItemsHasId(checkedItems, option.id) && !hasCheckedChild(option)) {
                    result.push({ ...option, breadcrumb: currentPath });
                }
                if (option.children && option.children.length > 0) {
                    collectLeafSelections(option.children, currentPath, result);
                }
            }
            return result;
        };

        const fromTree = collectLeafSelections(activeVocabularyObject.children || []).filter(
            (o) => !searchPanelCheckboxOnlyAncestorIds.has(String(o.id))
        );
        const byId = new Map(fromTree.map((o) => [o.id, o]));

        for (const [id, meta] of Object.entries(pendingHydrationMetaById)) {
            if (!metaFieldOptionIdsEqual(meta.vocabularyId, vocabId)) continue;
            if (byId.has(id)) continue;
            if (!checkedItemsHasId(checkedItems, id)) continue;
            const parts = breadcrumbPartsFromSearchString(meta.breadcrumb);
            const breadcrumbArr = parts.length ? parts : [meta.prefLabel];
            byId.set(id, {
                id,
                labelNL: meta.prefLabel,
                labelEN: meta.altLabel || meta.prefLabel,
                altLabel: meta.altLabel || undefined,
                breadcrumb: breadcrumbArr,
                _pendingHydration: true,
                rootNode: meta.vocabularyId,
                parentOption: null,
                children: [],
            });
        }

        return Array.from(byId.values());
    }, [
        activeVocabularyObject,
        checkedItems,
        pendingHydrationMetaById,
        searchPanelCheckboxOnlyAncestorIds,
        t,
    ]);

    /** Readonly: all leaf selections across every loaded vocabulary (no active-vocab switcher). */
    const readonlySelectedPanelObjects = useMemo(() => {
        if (!props.readonly || !vocabularyOptions?.length) return [];
        const langKey = t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN';

        const hasCheckedChild = (option) => {
            if (!option.children || option.children.length === 0) return false;
            return option.children.some(
                (child) =>
                    checkedItemsHasId(checkedItems, child.id) || hasCheckedChild(child)
            );
        };

        const collectLeafSelections = (options, ancestors = [], result = []) => {
            for (const option of options) {
                const currentPath = [...ancestors, option[langKey] || option.labelNL];

                if (checkedItemsHasId(checkedItems, option.id) && !hasCheckedChild(option)) {
                    result.push({ ...option, breadcrumb: currentPath });
                }
                if (option.children && option.children.length > 0) {
                    collectLeafSelections(option.children, currentPath, result);
                }
            }
            return result;
        };

        const byId = new Map();
        for (const vocabRoot of vocabularyOptions) {
            const fromTree = collectLeafSelections(vocabRoot.children || []).filter(
                (o) => !searchPanelCheckboxOnlyAncestorIds.has(String(o.id))
            );
            for (const o of fromTree) {
                if (!byId.has(o.id)) byId.set(o.id, o);
            }
            for (const [id, meta] of Object.entries(pendingHydrationMetaById)) {
                if (!metaFieldOptionIdsEqual(meta.vocabularyId, vocabRoot.id)) continue;
                if (byId.has(id)) continue;
                if (!checkedItemsHasId(checkedItems, id)) continue;
                const parts = breadcrumbPartsFromSearchString(meta.breadcrumb);
                const breadcrumbArr = parts.length ? parts : [meta.prefLabel];
                byId.set(id, {
                    id,
                    labelNL: meta.prefLabel,
                    labelEN: meta.altLabel || meta.prefLabel,
                    altLabel: meta.altLabel || undefined,
                    breadcrumb: breadcrumbArr,
                    _pendingHydration: true,
                    rootNode: meta.vocabularyId,
                    parentOption: null,
                    children: [],
                });
            }
        }
        return Array.from(byId.values());
    }, [
        props.readonly,
        vocabularyOptions,
        checkedItems,
        pendingHydrationMetaById,
        searchPanelCheckboxOnlyAncestorIds,
        t,
    ]);

    const selectedCountPerVocabulary = useMemo(() => {
        const counts = {};
        const hasCheckedChild = (option) => {
            if (!option.children || option.children.length === 0) return false;
            return option.children.some(child =>
                checkedItemsHasId(checkedItems, child.id) || hasCheckedChild(child)
            );
        };
        const countLeaves = (options) => {
            let count = 0;
            for (const option of options) {
                if (
                    checkedItemsHasId(checkedItems, option.id) &&
                    !hasCheckedChild(option) &&
                    !searchPanelCheckboxOnlyAncestorIds.has(String(option.id))
                ) {
                    count++;
                }
                if (option.children?.length) {
                    count += countLeaves(option.children);
                }
            }
            return count;
        };
        for (const vocab of vocabularyOptions) {
            counts[vocab.id] = countLeaves(vocab.children || []);
        }
        for (const [id, meta] of Object.entries(pendingHydrationMetaById)) {
            if (!checkedItemsHasId(checkedItems, id)) continue;
            const vid = meta.vocabularyId;
            if (findOptionById(id, vocabularyOptions)) continue;
            counts[vid] = (counts[vid] || 0) + 1;
        }
        return counts;
    }, [vocabularyOptions, checkedItems, pendingHydrationMetaById, searchPanelCheckboxOnlyAncestorIds]);

    const toggleSearchResult = (id) => {
        setSelectedSearchResults((prev) =>
            checkedItemsHasId(prev, id)
                ? prev.filter((x) => !metaFieldOptionIdsEqual(x, id))
                : [...prev, id]
        );
    };

    const handleSearchResultRowClick = (result) => {
        if (props.readonly) return;

        const vocabId = activeVocabularyRef.current;
        if (!vocabId) return;

        if (checkedItemsHasId(selectedSearchResults, result.id)) {
            toggleSearchResult(result.id);
            const opt = findOptionById(result.id, vocabularyOptionsRef.current);
            if (opt) {
                handleFieldSetClick(opt);
            } else {
                setCheckedItems((prev) =>
                    prev.filter((x) => !metaFieldOptionIdsEqual(x, result.id))
                );
            }
            setPendingHydrationMetaById((prev) => {
                const next = { ...prev };
                for (const k of Object.keys(next)) {
                    if (metaFieldOptionIdsEqual(k, result.id)) delete next[k];
                }
                return next;
            });
            setSearchPickAncestorIdsByLeafId((prev) => {
                const next = { ...prev };
                for (const k of Object.keys(next)) {
                    if (metaFieldOptionIdsEqual(k, result.id)) delete next[k];
                }
                return next;
            });
            return;
        }

        setSelectedSearchResults((prev) => [...prev, result.id]);
        const existing = findOptionById(result.id, vocabularyOptionsRef.current);
        if (existing) {
            setCheckedItems((prev) => {
                if (checkedItemsHasId(prev, result.id)) return prev;
                const rootFiltered = vocabularyOptionsRef.current.filter((v) =>
                    metaFieldOptionIdsEqual(v.id, existing.rootNode)
                );
                const parentIds = [];
                let cur = existing;
                while (cur && cur.parentOption && cur.parentOption !== cur.rootNode) {
                    parentIds.push(cur.parentOption);
                    cur = findOptionById(cur.parentOption, rootFiltered);
                }
                return [...new Set([...prev, existing.id, ...parentIds])];
            });
        } else {
            setCheckedItems((prev) =>
                checkedItemsHasId(prev, result.id) ? prev : [...prev, result.id]
            );
            setPendingHydrationMetaById((prev) => ({
                ...prev,
                [result.id]: {
                    vocabularyId: vocabId != null ? String(vocabId) : vocabId,
                    prefLabel: result.prefLabel,
                    altLabel: result.altLabel,
                    breadcrumb: result.breadcrumb,
                },
            }));
        }
        setActiveVocabulary(String(vocabId));
        void mergeCheckedAncestorIdsFromApi(result.id);
    };

    const renderOptionRow = (option, level = 0) => {
        const childList = option.children || [];
        const hasLoadedChildren = childList.length > 0;
        const mayHaveMoreChildren = option.hasChildren === true && !option._childrenLoaded;
        const showChevron = option.hasChildren === true || hasLoadedChildren;
        const expKey = treeExpansionKey(option.id);
        const isExpanded = !!expandedItems[expKey];
        const isChecked = checkedItemsHasId(checkedItems, option.id);
        const childLoading = !!loadingChildrenById[expKey];
        const labelKey = t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN';
        const langIsNl = t('language.current_code') === 'nl';
        const optionLabel = option[labelKey] || option.labelNL;
        const synonymTip = synonymBadgeTitle(option, langIsNl);

        return (
            <React.Fragment key={option.id}>
                <OptionRow
                    level={level}
                    highlighted={metaFieldOptionIdsEqual(highlightedOptionId, option.id)}
                    data-option-id={option.id}
                    onClick={() => {
                        if (!props.readonly) handleFieldSetClick(option);
                    }}
                >
                    <OptionRowLeft>
                        <ChevronButton
                            visible={showChevron}
                            onClick={async (e) => {
                                e.stopPropagation();
                                if (!showChevron) return;
                                if (mayHaveMoreChildren) {
                                    try {
                                        await loadNestedChildren(option);
                                    } catch {
                                        return;
                                    }
                                }
                                toggleExpanded(expKey);
                            }}
                        >
                            {showChevron && (
                                <FontAwesomeIcon
                                    icon={isExpanded ? faChevronDown : faChevronRight}
                                    style={{fontSize: '11px', color: '#666'}}
                                />
                            )}
                        </ChevronButton>
                        <StyledCheckbox
                            type="checkbox"
                            checked={isChecked}
                            disabled={props.readonly}
                            onClick={(e) => e.stopPropagation()}
                            onChange={() => {
                                if (!props.readonly) handleFieldSetClick(option);
                            }}
                        />
                        <OptionLabelRow>
                            <OptionLabel>{optionLabel}</OptionLabel>
                            {optionQualifiesForSynonymBadge(option, langIsNl) ? (
                                <SynonymBadgeElementTooltip text={synonymTip}>
                                    <SynonymOutlineBadge
                                        onClick={(e) => e.stopPropagation()}
                                    >
                                        {t('treemultiselect_field.synonym_badge')}
                                    </SynonymOutlineBadge>
                                </SynonymBadgeElementTooltip>
                            ) : null}
                        </OptionLabelRow>
                    </OptionRowLeft>
                </OptionRow>
                {childLoading && (
                    <OptionRow level={level + 1} data-option-id={`${option.id}-loading`}>
                        <OptionRowLeft>
                            <LoadingChildrenText>{t('treemultiselect_field.loading_children')}</LoadingChildrenText>
                        </OptionRowLeft>
                    </OptionRow>
                )}
                {showChevron && isExpanded && childList.map(child => renderOptionRow(child, level + 1))}
            </React.Fragment>
        );
    };

    return (
        <TreeMultiSelectFieldRoot>
            {props.readonly && !(items && items.length > 0) &&
                <>
                    { props.showEmptyState
                        ?   <EmptyPlaceholder>
                                <ThemedH6>{props.emptyText}</ThemedH6>
                            </EmptyPlaceholder>
                        :   <div className={"field-input readonly"}>{"-"}</div>
                    }
                </>
            }

            {!props.readonly && (
                <VocabularyTagRow>
                    {vocabularyOptions && vocabularyOptions.map((tag) => {
                        return (
                            <SelectedVocabularyTag key={tag.id} active={activeVocabulary === tag.id} onClick={() => setCurrentRootNode(tag.id)}>
                                <p>{tag.labelNL}</p>
                                {selectedCountPerVocabulary[tag.id] > 0 && (
                                    <VocabularyCountBadge active={activeVocabulary === tag.id}>{selectedCountPerVocabulary[tag.id]}</VocabularyCountBadge>
                                )}
                                <DeleteIcon active={activeVocabulary === tag.id} color={majorelle} icon={faTimes} onClick={(e) => { e.stopPropagation(); deleteVocabulary(tag); }}/>
                            </SelectedVocabularyTag>
                        )
                    })}

                    {!isInitialLoading &&
                        <AddVocabularyButton
                            key={'add-vocabulary-button'}
                            onClick={(!isLoading && !allVocabulariesSelected) ? () => openVocabularyPopup(vocabularyOptions) : null}
                            disabled={isLoading || allVocabulariesSelected}
                        >
                            <span>+ {props.addText}</span>
                        </AddVocabularyButton>
                    }
                </VocabularyTagRow>
            )}

            {props.readonly && vocabularyOptions?.length > 0 && (
                <TreeMultiSelectSelectedResultsView
                    selectedOptions={readonlySelectedPanelObjects}
                    resolvingOptionId={rightPanelHydrateLoadingId}
                />
            )}

            {!props.readonly && activeVocabulary && (
                <>
                    <ContentContainer>
                        <LeftPanel>
                            <SearchBarWrapper ref={searchBarAreaRef}>
                                <SearchBar>
                                    <SearchIcon icon={faSearch}/>
                                    <SearchInput
                                        type="text"
                                        placeholder={t('treemultiselect_field.search')}
                                        value={searchQuery}
                                        onChange={(e) => {
                                            setSearchQuery(e.target.value);
                                            setSearchResultsOpen(true);
                                        }}
                                        onFocus={() => setSearchResultsOpen(true)}
                                    />
                                </SearchBar>
                                {searchQuery.trim() && searchResultsOpen && (
                                    <SearchResultsDropdown>
                                        <SearchResultsHeader>
                                            {t('treemultiselect_field.search_results')}
                                        </SearchResultsHeader>
                                        <SearchResultsList
                                            ref={searchResultsListRef}
                                            onScroll={handleSearchResultsScroll}
                                        >
                                            {treeSearchLoading && treeSearchHits.length === 0 && (
                                                <SearchResultsStatus>
                                                    <LoadingIndicator />
                                                </SearchResultsStatus>
                                            )}
                                            {!treeSearchLoading &&
                                                treeSearchHits.length === 0 &&
                                                searchQuery.trim() && (
                                                    <SearchResultsStatus>
                                                        {t('treemultiselect_field.search_no_results')}
                                                    </SearchResultsStatus>
                                                )}
                                            {treeSearchHits.map((result) => (
                                                <SearchResultRow
                                                    key={result.id}
                                                    onClick={() =>
                                                        !props.readonly &&
                                                        handleSearchResultRowClick(result)
                                                    }
                                                >
                                                    <StyledCheckbox
                                                        type="checkbox"
                                                        checked={selectedSearchResults.includes(result.id)}
                                                        disabled={props.readonly}
                                                        readOnly
                                                    />
                                                    <SearchResultContent>
                                                        <SearchResultLabelRow>
                                                            <SearchResultPrefLabel>
                                                                {result.prefLabel}
                                                            </SearchResultPrefLabel>
                                                            {result.altLabel ? (
                                                                <SearchResultAltLabel>
                                                                    {t('treemultiselect_field.search_alt_prefix')}{' '}
                                                                    {result.altLabel}
                                                                </SearchResultAltLabel>
                                                            ) : null}
                                                        </SearchResultLabelRow>
                                                        {result.breadcrumb ? (
                                                            <SearchResultBreadcrumb>
                                                                {result.breadcrumb}
                                                            </SearchResultBreadcrumb>
                                                        ) : null}
                                                    </SearchResultContent>
                                                </SearchResultRow>
                                            ))}
                                            {treeSearchLoadingMore && (
                                                <SearchResultsStatus>
                                                    <LoadingIndicator />
                                                </SearchResultsStatus>
                                            )}
                                            {treeSearchHasMore && !treeSearchLoadingMore && (
                                                <LoadMoreRow>
                                                    <LoadMoreButton
                                                        type="button"
                                                        disabled={treeSearchLoading}
                                                        onClick={() => loadMoreTreeSearchResults()}
                                                    >
                                                        {t('treemultiselect_field.search_load_more')}
                                                    </LoadMoreButton>
                                                </LoadMoreRow>
                                            )}
                                        </SearchResultsList>
                                        <SearchResultsFooter>
                                            <span>
                                                {selectedSearchResults.length}{' '}
                                                {selectedSearchResults.length === 1
                                                    ? t('treemultiselect_field.search_selected_one')
                                                    : t('treemultiselect_field.search_selected_other')}
                                            </span>
                                        </SearchResultsFooter>
                                    </SearchResultsDropdown>
                                )}
                            </SearchBarWrapper>
                            <OptionsList ref={optionsListRef}>
                                {filteredOptions.map(option => renderOptionRow(option, 0))}
                                {(!searchQuery.trim() || !searchResultsOpen) &&
                                    activeVocabularyObject?._rootHasMore && (
                                    <LoadMoreRow>
                                        <LoadMoreButton
                                            type="button"
                                            disabled={isLoadingRootPage}
                                            onClick={() => appendNextRootPage()}
                                        >
                                            {isLoadingRootPage
                                                ? t('treemultiselect_field.loading')
                                                : t('treemultiselect_field.load_more')}
                                        </LoadMoreButton>
                                    </LoadMoreRow>
                                )}
                            </OptionsList>
                        </LeftPanel>
                        <RightPanel>
                            <SelectedHeader>
                                <SelectedHeaderGroup>
                                    <SelectedTitle>
                                        {t('treemultiselect_field.selected').toUpperCase()}
                                    </SelectedTitle>
                                    <CountBadge>{selectedOptionObjects.length}</CountBadge>
                                </SelectedHeaderGroup>
                            </SelectedHeader>
                            <SelectedContent>
                                {selectedOptionObjects.length === 0 ? (
                                    <NothingSelected>{t('treemultiselect_field.nothing_selected')}</NothingSelected>
                                ) : (
                                    selectedOptionObjects.map(option => {
                                        const langKey = t('language.current_code') === 'nl' ? 'labelNL' : 'labelEN';
                                        const leafLabel = option[langKey] || option.labelNL;
                                        const ancestors = option.breadcrumb.slice(0, -1);
                                        const breadcrumb = ancestors.length > 0
                                            ? ancestors.join(' › ')
                                            : null;
                                        return (
                                            <SelectedItem
                                                key={option.id}
                                                onClick={() =>
                                                    hydrateSelectedOptionFromSearch(option.id)
                                                }
                                            >
                                                <SelectedItemInfo>
                                                    <SelectedItemTopRow>
                                                        <SelectedItemLabel>
                                                            {leafLabel}
                                                        </SelectedItemLabel>
                                                    </SelectedItemTopRow>
                                                    {breadcrumb && (
                                                        <SelectedPathText>
                                                            {breadcrumb}
                                                        </SelectedPathText>
                                                    )}
                                                    {metaFieldOptionIdsEqual(
                                                        rightPanelHydrateLoadingId,
                                                        option.id
                                                    ) && (
                                                        <SelectedItemResolving>
                                                            {t('treemultiselect_field.resolving_option')}
                                                        </SelectedItemResolving>
                                                    )}
                                                </SelectedItemInfo>
                                                <RemoveButton
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handleFieldSetClick(option);
                                                        setSelectedSearchResults((p) =>
                                                            p.filter(
                                                                (x) =>
                                                                    !metaFieldOptionIdsEqual(x, option.id)
                                                            )
                                                        );
                                                        if (option._pendingHydration) {
                                                            setPendingHydrationMetaById((prev) => {
                                                                const next = { ...prev };
                                                                for (const k of Object.keys(next)) {
                                                                    if (metaFieldOptionIdsEqual(k, option.id)) {
                                                                        delete next[k];
                                                                    }
                                                                }
                                                                return next;
                                                            });
                                                        }
                                                        setSearchPickAncestorIdsByLeafId((prev) => {
                                                            const next = { ...prev };
                                                            for (const k of Object.keys(next)) {
                                                                if (metaFieldOptionIdsEqual(k, option.id)) {
                                                                    delete next[k];
                                                                }
                                                            }
                                                            return next;
                                                        });
                                                    }}
                                                >
                                                    <FontAwesomeIcon icon={faTimes} style={{fontSize: '10px'}}/>
                                                </RemoveButton>
                                            </SelectedItem>
                                        );
                                    })
                                )}
                            </SelectedContent>
                        </RightPanel>
                    </ContentContainer>
                    <PurpleBar>
                        <PurpleBarItemCount>
                            {selectedOptionObjects.length === 0
                                ? t('treemultiselect_field.items_selected_none')
                                : <><PurpleBarNumber>{selectedOptionObjects.length}</PurpleBarNumber> {selectedOptionObjects.length === 1 ? t('treemultiselect_field.item_selected_label') : t('treemultiselect_field.items_selected_label')}</>
                            }
                        </PurpleBarItemCount>
                    </PurpleBar>
                </>
            )}

            {isInitialLoading && (
                <LoadingStatus>
                    <LoadingIndicator />
                    <LoadingText>{t('treemultiselect_field.loading')}</LoadingText>
                </LoadingStatus>
            )}
        </TreeMultiSelectFieldRoot>
    );

    function setCurrentRootNode(vocabularyId){
        setActiveVocabulary(vocabularyId)

        clearStateById(setFoldedCategories, vocabularyId)
        clearStateById(setExpandedItems, vocabularyId)
        updateTrail(null)
        setSearchQuery('')
        setSearchResultsOpen(false)
    }

    function deleteVocabulary(vocabulary){
        setVocabularyOptions((prevArray) => {
            const next = prevArray.filter(
                (item) => !metaFieldOptionIdsEqual(item.id, vocabulary.id)
            );
            vocabularyOptionsRef.current = next;
            return next;
        });

        const collectIds = (items) => {
            let ids = [];
            items.forEach(item => {
                ids.push(item.id);
                if (item.children) {
                    ids = ids.concat(collectIds(item.children));
                }
            });
            return ids;
        };

        const allVocabularyIds = collectIds(vocabulary.children || []);

        clearStateById(setFoldedCategories, vocabulary.id)
        clearStateById(setExpandedItems, vocabulary.id)

        setPendingHydrationMetaById((prevMeta) => {
            const pendingToRemove = Object.keys(prevMeta).filter(
                (pid) =>
                    metaFieldOptionIdsEqual(prevMeta[pid].vocabularyId, vocabulary.id)
            );
            const removeAll = new Set([...allVocabularyIds, ...pendingToRemove]);
            setCheckedItems((ci) => ci.filter((id) => !removeAll.has(id)));
            setSelectedSearchResults((sr) => sr.filter((id) => !removeAll.has(id)));
            const next = { ...prevMeta };
            pendingToRemove.forEach((pid) => delete next[pid]);
            return next;
        });

        setSearchPickAncestorIdsByLeafId((prev) => {
            const next = { ...prev };
            for (const leafKey of Object.keys(next)) {
                if (allVocabularyIds.some((x) => metaFieldOptionIdsEqual(x, leafKey))) {
                    delete next[leafKey];
                }
            }
            return next;
        });

        if (activeVocabulary === vocabulary.id) {
            setActiveVocabulary(null);
        }
    }

    function getVocabularyOptions(props, parentOptionId, onSuccess, errorCallback = () => {}) {

        function onValidate(response) {}
        function onLocalFailure(error) {
            errorCallback(error)
        }

        function onServerFailure(error) {
            errorCallback(error)
        }

        const vocabularyCallConfig = {
            params: {
                "filter[fieldKey]": props.name,
                "filter[ParentOption]": parentOptionId,
                "filter[isRemoved][EQ]": 0,
                'page[number]': 1,
                'page[size]': META_FIELD_OPTIONS_PAGE_SIZE,
                ...(META_FIELD_OPTIONS_INCLUDE_CHILDREN ? { 'filter[includeChildren]': 1 } : {}),
            }
        };

        if (!props.retainOrder) {
            vocabularyCallConfig.params.sort = label
        }

        Api.jsonApiGet('metaFieldOptions', onValidate, onSuccess, onLocalFailure, onServerFailure, vocabularyCallConfig);
    }

    function fetchMetaFieldOptionsPage(props, parentOptionId, pageNumber, pageSize) {
        const parent =
            parentOptionId === null || parentOptionId === undefined ? 'null' : parentOptionId;
        return new Promise((resolve, reject) => {
            function onValidate(response) {}

            function onLocalFailure(error) {
                Toaster.showServerError(error);
                GlobalPageMethods.setFullScreenLoading(false);
                reject(error);
            }

            function onServerFailure(error) {
                Toaster.showServerError(error);
                if (error.response?.status === 401) {
                    navigate('/login?redirect=' + window.location.pathname);
                }
                GlobalPageMethods.setFullScreenLoading(false);
                reject(error);
            }

            const vocabularyCallConfig = {
                params: {
                    "filter[fieldKey]": props.name,
                    "filter[ParentOption]": parent,
                    "filter[isRemoved][EQ]": 0,
                    'page[number]': pageNumber,
                    'page[size]': pageSize,
                    ...(META_FIELD_OPTIONS_INCLUDE_CHILDREN ? { 'filter[includeChildren]': 1 } : {}),
                }
            };

            if (!props.retainOrder) {
                vocabularyCallConfig.params.sort = label;
            }

            Api.jsonApiGet('metaFieldOptions', onValidate, resolve, onLocalFailure, onServerFailure, vocabularyCallConfig);
        });
    }

    /**
     * Loads one option via GET metaFieldOptions/{id} (id URL-encoded).
     * Collection filters must not use filter[id] — this API only allows filter[fieldKey] on list routes (JAC_014).
     */
    function fetchMetaFieldOptionById(props, optionId) {
        const path = `metaFieldOptions/${encodeURIComponent(optionId)}`;
        return new Promise((resolve, reject) => {
            function onValidate(response) {}

            function onLocalFailure(error) {
                Toaster.showServerError(error);
                GlobalPageMethods.setFullScreenLoading(false);
                reject(error);
            }

            function onServerFailure(error) {
                Toaster.showServerError(error);
                if (error.response?.status === 401) {
                    navigate('/login?redirect=' + window.location.pathname);
                }
                GlobalPageMethods.setFullScreenLoading(false);
                reject(error);
            }

            const vocabularyCallConfig = {
                params: {
                    "filter[fieldKey]": props.name,
                }
            };

            Api.jsonApiGet(path, onValidate, resolve, onLocalFailure, onServerFailure, vocabularyCallConfig);
        });
    }

    /**
     * Walk parentOption via GET metaFieldOptions/{id} and merge every id on the path into checkedItems.
     * Needed for search picks before the lazy tree contains the leaf: ancestors like "Systema nervosum"
     * only exist in the UI once their id is checked, which list payloads may not provide until expanded.
     */
    async function mergeCheckedAncestorIdsFromApi(leafId) {
        if (props.readonly || leafId == null || leafId === '') return;
        const idsToMerge = [];
        let currentId = leafId;
        const seen = new Set();
        while (currentId != null && !seen.has(String(currentId))) {
            seen.add(String(currentId));
            idsToMerge.push(String(currentId));
            let resp;
            try {
                resp = await fetchMetaFieldOptionById(props, currentId);
            } catch {
                break;
            }
            const node = firstDeserializedMetaFieldOption(resp);
            if (!node) break;
            const p = metaFieldRelationId(node.parentOption);
            const r = metaFieldRelationId(node.rootNode);
            if (!p || (r && metaFieldOptionIdsEqual(p, r))) break;
            currentId = p;
        }
        if (idsToMerge.length === 0) return;
        const leafKey = String(idsToMerge[0]);
        const ancestorOnly = idsToMerge.slice(1);
        setSearchPickAncestorIdsByLeafId((prev) => ({
            ...prev,
            [leafKey]: ancestorOnly,
        }));
        setCheckedItems((prev) => [...new Set([...prev, ...idsToMerge])]);
    }

    /**
     * Prefer React state over ref: ref is synced in useEffect and can lag one commit after lazy merges,
     * which would wrongly trigger a full API re-hydration even when the node is already in memory.
     */
    function findOptionInLoadedVocabularies(id) {
        return (
            findOptionById(id, vocabularyOptions) ||
            findOptionById(id, vocabularyOptionsRef.current)
        );
    }

    async function ensureOptionPathLoadedForSearch(leafId) {
        const existing = findOptionInLoadedVocabularies(leafId);
        if (existing) {
            if (findOptionById(leafId, vocabularyOptions)) {
                vocabularyOptionsRef.current = vocabularyOptions;
                return vocabularyOptions;
            }
            return vocabularyOptionsRef.current;
        }

        const chainBottomUp = [];
        let currentId = leafId;
        const seen = new Set();
        while (currentId && !seen.has(currentId)) {
            seen.add(currentId);
            const resp = await fetchMetaFieldOptionById(props, currentId);
            const node = firstDeserializedMetaFieldOption(resp);
            if (!node) {
                break;
            }
            chainBottomUp.push(node);
            if (node.parentOption === node.rootNode) {
                break;
            }
            currentId = node.parentOption;
        }

        if (!chainBottomUp.length) {
            throw new Error('Meta field option not found');
        }

        const chain = chainBottomUp.reverse();
        const rootNodeId = chain[0].rootNode;
        let merged = [...vocabularyOptionsRef.current];

        if (!merged.some((v) => metaFieldOptionIdsEqual(v.id, rootNodeId))) {
            const rootResp = await fetchMetaFieldOptionById(props, rootNodeId);
            const rootRow = firstDeserializedMetaFieldOption(rootResp);
            if (!rootRow) {
                throw new Error('Vocabulary root could not be loaded');
            }
            merged = [
                ...merged,
                {
                    id: String(rootNodeId),
                    labelNL: rootRow.labelNL,
                    labelEN: rootRow.labelEN,
                    children: [],
                    _rootNextPage: 2,
                    _rootHasMore: false,
                },
            ];
        }

        for (const raw of chain) {
            const normalized = normalizeLazyOptionNodes([raw])[0];
            merged = mergeOptionIntoTree(
                merged,
                rootNodeId,
                normalized,
                !props.retainOrder ? label : null
            );
        }

        const sortKey = !props.retainOrder ? label : null;

        const rootKidsRaw = await fetchAllPagesForParent(props, rootNodeId);
        const rootKidsNorm = normalizeLazyOptionNodes(rootKidsRaw);
        merged = merged.map((v) => {
            if (!metaFieldOptionIdsEqual(v.id, rootNodeId)) return v;
            return {
                ...v,
                children: mergeReplacedChildrenWithExisting(
                    v.children || [],
                    rootKidsNorm,
                    sortKey
                ),
                _rootHasMore: false,
                _rootNextPage: 2,
            };
        });

        for (const raw of chain) {
            if (raw.hasChildren === false) continue;
            const kidsRaw = await fetchAllPagesForParent(props, raw.id);
            const kidsNorm = normalizeLazyOptionNodes(kidsRaw);
            merged = patchVocabularyOptionChildren(
                merged,
                rootNodeId,
                raw.id,
                kidsNorm,
                sortKey
            );
        }

        return merged;
    }

    async function hydrateSelectedOptionFromSearch(leafId) {
        if (props.readonly) {
            const ro = findOptionInLoadedVocabularies(leafId);
            if (ro) void expandToOptionWithPrefetch(ro);
            return;
        }
        if (rightPanelHydrateLockRef.current) return;

        const existing = findOptionInLoadedVocabularies(leafId);
        if (existing) {
            const roots =
                findOptionById(leafId, vocabularyOptions) != null
                    ? vocabularyOptions
                    : vocabularyOptionsRef.current;
            vocabularyOptionsRef.current = roots;
            setPendingHydrationMetaById((p) => {
                const n = { ...p };
                for (const k of Object.keys(n)) {
                    if (metaFieldOptionIdsEqual(k, leafId)) delete n[k];
                }
                return n;
            });
            setCheckedItems((prev) => {
                const rootFiltered = roots.filter((v) =>
                    metaFieldOptionIdsEqual(v.id, existing.rootNode)
                );
                const parentIds = [];
                let cur = existing;
                while (cur && cur.parentOption && cur.parentOption !== cur.rootNode) {
                    parentIds.push(cur.parentOption);
                    cur = findOptionById(cur.parentOption, rootFiltered);
                }
                return [...new Set([...prev, existing.id, ...parentIds])];
            });
            setActiveVocabulary(existing.rootNode);
            await expandToOptionWithPrefetch(existing);
            return;
        }

        rightPanelHydrateLockRef.current = true;
        setRightPanelHydrateLoadingId(leafId);
        try {
            const merged = await ensureOptionPathLoadedForSearch(leafId);
            vocabularyOptionsRef.current = merged;
            setVocabularyOptions(merged);
            const option = findOptionById(leafId, merged);
            if (!option) {
                Toaster.showServerError('Option could not be placed in the tree');
                return;
            }
            setActiveVocabulary(option.rootNode);
            setCheckedItems((prev) => {
                const parentIds = [];
                let cur = option;
                while (cur && cur.parentOption && cur.parentOption !== cur.rootNode) {
                    parentIds.push(cur.parentOption);
                    cur = findOptionById(cur.parentOption, merged);
                }
                return [...new Set([...prev, ...parentIds])];
            });
            setPendingHydrationMetaById((p) => {
                const n = { ...p };
                for (const k of Object.keys(n)) {
                    if (metaFieldOptionIdsEqual(k, leafId)) delete n[k];
                }
                return n;
            });
            await expandToOptionWithPrefetch(option);
        } catch (e) {
            console.error(e);
            Toaster.showServerError(e);
        } finally {
            rightPanelHydrateLockRef.current = false;
            setRightPanelHydrateLoadingId(null);
        }
    }

    async function fetchAllPagesForParent(props, parentOptionId) {
        const aggregated = [];
        let page = 1;
        while (page <= META_FIELD_OPTIONS_MAX_PAGES) {
            const response = await fetchMetaFieldOptionsPage(
                props,
                parentOptionId,
                page,
                META_FIELD_OPTIONS_PAGE_SIZE
            );
            const batch = response.data || [];
            aggregated.push(...batch);
            if (batch.length < META_FIELD_OPTIONS_PAGE_SIZE) break;
            page++;
        }
        return aggregated;
    }

    async function loadNestedChildren(option, vocabularyRootIdOverride) {
        const rootId =
            vocabularyRootIdOverride != null && vocabularyRootIdOverride !== ''
                ? String(vocabularyRootIdOverride)
                : activeVocabulary;
        if (!rootId) return;
        if (option._childrenLoaded) return;
        if (option.hasChildren !== true) return;
        const loadKey = treeExpansionKey(option.id);
        if (loadingChildrenById[loadKey]) return;

        setLoadingChildrenById((prev) => ({ ...prev, [loadKey]: true }));
        try {
            const aggregated = await fetchAllPagesForParent(props, option.id);
            const normalized = normalizeLazyOptionNodes(aggregated);
            const rootIdCaptured = rootId;
            setVocabularyOptions((prev) => {
                const next = patchVocabularyOptionChildren(
                    prev,
                    rootIdCaptured,
                    option.id,
                    normalized,
                    !props.retainOrder ? label : null
                );
                vocabularyOptionsRef.current = next;
                return next;
            });
        } finally {
            setLoadingChildrenById((prev) => {
                const next = { ...prev };
                delete next[loadKey];
                return next;
            });
        }
    }

    function collectAncestorsRootToParentForPrefetch(targetOption, vocabs) {
        if (!targetOption?.parentOption || !vocabs?.length) return [];
        const up = [];
        let cur = findOptionById(targetOption.parentOption, vocabs);
        while (cur) {
            up.push(cur);
            const p = cur.parentOption;
            if (p == null || metaFieldOptionIdsEqual(p, cur.rootNode)) break;
            cur = findOptionById(p, vocabs);
        }
        return up.reverse();
    }

    async function prefetchChildrenAlongPathToOption(targetOption) {
        const vocabRoot = metaFieldRelationId(targetOption.rootNode);
        if (!vocabRoot) return;
        const ancestors = collectAncestorsRootToParentForPrefetch(
            targetOption,
            vocabularyOptionsRef.current
        );
        for (const anc of ancestors) {
            const fresh = findOptionById(anc.id, vocabularyOptionsRef.current);
            if (!fresh) continue;
            if (fresh.hasChildren === true && !fresh._childrenLoaded) {
                try {
                    await loadNestedChildren(fresh, vocabRoot);
                } catch {
                    break;
                }
            }
        }
    }

    async function expandToOptionWithPrefetch(targetOption) {
        if (!targetOption) return;
        await prefetchChildrenAlongPathToOption(targetOption);
        const fresh =
            findOptionById(targetOption.id, vocabularyOptionsRef.current) || targetOption;
        expandToOption(fresh);
    }

    async function appendNextRootPage() {
        const rootId = activeVocabulary;
        if (!rootId || isLoadingRootPage) return;

        const vocabEntry = vocabularyOptions.find((v) =>
            metaFieldOptionIdsEqual(v.id, rootId)
        );
        if (!vocabEntry?._rootHasMore) return;

        const nextPage = vocabEntry._rootNextPage ?? 2;
        setIsLoadingRootPage(true);
        try {
            const response = await fetchMetaFieldOptionsPage(
                props,
                rootId,
                nextPage,
                META_FIELD_OPTIONS_PAGE_SIZE
            );
            const batch = normalizeLazyOptionNodes(response.data || []);
            setVocabularyOptions((prev) => {
                const next = prev.map((vocab) => {
                    if (!metaFieldOptionIdsEqual(vocab.id, rootId)) return vocab;
                    return {
                        ...vocab,
                        children: appendRootPageChildrenDeduped(
                            vocab.children || [],
                            batch,
                            !props.retainOrder ? label : null
                        ),
                        _rootNextPage: nextPage + 1,
                        _rootHasMore: batch.length >= META_FIELD_OPTIONS_PAGE_SIZE,
                    };
                });
                vocabularyOptionsRef.current = next;
                return next;
            });
        } catch (e) {
            console.error(e);
            Toaster.showServerError(e);
        } finally {
            setIsLoadingRootPage(false);
        }
    }

    function setOptionsForVocabulary(value, data, onError){
        const firstPage = normalizeLazyOptionNodes(data || []);
        setVocabularyOptions((prev) => {
            if (prev.some((item) => metaFieldOptionIdsEqual(item.id, value.id))) {
                onError();
                return prev;
            }
            const next = [
                ...prev,
                {
                    labelNL: value.labelNL,
                    labelEN: value.labelEN,
                    id: String(value.id),
                    children: firstPage,
                    _rootNextPage: 2,
                    _rootHasMore: firstPage.length >= META_FIELD_OPTIONS_PAGE_SIZE,
                },
            ];
            vocabularyOptionsRef.current = next;
            return next;
        });
    }

    function handleVocabularySelected(vocabulary) {
        if (vocabulary) {
            getVocabularyOptions(props, vocabulary.id, (response) => {
                setActiveVocabulary(vocabulary.id);
                setOptionsForVocabulary(vocabulary, response.data, () => {
                    console.error("already exists");
                    setIsLoading(false);
                });
                setIsLoading(false);
            }, (error) => {
                if (error.response.status === 401) {
                    navigate('/login?redirect=' + window.location.pathname);
                }
                Toaster.showServerError(error)
                GlobalPageMethods.setFullScreenLoading(false)
            });
        } else {
            console.error("No vocabulary selected or vocabulary.id is undefined");
            setIsLoading(false);
        }
    }

    function openVocabularyPopup(vocabularyOptions) {
        setIsLoading(true);

        const prefetchConfig = {
            params: {
                "filter[fieldKey]": props.name,
                "filter[ParentOption]": 'null',
                "filter[isRemoved][EQ]": 0,
                'page[number]': 1,
                'page[size]': 10,
            }
        };
        if (!props.retainOrder) {
            prefetchConfig.params.sort = label;
        }

        const onPrefetchError = (error) => {
            if (error?.response?.status === 401) {
                navigate('/login?redirect=' + window.location.pathname);
            }
            Toaster.showServerError(error);
            setIsLoading(false);
        };

        Api.jsonApiGet(
            'metaFieldOptions',
            () => {},
            (response) => {
                const availableOptions = (response.data || []).filter(
                    option => !vocabularyOptions.some(vocab => metaFieldOptionIdsEqual(vocab.id, option.id))
                );

                if (availableOptions.length === 1) {
                    handleVocabularySelected(availableOptions[0]);
                    return;
                }

                VocabularyPopup2.show(
                    props.formReducerState,
                    props.name,
                    props.jsonKey,
                    props.label,
                    handleVocabularySelected,
                    () => {
                        console.log("Popup canceled");
                        setIsLoading(false);
                    },
                    props.retainOrder,
                    vocabularyOptions
                );
            },
            onPrefetchError,
            onPrefetchError,
            prefetchConfig,
        );
    }
}

const EmptyPlaceholder = styled.div`
    width: 100%;
    background: ${cultured};
    margin: 20px 0;
    text-align: center;
    padding: 20px 0;
`;

const TreeMultiSelectFieldRoot = styled.div`
    display: flex;
    flex-direction: column;
    flex: 1;
    position: relative;
    top: 10px;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
`;

const VocabularyTagRow = styled.div`
    display: flex;
    align-items: center;
    gap: 10px;
    flex-direction: row;
    flex-wrap: wrap;
`;

const AddVocabularyButton = styled.div`
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    border: 2px dashed ${majorelle};
    border-radius: 20px;
    cursor: ${props => props.disabled ? 'not-allowed' : 'pointer'};
    opacity: ${props => props.disabled ? 0.5 : 1};
    transition: background-color 0.15s ease, opacity 0.15s ease;

    &:hover {
        background-color: ${props => props.disabled ? 'transparent' : 'rgba(144, 106, 241, 0.1)'};
    }

    span {
        ${openSans()};
        font-size: 13px;
        font-weight: 600;
        color: ${majorelle};
    }
`;

const ContentContainer = styled.div`
    display: flex;
    margin-top: 16px;
    overflow: hidden;
    height: 420px;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
`;

const LeftPanel = styled.div`
    flex: 3;
    display: flex;
    flex-direction: column;
    min-width: 0;
    min-height: 0;
    overflow: visible;
    padding-top: 10px;
    padding-right: 10px;
    border-top: 2px solid ${greyMedium};
`;

const SearchBar = styled.div`
    background-color: ${greyLighter};
    padding: 10px 0 10px 20px;
    display: flex;
    align-items: center;
    border-radius: 10px;
    max-height: 32px;
    border: 1px solid ${greyMedium};
`;

const SearchIcon = styled(FontAwesomeIcon)`
    color: ${greyDark};
    font-size: 14px;
    flex-shrink: 0;
`;

const SearchInput = styled.input`
    width: 100%;
    border: none;
    font-size: 14px !important;
    ${openSans()};
    outline: none;
    background: transparent;

    &::placeholder {
        color: ${greyDark};
    }

    &:focus {
        border-color: ${majorelle};
    }
`;

const OptionsList = styled.div`
    flex: 1;
    min-width: 0;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
`;

const PurpleBar = styled.div`
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    background-color: rgba(144, 106, 241, 0.15);
    border: 2px solid rgba(144, 106, 241, 0.15);
`;

const PurpleBarItemCount = styled.span`
    ${openSans()};
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    color: ${greyDark};
    text-transform: uppercase;
`;

const PurpleBarNumber = styled.span`
    color: ${majorelle};
`;

const PurpleBarBadge = styled.span`
    background-color: ${majorelle};
    color: ${white};
    font-size: 11px;
    font-weight: 700;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
`;

const highlightFade = keyframes`
    0% { background-color: rgba(144, 106, 241, 0.3); }
    100% { background-color: transparent; }
`;

const OptionRow = styled.div`
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px 12px ${props => 16 + (props.level * 24)}px;
    border-bottom: 1px solid ${greyLighter};
    cursor: pointer;
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
    box-sizing: border-box;
    ${props => props.highlighted && css`animation: ${highlightFade} 1.5s ease-out;`}

    &:hover {
        background-color: ${greyLighter};
    }

    &:last-child {
        border-bottom: none;
    }
`;

const OptionRowLeft = styled.div`
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 0;
    overflow: hidden;
`;

const ChevronButton = styled.div`
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    visibility: ${props => props.visible ? 'visible' : 'hidden'};
    cursor: pointer;
    flex-shrink: 0;
`;

const StyledCheckbox = styled.input`
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    min-height: 18px !important;
    accent-color: ${majorelle};
    cursor: pointer;
    margin: 0;
`;

const OptionLabelRow = styled.div`
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
    overflow: hidden;
`;

const OptionLabel = styled.span`
    ${openSans()};
    font-size: 14px;
    user-select: none;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
`;

const SynonymOutlineBadge = styled.span`
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    padding: 2px 8px;
    border: 1px solid ${majorelle};
    border-radius: 4px;
    color: ${majorelle};
    background: transparent;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    ${openSans()};
    cursor: help;
`;

const RightPanel = styled.div`
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
    background-color: rgba(144, 106, 241, 0.15);
    border: 2px solid rgba(144, 106, 241, 0.15);
    border-bottom: none;
    box-sizing: border-box;
`;

const SelectedHeader = styled.div`
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 10px 12px;
    border-bottom: 2px solid rgba(144, 106, 241, 0.15);
`;

const SelectedHeaderGroup = styled.div`
    display: flex;
    align-items: center;
    gap: 10px;
`;

const SelectedTitle = styled.span`
    ${openSans()};
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    color: ${greyDark};
`;

const CountBadge = styled.span`
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

const SelectedContent = styled.div`
    display: flex;
    flex: 1;
    overflow-y: auto;
    overscroll-behavior: contain;
    min-height: 0;
    max-height: 450px;
    padding: 16px;
    flex-direction: column;
    gap: 10px;
    background: transparent;
`;

const NothingSelected = styled.p`
    ${openSans()};
    font-size: 14px;
    color: ${greyDark};
    text-align: center;
    margin-top: 40px;
`;

const SelectedItem = styled.div`
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-sizing: border-box;
    cursor: pointer;
    transition: background-color 0.15s ease;
    min-width: 0;
    background-color: rgba(144, 106, 241, 0.2);
    padding: 10px;
    min-height: 56px;
    border-radius: 10px 0 10px 10px;
    border-bottom: 1px solid ${greyLighter};

    &:hover {
        background-color: rgba(144, 106, 241, 0.3);
    }

    &:last-child {
        border-bottom: none;
    }
`;

const SelectedItemInfo = styled.div`
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: 4px;
    min-width: 0;
`;

const SelectedItemTopRow = styled.div`
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
`;

const SelectedPathText = styled.span`
    ${openSans()};
    font-size: 11px;
    color: ${independence};
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-decoration: underline;
    direction: rtl;
    text-align: left;
`;

const SelectedItemLabel = styled.span`
    ${openSans()};
    font-size: 13px;
    font-weight: 600;
    color: inherit;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
    flex: 1;
`;

const SelectedItemResolving = styled.span`
    ${openSans()};
    font-size: 11px;
    font-style: italic;
    color: ${majorelle};
    margin-top: 2px;
`;

const RemoveButton = styled.div`
    cursor: pointer;
    color: ${greyDark};
    padding: 4px;
    display: flex;
    align-items: center;

    &:hover {
        color: ${majorelle};
    }
`;

const BottomStatus = styled.div`
    ${openSans()};
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1px;
    color: ${greyDark};
    margin-top: 16px;
    text-transform: uppercase;
`;

const LoadingStatus = styled.div`
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    gap: 8px;
`;

const LoadingText = styled.span`
    ${openSans()};
    font-size: 13px;
    color: ${greyDark};
`;

const VocabularyCountBadge = styled.span`
    background-color: ${props => props.active ? majorelle : 'rgba(144, 106, 241, 0.35)'};
    color: ${white};
    font-size: 11px;
    font-weight: 700;
    min-width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
`;

const DeleteIcon = styled(FontAwesomeIcon)`
    width: 10px;
    height: 10px;
    color: ${props => props.active ? majorelle : greyMedium};
`

const SearchBarWrapper = styled.div`
    position: relative;
    z-index: 10;
`;

const SearchResultsDropdown = styled.div`
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: ${white};
    border: 1px solid ${greyMedium};
    border-radius: 0 0 10px 10px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    display: flex;
    flex-direction: column;
    max-height: 400px;
`;

const SearchResultsHeader = styled.h3`
    ${openSans()};
    font-size: 16px;
    font-weight: 700;
    color: ${spaceCadetLight};
    padding: 16px 20px 8px;
    margin: 0;
`;

const SearchResultsList = styled.div`
    flex: 1;
    max-height: 320px;
    overflow-y: auto;
    overscroll-behavior: contain;
`;

const SearchResultsStatus = styled.div`
    padding: 16px 20px;
    text-align: center;
    ${openSans()};
    font-size: 13px;
    color: ${greyDark};
`;

const SearchResultRow = styled.div`
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid ${greyLighter};
    cursor: ${(p) => (p.$dimmed ? 'wait' : 'pointer')};
    opacity: ${(p) => (p.$dimmed ? 0.55 : 1)};
    transition: background-color 0.1s ease, opacity 0.1s ease;

    &:hover {
        background-color: ${(p) => (p.$dimmed ? 'transparent' : greyLighter)};
    }

    &:last-child {
        border-bottom: none;
    }
`;

const SearchResultContent = styled.div`
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
    flex: 1;
`;

const SearchResultLabelRow = styled.div`
    display: flex;
    align-items: baseline;
    gap: 8px;
    flex-wrap: wrap;
`;

const SearchResultPrefLabel = styled.span`
    ${openSans()};
    font-size: 15px;
    font-weight: 700;
    color: #1a1a1a;
    flex-shrink: 0;
`;

const SearchResultAltLabel = styled.span`
    ${openSans()};
    font-size: 13px;
    font-weight: 400;
    color: ${greyDark};
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
`;

const SearchResultBreadcrumb = styled.span`
    ${openSans()};
    font-size: 12px;
    color: ${majorelle};
    text-decoration: underline;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
`;

const SearchResultsFooter = styled.div`
    padding: 10px 20px;
    border-top: 1px solid ${greyLighter};

    span {
        ${openSans()};
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
        color: ${greyDark};
        text-transform: uppercase;
    }
`;

const SelectedVocabularyTag = styled.div`
    padding: 5px 10px;
    border: 2px solid;
    border-color: ${props => props.active ? majorelle : greyMedium};
    border-radius: 20px;
    align-self: center;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    cursor: pointer;

    &:hover {
        ${props => !props.active && `
            border-color: ${spaceCadetLight};
        `}

        p {
            ${props => !props.active && `color: ${spaceCadetLight};`}
        }

        ${DeleteIcon} {
            ${props => !props.active && `color: ${spaceCadetLight};`}
        }
    }

    p {
        font-size: 13px;
        font-weight: 600;
        color: ${props => props.active ? majorelle : greyMedium};
        margin: 0;
    }
`;

const LoadMoreRow = styled.div`
    padding: 12px 8px;
    display: flex;
    justify-content: center;
    border-top: 1px solid ${greyLighter};
`;

const LoadMoreButton = styled.button`
    ${openSans()};
    font-size: 13px;
    font-weight: 600;
    color: ${majorelle};
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 6px 12px;
    border-radius: 8px;

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    &:hover:not(:disabled) {
        background: rgba(144, 106, 241, 0.08);
    }
`;

const LoadingChildrenText = styled.span`
    ${openSans()};
    font-size: 12px;
    color: ${greyDark};
    font-style: italic;
    padding-left: 28px;
`;
