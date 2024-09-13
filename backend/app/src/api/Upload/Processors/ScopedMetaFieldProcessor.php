<?php

namespace SilverStripe\api\Upload\Processors;

use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\RepoItem;

abstract class ScopedMetaFieldProcessor extends MetaFieldProcessor {

    private string $rootInstituteUuid;

    public function __construct(RepoItem $repoItem, MetaField $metaField, $value, string $rootInstituteUuid){
        parent::__construct($repoItem, $metaField, $value);
        $this->rootInstituteUuid = $rootInstituteUuid;
    }

    public function getRootInstituteUuid(): string {
        return $this->rootInstituteUuid;
    }
}