<?php

namespace SilverStripe\Services\Institute;

use SilverStripe\models\dashboard\InstituteDaySummary;
use SilverStripe\ORM\DataList;

class InstituteService implements IInstituteService
{
    /**
     * Applies date filters to the given query based on the provided date range.
     *
     * @param DataList $query The query to which the date filters will be applied.
     * @param string|null $from The start date for filtering records (inclusive).
     * @param string|null $until The end date for filtering records (inclusive).
     * @return DataList The modified query with the applied date filters.
     */
    private function applyDateFilters($query, $from = null, $until = null) {
        $filters = [];

        if (!empty($from)) {
            $filters['Day:GreaterThanOrEqual'] = $from;
        }

        if (!empty($until)) {
            $filters['Day:LessThanOrEqual'] = $until;
        }

        if (!empty($filters)) {
            $query = $query->filter($filters);
        }

        return $query;
    }

    /**
     * Gets the aggregated count data for an institute based on the provided date range.
     *
     * @param int $instituteID The ID of the institute for which the count data will be retrieved.
     * @param string|null $from The start date for filtering records (inclusive).
     * @param string|null $until The end date for filtering records (inclusive).
     * @return array The aggregated count data for the institute, with the following keys:
     *     - Downloads: The total number of downloads.
     *     - PublicationRecordCount: The total number of publications.
     *     - LearningObjectCount: The total number of learning objects.
     *     - ResearchObjectCount: The total number of research objects.
     *     - DatasetCount: The total number of datasets.
     *     - PublishedRepoItemCount: The total number of published repository items.
     *     - EmbargoRepoItemCount: The total number of embargoed repository items.
     *     - DraftRepoItemCount: The total number of draft repository items.
     *     - ArchivedRepoItemCount: The total number of archived repository items.
     */
    public function getInstituteCountData($instituteID, $from = null, $until = null): array {
        $instituteSummaryQuery = InstituteDaySummary::get()->filter('InstituteID', $instituteID);
        $query = $this->applyDateFilters($instituteSummaryQuery, $from, $until);

        $totals = [
            'Downloads' => 0,
            'PublicationRecordCount' => 0,
            'LearningObjectCount' => 0,
            'ResearchObjectCount' => 0,
            'DatasetCount' => 0,
            'PublishedRepoItemCount' => 0,
            'EmbargoRepoItemCount' => 0,
            'DraftRepoItemCount' => 0,
            'ArchivedRepoItemCount' => 0
        ];

        foreach ($query as $record) {
            foreach (array_keys($totals) as $column) {
                $totals[$column] += $record->$column;
            }
        }

        return $totals;
    }


    /**
     * Retrieves aggregated UTM source data for a given institute within a specified date range.
     *
     * This function fetches UTM source data for the specified institute and applies date filters
     * to narrow down the records to the given date range. It aggregates the UTM source data by
     * channel, summing up the total, download, and link counts for each channel.
     *
     * @param int $instituteID The ID of the institute for which UTM source data is retrieved.
     * @param string|null $from The start date for filtering records (inclusive). Default is null.
     * @param string|null $until The end date for filtering records (inclusive). Default is null.
     * @return array An array of formatted UTM source data, where each entry contains:
     *               - 'channel': The name of the UTM source channel.
     *               - 'total': The total count for the channel.
     *               - 'download': The download count for the channel.
     *               - 'link': The link count for the channel.
     */
    public function getInstituteUtmSourcesData($instituteID, $from = null, $until = null): array {
        $query = InstituteDaySummary::get()->filter('InstituteID', $instituteID);
        $query = $this->applyDateFilters($query, $from, $until);

        $aggregatedData = [];
        foreach ($query as $record) {
            $utmSourceData = json_decode($record->UtmSourceData, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($utmSourceData)) {
                continue;
            }

            if (is_array($utmSourceData) && !empty($utmSourceData)) {
                foreach ($utmSourceData as $channel => $channelData) {
                    if (is_array($channelData) && isset($channelData['total'])) {
                        if (!isset($aggregatedData[$channel])) {
                            $aggregatedData[$channel] = [
                                'total' => 0,
                                'download' => 0,
                                'link' => 0
                            ];
                        }

                        $aggregatedData[$channel]['total'] += $channelData['total'];
                        $aggregatedData[$channel]['download'] += $channelData['download'];
                        $aggregatedData[$channel]['link'] += $channelData['link'];
                    } else if (is_numeric($channelData)) {
                        if (!isset($aggregatedData[$channel])) {
                            $aggregatedData[$channel] = [
                                'total' => 0,
                                'download' => 0,
                                'link' => 0
                            ];
                        }
                        $aggregatedData[$channel]['total'] += $channelData;
                    }
                }
            }
        }

        $formattedData = [];
        foreach ($aggregatedData as $channel => $counts) {
            $formattedData[] = [
                'channel' => $channel,
                'total' => $counts['total'],
                'download' => $counts['download'],
                'link' => $counts['link']
            ];
        }

        return $formattedData;
    }
}