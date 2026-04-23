<?php

namespace SilverStripe\Services\Institute;

use SurfSharekit\Models\Institute;

interface IInstituteService
{
    /**
     * Retrieves aggregated count metrics for an institute within an optional date range.
     *
     * @param int $instituteID Institute ID
     * @param string|null $from Optional start date
     * @param string|null $until Optional end date
     *
     * @return array Total counts for repository and publication metrics
     */
    public function getInstituteCountData(int $instituteID, string $from, string $until): array;

    /**
     * Retrieves the collected utm sources download data
     *
     * @param int $instituteID Institute ID
     * @param string|null $from Optional start date
     * @param string|null $until Optional end date
     *
     * @return array Total counts for each UTM source
     */
    public function getInstituteUtmSourcesData(int $instituteID, string $from, string $until): array;
}