<?php

namespace SurfSharekit\Tasks;

use LogHelper;
use SilverStripe\Dev\BuildTask;
use SurfSharekit\Models\TemplateMetaField;

class GenerateTemplateMetafieldCache extends BuildTask {

    protected $title = 'Generate TemplateMetaField cache';
    protected $description = 'Generates cache for templatemetafields where possible';

    protected $enabled = true;

    function run($request) {
        set_time_limit(0);
        $uncachedTemplateMetafields = TemplateMetaField::get()
            ->leftJoin('SurfSharekit_SimpleCacheItem', 'cache.DataObjectID = SurfSharekit_TemplateMetaField.ID', 'cache')
            ->leftJoin('SurfSharekit_Template', 'template.ID = SurfSharekit_TemplateMetaField.TemplateID', 'template')
            ->leftJoin('SurfSharekit_MetaField', 'metafield.ID = SurfSharekit_TemplateMetaField.MetaFieldID', 'metafield')
            ->leftJoin('SurfSharekit_MetaFieldType', 'metafieldtype.ID = metafield.MetaFieldTypeID', 'metafieldtype')
            ->leftJoin('SurfSharekit_MetaFieldOption', 'metafield.ID = metafieldoption.MetaFieldID', 'metafieldoption')
            ->where("cache.Key IS NULL AND cache.DataObjectClass IS NULL") //filter uncached items
            ->where("metafieldtype.Title != 'DOI'") //filter uncacheable items
            ->where("NOT(template.RepoType = 'LearningObject' AND metafield.SystemKey = 'ContainsParents')") //filter uncacheable items
            ->where("metafieldtype.Key NOT IN ('DropdownTag', 'Tag', 'MultiSelectDropdown', 'Tree-MultiSelect')") //filter uncacheable items
            ->where("metafieldoption.ID IS NULL");
        $count = $uncachedTemplateMetafields->count();
        foreach ($uncachedTemplateMetafields->limit(1000) as $cachacbleTemplateMetaField){
            $cachacbleTemplateMetaField->getJsonAPIDescription();
        }
        echo "$count TemplateMetaield to cache, cached max 1000";
    }
}