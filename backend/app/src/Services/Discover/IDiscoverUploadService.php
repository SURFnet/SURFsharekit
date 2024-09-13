<?php

namespace SurfSharekit\Services\Discover;

use SilverStripe\ORM\DataList;
use SurfSharekit\Models\Institute;

interface IDiscoverUploadService {

    /**
     * Retrieves all institutes starting from a given institute ID, including its hierarchical children.
     *
     * @param string $instituteUuid The UUID of the starting institute.
     * @return DataList<Institute>
     */
    public function getInstitutes($instituteUuid): DataList;


    public function getMetaFields($instituteUuid, $allowedRepoTypes): DataList;

}